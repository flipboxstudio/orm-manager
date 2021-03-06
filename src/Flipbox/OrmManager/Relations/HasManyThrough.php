<?php 

namespace Flipbox\OrmManager\Relations;

use ReflectionClass;
use Illuminate\Support\Str;

class HasManyThrough extends Relation
{
	/**
	 * set default options
	 *
	 * @param array $options
	 * @return void
	 */
	protected function setDefaultOptions(array $options=[])
	{
		if (isset($options['intermediate_model'])) {
			$intermediateModel = $options['intermediate_model'];
		} else {
			$this->text['intermediate_text'] = "[".$this->command->paintString('intermediate model' ,'brown')."]";
			$intermediateModel = $this->manager->getModel(
									$this->command->choice("Choice {$this->text['intermediate_text']} of both relation!",
									$this->manager->getModels()->keys()->toArray())
								 );
		}

		$this->defaultOptions = [
			'intermediate_model' => $intermediateModel,
			'first_key' => $this->model->getForeignKey(),
			'second_key' => $intermediateModel->getForeignKey(),
			'primary_key' => $this->model->getKeyName()
		];
	}

	/**
	 * styling text
	 *
	 * @return void
	 */
	protected function stylingText()
	{
		$this->text['first_key'] = "[".$this->command->paintString($this->defaultOptions['first_key'] ,'green')."]";
		$this->text['second_key'] = "[".$this->command->paintString($this->defaultOptions['second_key'] ,'green')."]";
		$this->text['primary_key'] = "[".$this->command->paintString($this->defaultOptions['primary_key'] ,'green')."]";
		$this->text['model_table'] = "[".$this->command->paintString($this->model->getTable() ,'green')."]";
		$this->text['to_model_table'] = "[".$this->command->paintString($this->toModel->getTable() ,'green')."]";
		$this->text['intermediate_model_table'] = "[".$this->command->paintString($this->defaultOptions['intermediate_model']->getTable() ,'green')."]";
		$this->text['foreign_text'] = $this->command->paintString('foreign key', 'brown');
		$this->text['primary_text'] = $this->command->paintString('primary key', 'brown');
	}

	/**
	 * set connected db relation options
	 *
	 * @return void
	 */
	protected function setConnectedRelationOptions()
	{
		$intermediateModel = $this->defaultOptions['intermediate_model'];

		if (! $this->db->isTableExists($intermediateModel->getTable())) {
			throw new TableNotExists($intermediateModel->getTable(), get_class($intermediateModel));
		}

		if (! $this->db->isFieldExists($table = $intermediateModel->getTable(), $this->defaultOptions['first_key'])) {
			$this->options['first_key'] = $this->command->choice(
				"Can't find field {$this->text['first_key']} in the table {$this->text['intermediate_model_table']} as {$this->text['foreign_text']} of table {$this->text['model_table']}, choice one!",
				$this->getFields($table)
			);
		}

		if (! $this->db->isFieldExists($table = $this->toModel->getTable(), $this->defaultOptions['second_key'])) {
			$this->options['second_key'] = $this->command->choice(
				"Can't find field {$this->text['second_key']} in the table {$this->text['to_model_table']} as {$this->text['foreign_text']} of table {$this->text['intermediate_model_table']}, choice one!",
				$this->getFields($table)
			);
		}

		if (! $this->db->isFieldExists($table = $this->model->getTable(), $primaryKey = $this->defaultOptions['primary_key'])) {
			$this->options['primary_key'] = $this->command->choice(
				"Can't find field {$this->text['primary_key']} in the table {$this->text['model_table']} as {$this->text['primary_text']}, choice one!",
				$this->getFields($table)
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
			"There should be field {$this->text['first_key']} in table {$this->text['intermediate_model_table']} as {$this->text['foreign_text']} of table {$this->text['model_table']}",
			"There should be field {$this->text['second_key']} in table {$this->text['to_model_table']} as {$this->text['foreign_text']} of table {$this->text['intermediate_model_table']}",
			"There should be field {$this->text['primary_key']} in table {$this->text['model_table']} as {$this->text['primary_text']} of table {$this->text['model_table']}"
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

		$this->options['first_key'] = $this->command->ask(
									"The {$this->text['foreign_text']} of table {$this->text['model_table']} in the table {$this->text['intermediate_model_table']} will be?",
									$this->defaultOptions['first_key']
								);
		
		$this->options['second_key'] = $this->command->ask(
									"The {$this->text['foreign_text']} of table {$this->text['intermediate_model_table']} in the table {$this->text['model_table']} will be?",
									$this->defaultOptions['second_key']
								);

		$this->options['primary_key'] = $this->command->ask(
									"The {$this->text['primary_text']} of table {$this->text['model_table']} will be?",
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
		$refModel = new ReflectionClass($this->model);
		$refIntermeidateModel = new ReflectionClass($this->defaultOptions['intermediate_model']);
		
		$model = $refIntermeidateModel->getShortName();
		
		if ($refModel->getNamespaceName() !== $refIntermeidateModel->getNamespaceName()) {
			$model = '\\'.$refIntermeidateModel->getName();
		}

		return str_replace('DummyIntermediateModel', $model, $stub);
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
