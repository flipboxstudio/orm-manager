<?php 

namespace Flipbox\OrmManager\Relations;

use Illuminate\Support\Str;

class HasOne extends Relation
{
	/**
	 * set default options
	 *
	 * @param array $options
	 * @return void
	 */
	protected function setDefaultOptions(array $options=[])
	{
		$this->defaultOptions = [
			'foreign_key' => Str::singular($this->model->getTable()).'_'.$this->model->getKeyName(),
			'primary_key' => $this->model->getKeyName()
		];

		$this->checkingOptions = array_merge($this->defaultOptions, $options);
	}

	/**
	 * styling text
	 *
	 * @return void
	 */
	protected function stylingText()
	{
		$modelTable = $table = $this->model->getTable();
		$toModelTable = $table = $this->toModel->getTable();
		$foreignKey = $this->checkingOptions['foreign_key'];
		$primaryKey = $this->checkingOptions['primary_key'];

		$this->text = [
			'table' => "[".$this->command->paintString($modelTable ,'green')."]",
			'to_table' => "[".$this->command->paintString($toModelTable ,'green')."]",
			'foreign_key' => "[".$this->command->paintString($foreignKey ,'green')."]",
			'primary_key' => "[".$this->command->paintString($primaryKey ,'green')."]",
			'primary_text' => $this->command->paintString('primary key', 'brown'),
			'foreign_text' => $this->command->paintString('foreign key', 'brown')
		];
	}

	/**
	 * set connected db relation options
	 *
	 * @return void
	 */
	protected function setConnectedRelationOptions()
	{
		$modelTable = $table = $this->model->getTable();
		$toModelTable = $table = $this->toModel->getTable();
		$foreignKey = $this->checkingOptions['foreign_key'];
		$primaryKey = $this->checkingOptions['primary_key'];
		
		if (! $this->database->isFieldExists($toModelTable, $foreignKey)) {
			$question = "Can't find field {$this->text['foreign_key']} in the table {$this->text['to_table']} as {$this->text['foreign_text']} of table {$this->text['table']}, choice one!";
			$this->options['foreign_key'] = $this->command->choice($question, $this->getFields($toModelTable));
		}

		if (! $this->database->isFieldExists($modelTable, $primaryKey)) {
			$question = "Can't find field {$this->text['primary_key']} in the table {$this->text['table']} as {$this->text['primary_text']} of table {$this->text['table']}, choice one!";
			$this->options['primary_key'] = $this->command->choice($question, $this->getFields($modelTable));
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
			"There should be field {$this->text['foreign_key']} in table {$this->text['to_table']} as {$this->text['foreign_text']} of table {$this->text['table']}",
			"There should be field {$this->text['primary_key']} in table {$this->text['table']} as {$this->text['primary_text']} of table {$this->text['table']}"
		];
	}

	/**
	 * ask to use custome options
	 *
	 * @return void
	 */
	protected function askToUseCustomeOptions()
	{
		$question = "The {$this->text['foreign_text']} of table {$this->text['table']} in the table {$this->text['to_table']}, will be?";
		$this->options['foreign_key'] = $this->command->ask($question, $this->defaultOptions['foreign_key']);

		$question = "The {$this->text['primary_text']} of the table {$this->text['table']}, will be?";
		$this->options['primary_key'] = $this->command->ask($question, $this->defaultOptions['primary_key']);
	}

	/**
	 * get stub method file
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/../Stubs/hasOne.stub';
	}
}
