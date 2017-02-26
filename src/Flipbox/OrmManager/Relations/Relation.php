<?php 

namespace Flipbox\OrmManager\Relations;

use Exception;
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
	protected $database;

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
	 * options that will be check
	 *
	 * @var array
	 */
	protected $checkingOptions = [];

	/**
	 * new line
	 *
	 * @var string
	 */
	protected $newline = "\n";

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
	 * Create a new Model instance.
	 *
	 * @param Command $command
	 * @param ModelManager $manager
	 * @param Model $model
	 * @param Model $toModel
	 * @param array $options
	 * @return void
	 */
	public function __construct(Command $command,
								ModelManager $manager,
								Model $model,
								Model $toModel=null,
								array $options=[])
	{
		$this->command = $command;
		$this->manager = $manager;
		$this->db = $this->manager->db;
		$this->model = $this->reverse ? $toModel : $model;
		$this->toModel = $this->reverse ? $model : $toModel;

		$this->showCaptionProcess($model, $toModel);
		$this->setDefaultOptions($options);
		$this->stylingText();
		$this->setRelationOptions();
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
		$toModelName = '';
		$refModel = new ReflectionClass($model);
		$relationModel = new ReflectionClass($this);

		if (! is_null($toModel)) {
			$refToModel = new ReflectionClass($toModel);
			$toModelName = $refToModel ? $refToModel->getShortName() : '';
		}

		$caption = " >>> Creating relation {$refModel->getShortName()} {$relationModel->getShortName()} {$toModelName} : ";

		$this->command->title($caption, 'white', 'blue');
	}

	/**
	 * set relation option required
	 *
	 * @return void
	 */
	protected function setRelationOptions()
	{
		if ($this->db->isConnected()) {
			if (! $this->db->isTableExists($this->model->getTable())) {
				throw new TableNotExists($this->model->getTable(), $refModel->getShortName());
			}

			if (! is_null($this->toModel) AND ! $this->db->isTableExists($this->toModel->getTable())) {
				throw new TableNotExists($this->toModel->getTable(), $refToModel->getShortName());
			}

			$this->setConnectedRelationOptions();
		} else {
			$this->setNotConnectedRelationOptions();
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
		$methodName = $this->generateMethodName();
		$stubFile = $this->getStub();
		$stub = file_get_contents($stubFile);

		$refModel = new ReflectionClass($this->reverse ? $this->toModel : $this->model);

		$stub = str_replace('DummyMethodName', $methodName, $stub);
		$stub = str_replace('DummyModel', strtolower($refModel->getShortName()), $stub);
		
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
		$refToModel = new ReflectionClass($this->reverse ? $this->model : $this->toModel);
		$name = $refToModel->getShortName();

		$methodName = Str::camel($this->getMethodName($name));

		if ($this->manager->isMethodExists($this->reverse ? $this->toModel : $this->model, $methodName)) {
			throw new MethodAlreadyExists($methodName);
		}

		return $methodName;
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
		$this->mergeUnchechingOptions();

		$replaced = false;

		foreach (array_reverse($this->defaultOptions) as $key => $option) {
			if (! $replaced) {
				$replaceWithComma = '';
				$replaceWithSingle = '';
			}

			if (in_array($key, $this->requiredOptions)) {
				if (! isset($this->options[$key])) {
					$value = $this->defaultOptions[$key];
				} else {
					$value = $this->options[$key];
				}

				$replaceWithComma = ", '$value'";
				$replaceWithSingle = "'$value'";
			} elseif (array_key_exists($key, $this->options)
				AND $this->options[$key] !== $this->defaultOptions[$key]) {

				$replaceWithComma = ", '{$this->options[$key]}'";
				$replaceWithSingle = "'{$this->options[$key]}'";
				$replaced = true;

			} elseif ($replaced) {
				$replaceWithComma = ", null";
				$replaceWithSingle = "null";
			}

			$stub = str_replace(", '{$key}'", $replaceWithComma, $stub);
			$stub = str_replace("'{$key}'", $replaceWithSingle, $stub);
		}

		return $stub;
	}

	/**
	 * merger unchecking options to options
	 *
	 * @return void
	 */
	protected function mergeUnchechingOptions()
	{
		foreach ($this->checkingOptions as $key => $option) {
			if ($this->checkingOptions[$key] !== $this->defaultOptions[$key]) {
				$this->options[$key] = $option;
			}
		}
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
			$this->newline = "";
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
		file_put_contents (
			$filePath,
			str_replace("\n}\n", $this->newline."\n".$methodCode."\n}\n", $modelCode)
		);
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
		$confirm = implode("\n ", $rules);

		if (! $this->command->confirm($confirm, true)) {
			$this->command->warn('You are trying to use custome options to connect models!');
			$this->askToUseCustomeOptions();
		}
	}

	/**
	 * get model fileds
	 *
	 * @param string $table
	 * @return array
	 */
	protected function getFields($table)
	{
		$fileds = $this->db->getTableFields($table);

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
