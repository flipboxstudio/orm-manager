<?php 

namespace Flipbox\OrmManager;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\Exceptions\ModelNotFound;
use Flipbox\OrmManager\Exceptions\FolderNotFound;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ModelManager
{
	use FontColor;

	/**
	 * database connection
	 *
	 * @var DatabaseConnection
	 */
	public $database;

	/**
	 * base namespace
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * base path
	 *
	 * @var string
	 */
	protected $path;

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
		$this->database = new DatabaseConnection;
		$this->namespace = $config['namespace'];
		$this->path = $config['basepath'];
	}

	/**
	 * get model list
	 *
	 * @param boolean $checkTable
	 * @return Collection
	 */
	public function getModels($checkTable = false)
	{
		try {
			$dirs = scandir($this->path);
		} catch (Exception $e) {
			throw new FolderNotFound('Directory not found '.$this->path);
		}

		$files = [];

		foreach($dirs as $file) {
			$info = pathinfo($filepath = $this->path.'/'.$file);

			if (! is_file($filepath) OR empty($info['filename'])) {
				continue;
			}

			if (! in_array($info['filename'], $this->getClassesFromFile($filepath))) {
				continue;
			}

			if ($this->isModel($class = $this->makeClass($info['filename']))) {
				$refClass = new ReflectionClass($class);

				$files[] = [
					'name' => $refClass->getShortName(),
					'table' => $this->getTable($class, $checkTable),
					'primary_key' => $this->getPrimaryKey($class, $checkTable),
					'relation_count' => $this->getRelationCount($class),
					'mutator_count' => $this->getMutators($class)->count(),
					'accessor_count' => $this->getAccessors($class)->count(),
					'scope_count' => $this->getScopes($class)->count(),
					'soft_deletes' => $this->isUseSoftDeletes($class)
										? $this->paintString('Yes', 'green')
										: $this->paintString('No', 'red')
				];
			}
		}

		return new Collection($files);
	}
	/**
	 * get classes of file
	 *
	 * @param string $filepath
	 * @return array
	 */
	protected function getClassesFromFile($filepath)
	{
		$phpCode = file_get_contents($filepath);

		$classes = $this->getPhpClasses($phpCode);

		return $classes;
	}

	/**
	 * get classes of file
	 *
	 * @param string $filepath
	 * @return array
	 */
	protected function getPhpClasses($phpCode)
	{
		$classes = [];
		$tokens = token_get_all($phpCode);
		$count = count($tokens);

		for ($i = 2; $i < $count; $i++) {
			if ($tokens[$i - 2][0] == T_CLASS
			    && $tokens[$i - 1][0] == T_WHITESPACE
			    && $tokens[$i][0] == T_STRING) {
			    $className = $tokens[$i][1];
			    $classes[] = $className;
			}
		}

		return $classes;
	}
	/**
	 * get table of model
	 *
	 * @param Model $model
	 * @param boolean $paint
	 * @return string
	 */
	protected function getTable(Model $model, $paint = false)
	{
		$table = $model->getTable();

		if ($paint) {
			if ($this->database->isConnected()) {
				if ($this->database->isTableExists($table)) {
					return $this->paintString($table, 'green');
				}
	
				return $this->paintString("{$table} (not exists)", 'white', 'red');

			}

			return $this->paintString($table, 'red');
		}

		return $table;
	}

	/**
	 * get table of model
	 *
	 * @param Model $model
	 * @param boolean $paint
	 * @return string
	 */
	protected function getPrimaryKey(Model $model, $paint = false)
	{
		$table = $model->getTable();
		$primaryKey = $model->getKeyName();

		if ($paint) {
			if ($this->database->isConnected()){
				if ($this->database->isTableExists($table)
					AND $this->database->isFieldExists($table, $primaryKey)) {
					return $this->paintString($primaryKey, 'green');
				}

				return $this->paintString("{$primaryKey} (not exists)", 'white', 'red');
			}

			return $this->paintString($primaryKey, 'red');
		}

		return $primaryKey;
	}

	/**
	 * check is file model
	 *
	 * @param object $model
	 * @return boolean
	 */
	public function isModel($model)
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
		$model = $this->getModels()->where('table', $table)->first();

		return $this->makeClass($model['name']);
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
	 * @param string $className
	 * @return string
	 */
	public function makeClass($className)
	{
		$class = $this->namespace.'\\'.$className;

		$refClass = new ReflectionClass($class);

		return $refClass->newInstanceWithoutConstructor();
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
	 * get relation count
	 *
	 * @param Model $model
	 * @return int
	 */
	public function getRelationCount(Model $model)
	{
		return $this->getRelations($model)->count();
	}

	/**
	 * filter model method
	 *
	 * @param Model $model
	 * @param array $methods
	 * @param bool $convertToObject
	 * @return array
	 */
	protected function filterRelationMethods(Model $model, array $methods, $convertToObject = true)
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
