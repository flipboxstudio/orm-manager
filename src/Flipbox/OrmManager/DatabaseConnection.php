<?php 

namespace Flipbox\OrmManager;

use Exception;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Support\Collection;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Database\DatabaseManager;

class DatabaseConnection
{
	/**
	 * database manager
	 *
	 * @var DatabaseManager
	 */
	protected $db;

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
	 * tables
	 *
	 * @var array
	 */
	protected $tables;

	/**
	 * Create a new DatabaseConnection instance.
	 *
	 * @return void
	 */
	public function __construct(DatabaseManager $db)
	{
		$this->db = $db;

		$this->initDoctrine();
		$this->scanDatabase();
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
			$this->doctrine = $this->db->getDoctrineSchemaManager();
			
			$platform = $this->doctrine->getDatabasePlatform();
			$platform->registerDoctrineTypeMapping('enum', 'string');
			$platform->registerDoctrineTypeMapping('json', 'json_array');
			
			$this->connection = true;
		} catch (PDOException $e) {
			$this->connection = false;
		}
	}

	/**
	 * scan database
	 *
	 * @return void
	 */
	protected function scanDatabase()
	{
		if ($this->isConnected()) {
			$tables = $this->doctrine->listTableNames();

			foreach ($tables as $table) {
				$schTable = $this->doctrine->listTableDetails($table);

				$this->tables[$table] = $this->getTableFields($schTable);
			}
		}
	}

	/**
	 * get database fileds
	 *
	 * @param Table $table
	 * @return array
	 */
	protected function getTableFields(Table $table)
	{
		$fileds = [];

		foreach ($table->getColumns() as $column) {
			$fileds[] = [
	            'name' => $column->getName(),
	            'type' => $column->getType()->getName(),
	            'not_null' => $column->getNotnull(),
	            'length' => $column->getLength(),
	            'unsigned' => $column->getUnsigned(),
	            'autoincrement' => $column->getAutoincrement(),
	            'primary_key' => $this->isPrimaryKey($table, $column),
	            'foreign_key' => $this->isForeignKey($table, $column)
			];
		}

		return $fileds;
	}

	/**
	 * check is column primary key
	 *
	 * @param Table $table
	 * @param Column $column
	 * @return bool
	 */
	protected function isPrimaryKey(Table $table, Column $column)
	{
		if ($table->hasPrimaryKey()) {
			$primaryKeys = $table->getPrimaryKey()->getColumns();

			return in_array($column->getName(), $primaryKeys);
		}

		return false;
	}

	/**
	 * check is column foreign key
	 *
	 * @param Table $table
	 * @param Column $column
	 * @return bool
	 */
	protected function isForeignKey(Table $table, Column $column)
	{
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

		return $foreignKey;
	}

	/**
	 * check database connection
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->connection;
	}

	/**
	 * get tables
	 *
	 * @return array
	 */
	public function getTables()
	{
		return new Collection($this->tables);
	}

	/**
	 * get table fields
	 *
	 * @param string $table
	 * @return array
	 */
	public function getFields($table)
	{
		if (isset($this->tables[$table])) {
			return new Collection($this->tables[$table]);
		}
	}

	/**
	 * check is model table exists
	 *
	 * @param string $table
	 * @return bool
	 */
	public function isTableExists($table)
	{
		return isset($this->tables[$table]);
	}

	/**
	 * check is model table exists
	 *
	 * @param string $table
	 * @param string $field
	 * @return bool
	 */
	public function isFieldExists($table, $field)
	{
		if (isset($this->tables[$table])) {
			$fields = $this->getFields($table);

			return $fields->where('name', $field)->count() > 0;
		}

		return false;
	}
}
