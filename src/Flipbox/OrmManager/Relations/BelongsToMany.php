<?php 

namespace Flipbox\OrmManager\Relations;

use Illuminate\Support\Str;

class BelongsToMany extends Relation
{
	/**
	 * maps
	 *
	 * @var array
	 */
	protected $maps;

	/**
	 * colored asset text
	 *
	 * @var array
	 */
	private $text;

	/**
	 * set default options
	 *
	 * @return void
	 */
	protected function setDefaultOptions()
	{
		$tables = [
			'model' => $this->model->getTable(),
			'toModel' => $this->toModel->getTable()
		];

		asort($tables, SORT_REGULAR);
		$pivotTable = implode('_', array_map([Str::class, 'singular'], $tables));
		$this->defaultOptions['pivot_table'] = $pivotTable;

		$no = 1;
		foreach ($tables as $key => $table) {
			$foreignKey = $table.'_'.$this->$key->getKeyName();
			$this->defaultOptions['foreign_key_'.($no++)] = $foreignKey;
			$this->maps[$foreignKey] = $this->manager->tableToModel($table);
		}
	}

	/**
	 * preparation set options
	 *
	 * @return void
	 */
	protected function preparationSetOptions()
	{
		$pivotTable = $this->checkingOptions['pivot_table'];

		$this->text = [
			'pivot_table' => "[".$this->paintString($pivotTable ,'green')."]",
			'pivot_text' => $this->paintString('pivot table', 'light_gray'),
			'foreign_text' => $this->paintString('foreign key', 'light_gray'),
		];
	}

	/**
	 * set connected db relation options
	 *
	 * @return void
	 */
	protected function setConnectedRelationOptions()
	{
		$pivotTable = $this->checkingOptions['pivot_table'];

		if (! $this->database->isTableExists($pivotTable)) {
			print("Can't find table {$this->text['pivot_table']} in the database as {$this->text['pivot_text']}");
			$pivotTable = $this->options['pivot_table'] = $this->command->choice(
				"choice one!", $this->database->getTables());

			$this->text['pivot_table'] = "[".$this->paintString($pivotTable, 'green')."]";
		}

		$key = 1;

		foreach ($this->maps as $foreignKey => $model) {
			$paintedTable = "[".$this->paintString($model->getTable(), 'green')."]";
			$foreignKey = $this->checkingOptions['foreign_key_'.$key];
			$paintedForeignKey = "[".$this->paintString($foreignKey, 'green')."]";

			if (! $this->database->isFieldExists($pivotTable, $foreignKey)) {
				print("Can't find field {$paintedForeignKey} in the table {$this->text['pivot_table']} as {$this->text['foreign_text']} of table {$paintedTable}");
				$this->options['foreign_key_'.$key] = $this->command->choice(
					"\n choice one!", $this->getFields($pivotTable));
			}

			$key++;
		}		
	}

	/**
	 * get relation options rules
	 *
	 * @return array
	 */
	protected function getRelationOptionsRules()
	{
		$this->text['pivot_table'] = "[".$this->paintString($this->defaultOptions['pivot_table'], 'green')."]";
		$rules = ["There should be table {$this->text['pivot_table']} in the database as {$this->text['pivot_text']}"];
		
		$key = 1;
		
		foreach ($this->maps as $foreignKey => $model) {
			$paintedTable = "[".$this->paintString($model->getTable(), 'green')."]";
			$foreignKey = $this->checkingOptions['foreign_key_'.$key];
			$paintedForeignKey = "[".$this->paintString($foreignKey, 'green')."]";	
			$rules[] = "There should be field {$paintedForeignKey} in the table {$this->text['pivot_table']} as {$this->text['foreign_text']} of table {$paintedTable}";
			$key++;
		}

		return $rules;
	}
	
	/**
	 * get method name form class
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getMethodName($name)
	{
		return Str::plural($name);
	}

	/**
	 * ask to use custome options
	 *
	 * @return void
	 */
	protected function askToUseCustomeOptions()
	{
		print("The {$this->text['pivot_text']} in the database");
		$this->options['pivot_table'] = $this->command->ask(' will be?', $this->defaultOptions['pivot_table']);
		$this->text['pivot_table'] = "[".$this->paintString($this->options['pivot_table'], 'green')."]";

		$key = 1;

		foreach ($this->maps as $foreignKey => $model) {
			$paintedTable = "[".$this->paintString($model->getTable(), 'green')."]";
			$foreignKey = $this->checkingOptions['foreign_key_'.$key];
			$paintedForeignKey = "[".$this->paintString($foreignKey, 'green')."]";
			
			print("The {$this->text['foreign_text']} of table {$paintedTable} in the table {$this->text['pivot_table']}");
			
			$this->options['foreign_key_'.$key] = $this->command->ask("\n will be?", $this->defaultOptions['foreign_key_'.$key]);
			$key++;
		}
	}

	/**
	 * get stub method file
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/../Stubs/belongsToMany.stub';
	}
}