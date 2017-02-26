<?php 

namespace Flipbox\OrmManager;

use DB;
use Exception;
use Illuminate\Support\Collection;

class DatabaseConnection
{
	/**
	 * check connection
	 *
	 * @var bool
	 */
	protected $connection = null;

	/**
	 * doctrine
	 *
	 * @var SchmeManager
	 */
	protected $doctrine;

	/**
	 * Create a new DatabaseConnection instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->initDoctrine();
	}
	
	/**
	 * initialize doctrine
	 *
	 * @param  
	 * @return void
	 */
	protected function initDoctrine()
	{
		try {
			$this->doctrine = DB::getDoctrineSchemaManager();
			$platform = $this->doctrine->getDatabasePlatform();
			$platform->registerDoctrineTypeMapping('enum', 'string');
			$this->connection = true;
		} catch (Exception $e) {
			$this->connection = false;
		}
	}

	/**
	 * check database connection
	 *
	 * @return boolean
	 */
	public function isConnected()
	{
		return $this->connection;
	}

	/**
	 * get database tables
	 *
	 * @return array
	 */
	public function getTables()
	{
		if (! $this->isConnected()) {
			return [];
		}

		try {
			return $this->doctrine->listTableNames();
		} catch (Exception $e) {
			return [];
		}
	}

	/**
	 * get database table field
	 *
	 * @param string $table
	 * @return array
	 */
	public function getTableFields($table)
	{
		if (! $this->isConnected()) {
			return [];
		}

		$fileds = [];
		
		try {
			$table = $this->doctrine->listTableDetails($table);

			foreach ($table->getColumns() as $column) {
				$primaryKey =  $table->hasPrimaryKey()
            					? in_array($column->getName(), $table->getPrimaryKey()->getColumns())
            					: flase;

				$foreignKey = false;
				foreach ($table->getIndexes() as $key => $index) {
					if ($key !== 'primary') {
						try {
							$fkConstrain = $table->getForeignkey($key);
							$foreignKey = in_array($column->getName(), $fkConstrain->getColumns());
						} catch (Exception $e) {
							//do noting
						}
					}
				}

				$fileds[] = [
		            'name' => $column->getName(),
		            'type' => $column->getType()->getName(),
		            'not_null' => $column->getNotnull(),
		            'length' => $column->getLength(),
		            'unsigned' => $column->getUnsigned(),
		            'autoincrement' => $column->getAutoincrement(),
		            'primary_key' => $primaryKey,
		            'foreign_key' => $foreignKey
				];
			}
		} catch (Exception $e) {
			$fileds = [];
		}
		
		return new Collection($fileds);
	}

	/**
	 * check is model table exists
	 *
	 * @param string $table
	 * @return boolean
	 */
	public function isTableExists($table)
	{
		$tables = $this->getTables();

		return in_array($table, $tables);
	}

	/**
	 * check is model table exists
	 *
	 * @param string $table
	 * @param string $field
	 * @return boolean
	 */
	public function isFieldExists($table, $field)
	{
		$fields = $this->getTableFields($table)->pluck('name')->toArray();

		return in_array($field, $fields);
	}
}
