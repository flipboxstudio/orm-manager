<?php 

namespace Flipbox\OrmManager;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\Exceptions\ModelNotFound;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ModelManager
{
	/**
	 * base path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * config
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * database connection
	 *
	 * @var DatabaseConnection
	 */
	public $database;

	/**
	 * models
	 *
	 * @var array
	 */
	protected $models;

	/**
	 * relations
	 *
	 * @var array
	 */
	public $relations = [
		'hasOne', 'hasMany', 'belongsTo',
		'belongsToMany', 'hasManyThrough',
		'morphTo', 'morphOne', 'morphMany',
		'morphToMany', 'morphedByMany' 
	];

	/**
	 * relations
	 *
	 * @var array
	 */
	public $both_relations = [
		'oneToOne', 'oneToMany', 'manyToMany',
		'morphOneToOne', 'morphOneToMany', 'morphManyToMany'
	];

	/**
	 * Create a new ModelManager instance.
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
		$this->database = new DatabaseConnection;
		$this->path = $this->config['basepath'];

		$this->models = $this->scandModels();
	}

	/**
	 * get model list
	 *
	 * @return Collection
	 */
	public function getModels()
	{
		return new Collection($this->models);
	}

	/**
	 * scand models from path
	 *
	 * @param  string $path
	 * @return array
	 */
	protected function scandModels($path=null)
	{
		try {
			$dirs = scandir($path = $path ?: $this->path);
		} catch (Exception $e) {
			return [];
		}

		$models = [];

		foreach($this->filterDirectory($dirs) as $file) {
			$info = pathinfo($filepath = $path.'/'.$file);

			if (is_dir($filepath)) {
				$models = array_merge($models, $this->scandModels($filepath));
			}

			if (! $this->isValidFileModel($info)) {
				continue;
			}

			if ($this->isValidModel($class = $this->makeClassFileInfo($info))) {
				$models[] = $this->petchModel($class);
			}
		}

		return $models;
	}

	/**
	 * filter directory
	 *
	 * @param array $dirs
	 * @return array
	 */
	protected function filterDirectory($dirs)
	{
		foreach ($dirs as $key => $dir) {
			if (in_array($dir, ['.', '..'])
				OR in_array($dir, $this->config['exclude_dir'])) {

				unset($dirs[$key]);
			}
		}

		return $dirs;
	}

	/**
	 * patch model
	 *
	 * @param Model $class
	 * @return array
	 */
	protected function petchModel(Model $class)
	{
		$refClass = new ReflectionClass($class);

		return [
			'namespace' => $refClass->getNamespaceName(),
			'name' => $refClass->getShortName(),
			'table' => $this->getTable($class),
			'primary_key' => $this->getPrimaryKey($class),
			'relation_count' => $this->getRelations($class)->count(),
			'mutator_count' => $this->getMutators($class)->count(),
			'accessor_count' => $this->getAccessors($class)->count(),
			'scope_count' => $this->getScopes($class)->count(),
			'soft_deletes' => $this->isUseSoftDeletes($class)
		];
	}

	/**
	 * get table of model
	 *
	 * @param Model $model
	 * @return string
	 */
	protected function getTable(Model $model)
	{
		return $model->getTable();
	}

	/**
	 * get table of model
	 *
	 * @param Model $model
	 * @return string
	 */
	protected function getPrimaryKey(Model $model)
	{
		return $model->getKeyName();
	}

	/**
	 * check path is file of model
	 *
	 * @param string $fileInfo
	 * @return boolean
	 */
	public function isValidFileModel(array $fileInfo)
	{
		extract($fileInfo);

		return is_file($filepath = $dirname.'/'.$basename)
			AND ! empty($filename)
			AND (in_array($filename, (new FileGetContent($filepath))->getClasses()));
	}

	/**
	 * check is file model
	 *
	 * @param object $model
	 * @return boolean
	 */
	public function isValidModel($model)
	{
		return is_subclass_of($model, Model::class);
	}

	/**
	 * table to model
	 *
	 * @param string $table
	 * @return Model
	 */
	public function tableToModel($table)
	{
		if ($model = $this->getModels()->where('table', $table)->first()) {
			return $this->makeClass($model['name']);
		}

		throw new ModelNotFound($table);		
	}

	/**
	 * check is model exists
	 *
	 * @param string $className
	 * @return boolean
	 */
	public function isModelExists($className)
	{
		return $this->getModels()->where('name', $className)->count() > 0;
	}

