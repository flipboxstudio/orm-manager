<?php 

namespace Flipbox\OrmManager\Relations;

use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\DatabaseConnection;
use Flipbox\OrmManager\Exceptions\TableNotExists;
use Flipbox\OrmManager\Exceptions\MethodAlreadyExists;

abstract class Relation
{
	/**
	 * laravel Command
	 *
	 * @var Command
	 */
	protected $command;

	/**
	 * model manager
	 *
	 * @var ModelManager
	 */
	protected $manager;

	/**
	 * database connection
	 *
	 * @var DatabaseConnection
	 */
	protected $db;

	/**
	 * model that want to connect to
	 *
	 * @var Model
	 */
	protected $model;

	/**
	 * model that will connect with
	 *
	 * @var Model
	 */
	protected $toModel;

	/**
	 * options connection models
	 *
	 * @var array
	 */
	protected $options = [];

	/**
	 * default options connection models
	 *
	 * @var array
	 */
	protected $defaultOptions = [];

	/**
	 * required options to replace
	 *
	 * @var array
	 */
	protected $requiredOptions = [];

	/**
	 * prefix new line
	 *
	 * @var string
	 */
	protected $prefixNewline = PHP_EOL;

	/**
	 * reverse operation
	 *
	 * @var bool
	 */
	protected $reverse = false;

	/**
	 * colored asset text
	 *
	 * @var array
	 */
	protected $text = [];

	/**
	 * method name
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * Create a new Model instance.
	 *
	 * @param Command $command
	 * @param ModelManager $manager
	 * @param Model $model
	 * @param mixed $toModel
	 * @param array $options
	 * @return void
	 */
	public function __construct(Command $command,
								ModelManager $manager,
								Model $model,
								$toModel=null,
								array $options=[])
	{
		$this->command = $command;
		$this->manager = $manager;
		$this->db = $this->manager->db;
		$this->model = $this->reverse ? $toModel : $model;
		$this->toModel = $this->reverse ? $model : $toModel;
		
		$this->setDefaultOptions($options);
		$this->showCaptionProcess($model, $toModel);

		$this->method = $this->generateMethodName();

		$this->stylingText();
		$this->setRelationOptions($options);
	}

	/**
	 * show captions process
	 *
	 * @param Model $model
	 * @param Model $toModel
	 * @return void
	 */
	protected function showCaptionProcess(Model $model, Model $toModel=null)
	{
		$modelName = $this->manager->getClassName($model);
		$toModelName = $this->manager->getClassName($toModel);
		$relationName = $this->manager->getClassName($this);

		$caption = "Creating relation {$modelName} {$relationName} {$toModelName} :";

		$this->command->title($caption);
	}

	/**
	 * set relation option required
	 *
	 * @param array $options
	 * @return void
	 */
	protected function setRelationOptions(array $options = [])
	{
		if (count($options) > 0) {
			$this->options = $options;
		} elseif ($this->db->isConnected()) {
			$this->checkModelDatabases();
			$this->setConnectedRelationOptions();
		} else {
			$this->setNotConnectedRelationOptions();
		}
	}

	/**
	 * check model databases
	 *
	 * @param  
	 * @return void
	 */
	protected function checkModelDatabases()
	{
		$modelName = $this->manager->getClassName($this->model);
		$toModelName = $this->manager->getClassName($this->toModel);

		if (! $this->db->isTableExists($this->model->getTable())) {
			throw new TableNotExists($this->model->getTable(), $modelName);
		}

		if (! is_null($this->toModel) AND ! $this->db->isTableExists($this->toModel->getTable())) {
			throw new TableNotExists($this->toModel->getTable(), $toModelName);
		}
	}

	/**
	 * create method in the model
	 *
	 * @return void
	 */
	public function createMethod()
	{
		$methodCode = $this->buildMethod();

		$refModel = new ReflectionClass($this->reverse ? $this->toModel : $this->model);
		
		$modelCode = $this->clearDefaultModelContent(
			file_get_contents($refModel->getFileName())
		);

		$this->writeMethodToFile($refModel->getFileName(), $modelCode, $methodCode);
	}

	/**
	 * build method
	 *
	 * @return string
	 */
	public function buildMethod()
	{
		$this->method = $this->generateMethodName();
		$stub = file_get_contents($this->getStub());
		$model = $this->reverse ? $this->toModel : $this->model;
		$modelName = $this->manager->getClassName($model);

		$stub = str_replace('DummyMethodName', $this->method, $stub);
		$stub = str_replace('DummyModel', strtolower($modelName), $stub);
		
		if (! is_null($this->toModel)) {
			$stub = str_replace('DummyToModel', $this->getRelationClassName(
				$this->model, $this->toModel
			), $stub);
		}

		return $this->applyOptions($this->beforeApplyOptions($stub));
	}

	/**
	 * get relation name class
	 *
	 * @param Model $model
	 * @param Model $toModel
	 * @return data type
	 */
	protected function getRelationClassName(Model $model, Model $toModel)
	{
		$refModel = new ReflectionClass($this->reverse ? $toModel : $model);
		$refToModel = new ReflectionClass($this->reverse ? $model : $toModel);

		if ($refModel->getNamespaceName() === $refToModel->getNamespaceName()) {
			return $refToModel->getShortName();
		}

		return '\\'.$refToModel->getName();
	}

