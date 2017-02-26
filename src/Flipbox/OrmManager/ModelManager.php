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
     * models
     *
     * @var array
     */
    protected $models;

    /**
     * database
     *
     * @var DatabaseConnection
     */
    public $db;

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
     * both relations
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
    public function __construct(array $config, DatabaseConnection $db)
    {
        $this->db = $db;
        $this->config = $config;
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
            if (is_dir($filepath = $path.'/'.$file)) {
                $models = array_merge($models, $this->scandModels($filepath));
            }

            if (! $this->isValidFileModel($filepath)) {
                continue;
            }

            if ($this->isValidModel($class = $this->makeClassFromFile($filepath))) {
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
    protected function filterDirectory(array $dirs)
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
            'table' => $class->getTable(),
            'primary_key' => $class->getKeyName(),
            'relation_count' => $this->getRelations($class)->count(),
            'mutator_count' => $this->getMutators($class)->count(),
            'accessor_count' => $this->getAccessors($class)->count(),
            'scope_count' => $this->getScopes($class)->count(),
            'soft_deletes' => $this->isUseSoftDeletes($class)
        ];
    }

    /**
     * check path is file of model
     *
     * @param string $filepath
     * @return boolean
     */
    public function isValidFileModel($filepath)
    {
        if (file_exists($filepath) AND is_file($filepath)) {
            extract(pathinfo($filepath));

            return ! empty($filename)
                AND in_array($filename, (new FileGetContent($filepath))->getClasses());

        }

        return false;
    }

    /**
     * check is file model
     *
     * @param object $model
     * @return boolean
     */
    public function isValidModel($model)
    {
        if (! is_object($model)) {
            return false;
        }

        $refModel = new ReflectionClass($model);

        if ($refModel->isAbstract()) {
            return false;
        }

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
     * instantiate class model by class file
     *
     * @param string $filepath
     * @return Object
     */
    protected function makeClassFromFile($filepath)
    {
        if (file_exists($filepath)) {

            extract(pathinfo($filepath));

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
     * @return Object
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
    public function getMethods(Model $model)
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
        $methods = $this->filterRelationMethods($model, $this->getMethods($model));

        return new Collection($methods);
    }

    /**
     * filter relations model method
     *
     * @param Model $model
     * @param array $methods
     * @return array
     */
    protected function filterRelationMethods(Model $model, array $methods)
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
                    $filtered[] = $relationClass;
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
        $methods = $this->filterMethods($model, $this->getMethods($model));

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
        $methods = $this->filterMethods($model, $this->getMethods($model), 'get');

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
        $methods = $this->filterMethods($model, $this->getMethods($model), 'scope', null);

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
