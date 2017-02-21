<?php 

namespace Flipbox\OrmManager;

use DB;
use Exception;
use Illuminate\Support\Collection;

class DatabaseConnection
{
	/**
	 * check database connection
	 *
	 * @return boolean
	 */
	public function isConnected()
	{
		try {
		    DB::connection()->getPdo();
		    return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * get database tables
	 *
	 * @return array
	 */
	public function getTables()
	{
		try {
			$tables = DB::select('SHOW TABLES');
			
			$results = [];
			
			foreach ($tables as $table) {
				$results[] = $table->Tables_in_orm;
			}

			return $results;
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
		$fileds = [];

		try {
			$table = DB::getDoctrineSchemaManager()->listTableDetails($table);

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
