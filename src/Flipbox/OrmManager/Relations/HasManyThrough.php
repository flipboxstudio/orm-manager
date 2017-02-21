<?php 

namespace Flipbox\OrmManager\Relations;

use ReflectionClass;
use Illuminate\Support\Str;

class HasManyThrough extends Model
{
	/**
	 * set default options
	 *
	 * @return void
	 */
	protected function setDefaultOptions()
	{
		$models = $this->manager->getModels()->pluck('name')->toArray();

		$intermediateModel = $this->manager->makeClass(
								$this->command->choice('Choice intermediate model!', $models)
							 );

		$this->defaultOptions = [
			'intermediate_model' => $intermediateModel,
			'foreign_key_1' => Str::singular($this->model->getTable()).'_'.$this->model->getKeyName(),
			'foreign_key_2' => Str::singular($intermediateModel->getTable()).'_'.$intermediateModel->getKeyName(),
			'primary_key' => $this->model->getKeyName()
		];
	}

	/**
	 * set connected db relation options
	 *
	 * @return void
	 */
	protected function setConnectedRelationOptions()
	{
		$intermediateModel = $this->defaultOptions['intermediate_model'];

		if (! $this->database->isTableExists($intermediateModel->getTable())) {
			throw new TableNotExists("Table {$intermediateModel->getTable()} doesn't exists.");
		}

		if (! $this->database->isFieldExists($table = $intermediateModel->getTable(), $foreignKey1 = $this->checkingOptions['foreign_key_1'])) {
			$this->options['foreign_key_1'] = $this->command->choice(
				"Can't find field {$foreignKey1} in the table {$table} as foreign key, choice one!",
				$this->database->getTableFields($table)
			);
		}

		if (! $this->database->isFieldExists($table = $this->toModel->getTable(), $foreignKey2 = $this->checkingOptions['foreign_key_2'])) {
			$this->options['foreign_key_2'] = $this->command->choice(
				"Can't find field {$foreignKey2} in the table {$table} as foreign key, choice one!",
				$this->database->getTableFields($table)
			);
		}

		if (! $this->database->isFieldExists($table = $this->model->getTable(), $primaryKey = $this->checkingOptions['primary_key'])) {
			$this->options['primary_key'] = $this->command->choice(
				"Can't find field {$primaryKey} in the table {$table} as primary key, choice one!",
				$this->database->getTableFields($table)
			);
		}
	}

	/**
	 * get relation options rules
	 *
	 * @return array
	 */
	protected function getRelationOptionsRules()
	{
		$intermediateModel = $this->defaultOptions['intermediate_model'];

		return [
			"There should be field {$this->defaultOptions['foreign_key_1']} in table {$intermediateModel->getTable()} as foreign key of table {$this->model->getTable()}",
			"There should be field {$this->defaultOptions['foreign_key_2']} in table {$this->toModel->getTable()} as foreign key of table {$intermediateModel->getTable()}",
			"There should be field {$this->defaultOptions['primary_key']} in table {$this->model->getTable()} as primary key of table {$this->model->getTable()}"
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
		$intermediateModel = $this->defaultOptions['intermediate_model'];

		$table = Str::plural(explode('_', $this->defaultOptions['foreign_key_1'])[0]);
		$this->options['foreign_key_1'] = $this->command->ask(
									"The foreign key of table {$table} in the table {$intermediateModel->getTable()} will be?",
									$this->defaultOptions['foreign_key_1']
								);
		
		$table = Str::plural(explode('_', $this->defaultOptions['foreign_key_2'])[0]);
		$this->options['foreign_key_2'] = $this->command->ask(
									"The foreign key of table {$table} in the table {$this->model->getTable()} will be?",
									$this->defaultOptions['foreign_key_2']
								);

		$this->options['primary_key'] = $this->command->ask(
									"The primary key of table {$this->model->getTable()} will be?",
									$this->defaultOptions['primary_key']
								);

	}

	/**
	 * replace more before apply options code
	 *
	 * @param string $stub
	 * @return string
	 */
	protected function beforeApplyOptions($stub)
	{
		$refModel = new ReflectionClass($this->defaultOptions['intermediate_model']);

		return str_replace('DummyIntermediateModel', $refModel->getShortName(), $stub);
	}

	/**
	 * get stub method file
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/../Stubs/hasManyThrough.stub';
	}
}