	/**
	 * check is method exists
	 *
	 * @param Model $class
	 * @param string $method
	 * @return boolean
	 */
	public function isMethodExists(Model $class, $method)
	{
		return method_exists($class, $method);
	}

	/**
	 * instantiate class model by name
	 *
	 * @param string $fileInfo
	 * @return string
	 */
	protected function makeClassFileInfo(array $fileInfo)
	{
		extract($fileInfo);

		if (file_exists($filepath = $dirname.'/'.$basename)) {
			$namespace = (new FileGetContent($filepath))->getNamespace();

			$refClass = new ReflectionClass($namespace.'\\'.$filename);

			return $refClass->newInstanceWithoutConstructor();
		}

		throw new ModelNotFound($filename);
	}

	/**
	 * instantiate class model by name
	 *
	 * @param string $className
	 * @return string
	 */
	public function makeClass($className)
	{
		if ($this->isModelExists($className)) {
			$model = $this->getModels()->where('name', $className)->first();
			$class = $model['namespace'].'\\'.$className;
			$refClass = new ReflectionClass($class);

			return $refClass->newInstanceWithoutConstructor();
		}

		throw new ModelNotFound($filename);

	}

	/**
	 * check is relations available
	 *
	 * @param string $relation
	 * @return boolean
	 */
	public function isRelationAvailable($relation, $both=false)
	{
		return $both
				? in_array($relation, $this->both_relations)
				: in_array($relation, $this->relations);
	}

	/**
	 * get class method of model
	 *
	 * @param Model $model
	 * @return array
	 */
	protected function getClassMethods(Model $model)
	{
		$class = new ReflectionClass($model);
		$methods = [];

		foreach ($class->getMethods() as $method) {
			if ($method->class === $class->getName()) {
				$methods[] = $method->name;
			}
		}

		return $methods;
	}

	/**
	 * get relations of models
	 *
	 * @param Model $model
	 * @return Collection
	 */
	public function getRelations(Model $model)
	{
		$methods = $this->filterRelationMethods($model, $this->getClassMethods($model));

		return new Collection($methods);
	}

	/**
	 * filter model method
	 *
	 * @param Model $model
	 * @param array $methods
	 * @param bool $convertToObject
	 * @return array
	 */
	protected function filterRelationMethods(Model $model, array $methods, $convertToObject=true)
	{
		$filtered = [];
		
		foreach ($methods as $method) {
			try {
				$relationMethod = new ReflectionMethod($model, $method);

				if (count($params = $relationMethod->getParameters()) > 0) {
					foreach ($params as $param) {
						if (! $param->isDefaultValueAvailable()) continue 2;
					}
				}

				$relationClass = $model->$method();

				if ($relationClass instanceof Relation) {
					$filtered[] = $convertToObject
									? $relationClass
									: $relationMethod;
				}
			} catch (Exception $e) {
				//
			}
		}

		return $filtered;
	}

	/**
	 * get mutators of models
	 *
	 * @param Model $model
	 * @return Collection
	 */
	public function getMutators(Model $model)
	{
		$methods = $this->filterMethods($model, $this->getClassMethods($model));

		return new Collection($methods);
	}

	/**
	 * get mutators of models
	 *
	 * @param Model $model
	 * @return Collection
	 */
	public function getAccessors(Model $model)
	{
		$methods = $this->filterMethods($model, $this->getClassMethods($model), 'get');

		return new Collection($methods);
	}

	/**
	 * get scopes of models
	 *
	 * @param Model $model
	 * @return Collection
	 */
	public function getScopes(Model $model)
	{
		$methods = $this->filterMethods($model, $this->getClassMethods($model), 'scope', null);

		return new Collection($methods);
	}

	/**
	 * filter model method
	 *
	 * @param Model $model
	 * @param array $methods
	 * @return array
	 */
	protected function filterMethods(Model $model, array $methods, $prefix='set', $suffix='Attribute')
	{
		$filtered = [];
		
		foreach ($methods as $method) {
			$search = $suffix ? "(.*?)" : "(.*)";

			if (preg_match("/{$prefix}{$search}{$suffix}/", $method)) {
				$filtered[] = new ReflectionMethod($model, $method);
			}
		}

		return $filtered;
	}

	/**
	 * check is model use soft deletes
	 *
	 * @param Model $model
	 * @return bool
	 */
	public function isUseSoftDeletes(Model $model)
	{
		return property_exists($model, 'forceDeleting');
	}
}
