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
	 * @return void
	 */
	protected function setDefaultOptions()
	{
		if (! isset($this->checkingOptions['name'])) {		
			$refModel = new ReflectionClass($this->model);
			$name = $this->getRelationName(strtolower($refModel->getShortName()));
	        $name = $this->command->ask('What relation name do you use?', $name);
		} else {
			$name = $this->checkingOptions['name'];
		}

		$this->defaultOptions = [
			'name' => $name,
			'type' => $this->getTypeName($name),
			'id' => $this->getIdName($name)
		];
	}

	/**
	 * set connected db relation options
	 *
	 * @return void
	 */
	protected function setConnectedRelationOptions()
	{
		$fields = $this->database->getTableFields($this->model->getTable());

		if (! isset($this->checkingOptions['name'])) {		
			$refModel = new ReflectionClass($this->model);
			$name = $this->getRelationName(strtolower($refModel->getShortName()));
	        $name = $this->command->ask('What relation name do you use?', $name);
		} else {
			$name = $this->checkingOptions['name'];
		}

		if (! in_array($this->defaultOptions['type'], $fields) 
			OR ! in_array($this->defaultOptions['id'], $fields)) {
			$this->options['name'] = $name = $this->command->ask("Can't find {$this->defaultOptions['type']} OR {$this->defaultOptions['id']}, you may not use {$this->defaultOptions['name']} as relation name, what are you using?");
		}

		if (! in_array($this->getTypeName($name ?: null), $fields)) {
			$this->options['type'] = $this->command->choice("Can't find {$this->defaultOptions['type']}, what are you using?", $fields);
		}

		if (! in_array($this->getIdName($name ?: null), $fields)) {
			$this->options['id'] = $this->command->choice("Can't find {$this->defaultOptions['id']}, what are you using?", $fields);
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
			"Relation name is {$this->defaultOptions['name']}",
			"There should be field {$this->defaultOptions['type']} in table {$this->model->getTable()}",
			"There should be field {$this->defaultOptions['id']} in table {$this->model->getTable()}",
		];
	}

	/**
	 * ask to use custome options
	 *
	 * @return void
	 */
	protected function askToUseCustomeOptions()
	{
		$this->options['name'] = $this->command->ask("The name of relation is will be?", $this->defaultOptions['name']);
		$this->options['type'] = $this->command->ask("The type of relation is will be?", $this->getTypeName($this->options['name']));
		$this->options['id'] = $this->command->ask("The name of relation is will be?", $this->getIdName($this->options['name']));
	}

	/**
	 * generate method relation name
	 *
	 * @param string $relation
	 * @return string
	 */
	protected function generateMethodName()
	{
		$refModel = new ReflectionClass($this->model);
		$name = $refModel->getShortName();

		$methodName = Str::camel($this->getMethodName($name));

		if ($this->manager->isMethodExists($this->model, $methodName)) {
			throw new MethodAlreadyExists($methodName);
		}

		return $methodName;
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
    	return $this->getRelationName($name);
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
