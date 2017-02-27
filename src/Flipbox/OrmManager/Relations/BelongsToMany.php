<?php 

namespace Flipbox\OrmManager\Relations;

use Illuminate\Support\Str;

class BelongsToMany extends Relation
{
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
		
		$this->defaultOptions = [
			'pivot_table' => $pivotTable,
			'foreign_key' => $this->model->getForeignKey(),
			'related_key' => $this->toModel->getForeignKey(),
			'relation' => $this->toModel->getTable()
		];
	}

	/**
	 * styling text
	 *
	 * @return void
	 */
	protected function stylingText()
	{
		$modelTable = $this->model->getTable();
		$toModelTable = $this->toModel->getTable();
		$pivotTable = $this->defaultOptions['pivot_table'];
		$foreignKey = $this->defaultOptions['foreign_key'];
		$relatedKey = $this->defaultOptions['related_key'];

		$this->text = [
			'table' => "[".$this->command->paintString($modelTable ,'green')."]",
			'to_table' => "[".$this->command->paintString($toModelTable ,'green')."]",
			'pivot_table' => "[".$this->command->paintString($pivotTable ,'green')."]",
			'foreign_key' => "[".$this->command->paintString($foreignKey ,'green')."]",
			'related_key' => "[".$this->command->paintString($relatedKey ,'green')."]",
			'pivot_text' => $this->command->paintString('pivot table', 'brown'),
			'foreign_text' => $this->command->paintString('foreign key', 'brown'),
			'related_text' => $this->command->paintString('related key', 'brown'),
		];
	}

	/**
	 * set connected db relation options
	 *
	 * @return void
	 */
	protected function setConnectedRelationOptions()
	{
		$pivotTable = $this->defaultOptions['pivot_table'];
		$foreignKey = $this->defaultOptions['foreign_key'];
		$relatedKey = $this->defaultOptions['related_key'];

		if (! $this->db->isTableExists($pivotTable)) {
			$question = "Can't find table {$this->text['pivot_table']} in the database as {$this->text['pivot_text']}, choice one!";
			$pivotTable = $this->options['pivot_table'] = $this->command->choice(
				$question, $this->getTables());

			$this->text['pivot_table'] = "[".$this->command->paintString($pivotTable, 'green')."]";
		}

		if (! $this->db->isFieldExists($pivotTable, $foreignKey)) {
			$question = "Can't find field {$this->text['foreign_key']} in the table {$this->text['pivot_table']} as {$this->text['foreign_text']} of table {$this->text['table']}, choice one!";
			$this->options['foreign_key'] = $this->command->choice($question, $this->getFields($pivotTable));
		}

		if (! $this->db->isFieldExists($pivotTable, $relatedKey)) {
			$question = "Can't find field {$this->text['related_key']} in the table {$this->text['pivot_table']} as {$this->text['related_text']} of table {$this->text['to_table']}, choice one!";
			$this->options['related_key'] = $this->command->choice($question, $this->getFields($pivotTable));
		}
	}

	/**
	 * get relation options rules
	 *
	 * @return array
	 */
	protected function getRelationOptionsRules()
	{
		return [
			"There should be table {$this->text['pivot_table']} in the database as {$this->text['pivot_text']}",
			"There should be field {$this->text['foreign_key']} in table {$this->text['pivot_table']} as {$this->text['foreign_text']} of table {$this->text['table']}",
			"There should be field {$this->text['related_key']} in table {$this->text['pivot_table']} as {$this->text['related_key']} of table {$this->text['to_table']}"
		];
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

		$question = "The {$this->text['foreign_text']} of table {$this->text['table']} in the table {$this->text['pivot_table']}, will be?";
		$this->options['foreign_key'] = $this->command->ask($question, $this->defaultOptions['foreign_key']);

		$question = "The {$this->text['related_text']} of table {$this->text['to_table']} in the table {$this->text['pivot_table']}, will be?";
		$this->options['related_key'] = $this->command->ask($question, $this->defaultOptions['related_key']);
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