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
	 * set default options
	 *
	 * @param array $options
	 * @return void
	 */
	protected function setDefaultOptions(array $options=[])
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
			$foreignKey = Str::singular($table).'_'.$this->$key->getKeyName();
			$this->defaultOptions['foreign_key_'.($no++)] = $foreignKey;
			$this->maps[$foreignKey] = $this->manager->tableToModel($table);
		}

		$this->checkingOptions = array_merge($this->defaultOptions, $options);
	}

	/**
	 * styling text
	 *
	 * @return void
	 */
	protected function stylingText()
	{
		$pivotTable = $this->checkingOptions['pivot_table'];

		$this->text = [
			'pivot_table' => "[".$this->command->paintString($pivotTable ,'green')."]",
			'pivot_text' => $this->command->paintString('pivot table', 'brown'),
			'foreign_text' => $this->command->paintString('foreign key', 'brown'),
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

		if (! $this->db->isTableExists($pivotTable)) {
			$question = "Can't find table {$this->text['pivot_table']} in the database as {$this->text['pivot_text']}, choice one!";
			$pivotTable = $this->options['pivot_table'] = $this->command->choice(
				$question, $this->db->getTables());

			$this->text['pivot_table'] = "[".$this->command->paintString($pivotTable, 'green')."]";
		}

		$key = 1;

		foreach ($this->maps as $foreignKey => $model) {
			$paintedTable = "[".$this->command->paintString($model->getTable(), 'green')."]";
			$foreignKey = $this->checkingOptions['foreign_key_'.$key];
			$paintedForeignKey = "[".$this->command->paintString($foreignKey, 'green')."]";

			if (! $this->db->isFieldExists($pivotTable, $foreignKey)) {
				$question = "Can't find field {$paintedForeignKey} in the table {$this->text['pivot_table']} as {$this->text['foreign_text']} of table {$paintedTable}, choice one!";
				$this->options['foreign_key_'.$key] = $this->command->choice(
					$question, $this->getFields($pivotTable));
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
		$this->text['pivot_table'] = "[".$this->command->paintString($this->defaultOptions['pivot_table'], 'green')."]";
		$rules = ["There should be table {$this->text['pivot_table']} in the database as {$this->text['pivot_text']}"];
		
		$key = 1;
		
		foreach ($this->maps as $foreignKey => $model) {
			$paintedTable = "[".$this->command->paintString($model->getTable(), 'green')."]";
			$foreignKey = $this->checkingOptions['foreign_key_'.$key];
			$paintedForeignKey = "[".$this->command->paintString($foreignKey, 'green')."]";	
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
		$question = "The {$this->text['pivot_text']} in the database will be?";
		$this->options['pivot_table'] = $this->command->ask($question, $this->defaultOptions['pivot_table']);
		$this->text['pivot_table'] = "[".$this->command->paintString($this->options['pivot_table'], 'green')."]";

		$key = 1;

		foreach ($this->maps as $foreignKey => $model) {
			$paintedTable = "[".$this->command->paintString($model->getTable(), 'green')."]";
			$foreignKey = $this->checkingOptions['foreign_key_'.$key];
			$paintedForeignKey = "[".$this->command->paintString($foreignKey, 'green')."]";
			
			$question = "The {$this->text['foreign_text']} of table {$paintedTable} in the table {$this->text['pivot_table']} will be?";
			
			$this->options['foreign_key_'.$key] = $this->command->ask($question, $this->defaultOptions['foreign_key_'.$key]);
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