	/**
	 * replace more before apply options code
	 *
	 * @param string $stub
	 * @return string
	 */
	protected function beforeApplyOptions($stub)
	{
		return $stub;
	}

	/**
	 * generate method relation name
	 *
	 * @param string $relation
	 * @return string
	 */
	protected function generateMethodName()
	{
		$model = $this->reverse ? $this->toModel : $this->model;
		$toModel = $this->reverse ? $this->model : $this->toModel;

		$className = $this->manager->getClassName($toModel);
		$methodName = Str::camel($this->getMethodName($className));

		if (! $this->manager->isMethodExists($model, $methodName)) {
			return $methodName;
		}

		throw new MethodAlreadyExists($methodName);
	}

	/**
	 * get method name form class
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getMethodName($name)
	{
		return $name;
	}

	/**
	 * apply options
	 *
	 * @param string $stub
	 * @return string
	 */
	protected function applyOptions($stub)
	{
		$replaced = false;

		foreach (array_reverse($this->defaultOptions) as $key => $option) {
			if (! $replaced) {
				$replace = '';
				$replaceWithComma = '';
			}

			if (in_array($key, $this->requiredOptions)) {
				if (! isset($this->options[$key])) {
					$value = $this->defaultOptions[$key];
				} else {
					$value = $this->options[$key];
				}

				$replace = "'$value'";
				$replaceWithComma = ", '$value'";
			} elseif (array_key_exists($key, $this->options)
				AND $this->options[$key] !== $this->defaultOptions[$key]) {

				$replaced = true;
				$replace = "'{$this->options[$key]}'";
				$replaceWithComma = ", '{$this->options[$key]}'";

			} elseif ($replaced) {
				$replace = "null";
				$replaceWithComma = ", null";
			}

			$stub = str_replace(", '{$key}'", $replaceWithComma, $stub);
			$stub = str_replace("'{$key}'", $replace, $stub);
		}

		return $stub;
	}

	/**
	 * clear default laravel model generator content
	 *
	 * @param string $modelCode
	 * @return string
	 */
	protected function clearDefaultModelContent($modelCode)
	{
		preg_match("[\s+\/\/]", $modelCode, $matches);

		if (count($matches) > 0) {
			$this->prefixNewline = "";
			$modelCode = preg_replace("[\s+\/\/]", '', $modelCode);
		}

		return $modelCode;
	}

	/**
	 * write method to file
	 *
	 * @param string $filePath
	 * @param string $modelCode
	 * @param string $methodCode
	 * @return void
	 */
	protected function writeMethodToFile($filePath, $modelCode, $methodCode)
	{
		$modelCode = $this->normalizeLineEndings($modelCode);
		$fileCode = $this->appendMethodToClass($modelCode, $methodCode);
		
		file_put_contents($filePath, $fileCode);
	}

	/**
	 * Normalizes all line endings in this string
	 *
	 * @param string $string
	 * @return string
	 */
	protected function normalizeLineEndings($string)
	{
		return preg_replace('/\R/u', PHP_EOL, $string);
	}

	/**
	 * append method to class
	 *
	 * @param string $modelCode
	 * @param string $modelCode
	 * @return string
	 */
	protected function appendMethodToClass($modelCode, $methodCode)
	{
		$pattern = "/(\})[^\}]*$/";
		$methodCode = $this->prefixNewline.$methodCode.PHP_EOL."}".PHP_EOL;

		return preg_replace($pattern, $methodCode, $modelCode);
	}

	/**
	 * get not connected db relation options
	 *
	 * @return void
	 */
	protected function setNotConnectedRelationOptions()
	{
		$this->command->warn('Can\'t connect to the database, plase confirm to follow instruction!');

		$rules = $this->getRelationOptionsRules();

		array_walk($rules, function(&$rule, $key) use ($rules) {
			$rule = ($key+1).'. '.$rule;
		});

		$rules[] = 'confirm that you will create the database schema as above!';
		$confirm = implode(PHP_EOL.' ', $rules);

		if (! $this->command->confirm($confirm, true)) {
			$this->command->warn('You are trying to use custome options to connect models!');
			$this->askToUseCustomeOptions();
		}
	}

	/**
	 * get model tables
	 *
	 * @return array
	 */
	protected function getTables()
	{
		return $this->db->getTables();
	}

	/**
	 * get model fileds
	 *
	 * @param string $table
	 * @return array
	 */
	protected function getFields($table)
	{
		$fileds = $this->db->getFields($table);

		return $fileds->pluck('name')->toArray();
	}

	/**
	 * set default options
	 *
	 * @param array $options
	 * @return void
	 */
	abstract protected function setDefaultOptions(array $options=[]);
	
	/**
	 * styling text
	 *
	 * @return void
	 */
	abstract protected function stylingText();

	/**
	 * get connected db relation options
	 *
	 * @return void
	 */
	abstract protected function setConnectedRelationOptions();

	/**
	 * ask to use custome options
	 *
	 * @return void
	 */
	abstract protected function askToUseCustomeOptions();

	/**
	 * get relation options rules
	 *
	 * @return array
	 */
	abstract protected function getRelationOptionsRules();

	/**
	 * get stub method file
	 *
	 * @return string
	 */
	abstract protected function getStub();
}
