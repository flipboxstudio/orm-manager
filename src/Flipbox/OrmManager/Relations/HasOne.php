<?php 

namespace Flipbox\OrmManager\Relations;

use Illuminate\Support\Str;

class HasOne extends Model
{
	/**
	 * colored asset text
	 *
	 * @var array
	 */
	private $text = [];

	/**
	 * set default options
	 *
	 * @return void
	 */
	protected function setDefaultOptions()
	{
		$this->defaultOptions = [
			'foreign_key' => Str::singular($this->model->getTable()).'_'.$this->model->getKeyName(),
			'primary_key' => $this->model->getKeyName()
		];
	}

	/**
	 * preparation set options
	 *
	 * @return void
	 */
	protected function preparationSetOptions()
	{
		$modelTable = $table = $this->model->getTable();
		$toModelTable = $table = $this->toModel->getTable();
		$foreignKey = $this->checkingOptions['foreign_key'];
		$primaryKey = $this->checkingOptions['primary_key'];

		$this->text = [
			'table' => "[".$this->paintString($modelTable ,'green')."]",
			'to_table' => "[".$this->paintString($toModelTable ,'green')."]",
			'foreign_key' => "[".$this->paintString($foreignKey ,'green')."]",
			'primary_key' => "[".$this->paintString($primaryKey ,'green')."]",
			'primary_text' => $this->paintString('primary key', 'light_gray'),
			'foreign_text' => $this->paintString('foreign key', 'light_gray')
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
			print("Can't find field {$this->text['foreign_key']} in the table {$this->text['to_table']} as {$this->text['foreign_text']} of table {$this->text['table']}");
			$this->options['foreign_key'] = $this->command->choice("choice one!", $this->getFields($toModelTable));
		}

		if (! $this->database->isFieldExists($modelTable, $primaryKey)) {
			print("Can't find field {$this->text['primary_key']} in the table {$this->text['table']} as {$this->text['primary_text']} of table {$this->text['table']}");
			$this->options['primary_key'] = $this->command->choice("\n choice one!", $this->getFields($modelTable));
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
		print("The {$this->text['foreign_text']} of table {$this->text['table']} in the table {$this->text['to_table']}");
		$this->options['foreign_key'] = $this->command->ask("will be?", $this->defaultOptions['foreign_key']);

		print("The {$this->text['primary_text']} of the table {$this->text['table']}");
		$this->options['primary_key'] = $this->command->ask("\n will be?", $this->defaultOptions['primary_key']);
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
