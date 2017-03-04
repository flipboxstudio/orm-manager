<?php 

namespace Flipbox\OrmManager\Relations;

use ReflectionClass;
use Illuminate\Support\Str;
use Flipbox\OrmManager\Exceptions\MethodAlreadyExists;

class MorphTo extends Relation
{
	/**
	 * method suffix
	 *
	 * @var string
	 */
	protected $nameSuffix = 'able';

	/**
	 * type suffix
	 *
	 * @var string
	 */
	protected $typeSuffix = '_type';

	/**
	 * id suffix
	 *
	 * @var string
	 */
	protected $idSuffix = '_id';

	/**
	 * set default options
	 *
	 * @param $options
	 * @return void
	 */
	protected function setDefaultOptions(array $options=[])
	{
		$this->text['relation_name_text'] = $this->command->paintString('relation name', 'brown');

		if (! isset($options['name'])) {
			$refModel = new ReflectionClass($this->model);
			$name = $this->getRelationName(strtolower($refModel->getShortName()));
	        $name = $this->command->ask("What {$this->text['relation_name_text']} do you use?", $name);
		} else {
			$name = $options['name'];
		}

		$this->defaultOptions = [
			'name' => $name,
			'type' => $this->getTypeName($name),
			'id' => $this->getIdName($name)
		];
	}

	/**
	 * styling text
	 *
	 * @return void
	 */
	protected function stylingText()
	{
		$this->text = array_merge($this->text, [
			'table' => "[".$this->command->paintString($this->model->getTable(), 'green')."]",
			'name' => "[".$this->command->paintString($this->defaultOptions['name'], 'green')."]",
			'type' => "[".$this->command->paintString($this->defaultOptions['type'], 'green')."]",
			'id' => "[".$this->command->paintString($this->defaultOptions['id'], 'green')."]",
			'related_type_text' => $this->command->paintString('related type', 'brown'),
			'related_id_text' => $this->command->paintString('related id', 'brown')
		]);
	}

	/**
	 * set connected db relation options
	 *
	 * @return void
	 */
	protected function setConnectedRelationOptions()
	{
		$fields = $this->getFields($this->model->getTable());
		$name = $this->defaultOptions['name'];

		if (! in_array($this->defaultOptions['type'], $fields) 
			OR ! in_array($this->defaultOptions['id'], $fields)) {
			$this->options['name'] = $name = $this->command->ask("Can't find {$this->text['type']} or {$this->text['id']} in the table {$this->text['table']}, you may not use {$this->text['name']} as {$this->text['relation_name_text']}, what are you using?");
			$this->text['type'] = "[".$this->command->paintString($this->getTypeName($name), 'green')."]";
			$this->text['id'] = "[".$this->command->paintString($this->getIdName($name), 'green')."]";
		}

		if (! in_array($this->getTypeName($name), $fields)) {
			$this->options['type'] = $this->command->choice("Can't find {$this->text['type']} as {$this->text['related_type_text']}, what are you using?", $fields);
		}

		if (! in_array($this->getIdName($name), $fields)) {
			$this->options['id'] = $this->command->choice("Can't find {$this->text['id']} as {$this->text['related_id_text']}, what are you using?", $fields);
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
			"The {$this->text['relation_name_text']} is {$this->text['name']}",
			"There should be field {$this->text['type']} in table {$this->text['table']} as {$this->text['related_type_text']}",
			"There should be field {$this->text['id']} in table {$this->text['table']} as {$this->text['related_id_text']}",
		];
	}

	/**
	 * ask to use custome options
	 *
	 * @return void
	 */
	protected function askToUseCustomeOptions()
	{
		$this->options = [
			'name' => $this->command->ask("The {$this->text['relation_name_text']} of relation is will be?", $this->defaultOptions['name']),
			'type' => $this->command->ask("The {$this->text['related_type_text']} of relation is will be?", $this->getTypeName($this->options['name'])),
			'id' => $this->command->ask("The {$this->text['related_id_text']} of relation is will be?", $this->getIdName($this->options['name'])),
		];
	}

	/**
	 * replace more before apply options code
	 *
	 * @param string $stub
	 * @return string
	 */
	protected function beforeApplyOptions($stub)
	{
		if (count($this->defaultOptions) === count($this->options)) {
			if ($this->options['type'] === $this->getTypeName($this->options['name'])) {
				unset($this->options['type']);
			}

			if ($this->options['id'] === $this->getIdName($this->options['name'])) {
				unset($this->options['id']);
			}
		}

		return $stub;
	}

    /**
     * get method name form class
     *
     * @param string $name
     * @return string
     */
    protected function getMethodName($name)
    {
		$methodName = $this->getRelationName($name);

		if ($this->defaultOptions['name'] !== $methodName) {
			return $this->defaultOptions['name'];
		}

		return $methodName;
    }

	/**
	 * get method name form class
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getRelationName($name)
	{
		return $name.$this->nameSuffix;
	}

	/**
	 * get type name
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getTypeName($name)
	{
		return ($name ?: $this->defaultOptions['name']).$this->typeSuffix;
	}

	/**
	 * get id name
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getIdName($name)
	{
		return ($name ?: $this->defaultOptions['name']).$this->idSuffix;
	}

	/**
	 * get stub method file
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/../Stubs/morphTo.stub';
	}
}
