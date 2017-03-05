<?php

namespace Flipbox\OrmManager;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Arrayable;
use Flipbox\OrmManager\Exceptions\ModelNotFound;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ModelManager implements Arrayable
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
     * @var Repository
     */
    protected $config;

    /**
     * models
     *
     * @var array
     */
    protected $models = [];

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
     * @param Repository $config
     * @param DatabaseConnection $config
     * @return void
     */
    public function __construct(Repository $config, DatabaseConnection $db)
    {
        $this->db = $db;
        $this->config = $config;
        $this->path = base_path($this->config->get('orm.basepath', 'app'));

        $this->scandModels();
    }

    /**
     * scand models from path
     *
     * @param  string $path
     * @return void
     */
    protected function scandModels($path=null)
    {
        try {
            $dirs = scandir($path = $path ?: $this->path);

            foreach($this->filterDirectory($dirs) as $file) {
                if (is_dir($filepath = $path.'/'.$file)) {
                    $this->scandModels($filepath);
                }

                if ($model = $this->makeModelFromFile($filepath)) {
                    $this->models[$this->getClassName($model)] = $model;
                }
            }
        } catch (Exception $e) {
            //
        }
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
                OR in_array($dir, $this->config->get('orm.exclude_dir', []))) {

                unset($dirs[$key]);
            }
        }

        return $dirs;
    }

    /**
     * make model form filepath
     *
     * @param string $filepath
     * @return Model
     */
    protected function makeModelFromFile($filepath)
    {
        if (! file_exists($filepath) OR ! is_file($filepath)) {
            return null;
        }
        
        extract(pathinfo($filepath));

        $fileClasses = (new FileGetContent($filepath))->getClasses();

        if (empty($filename) OR ! in_array($filename, $fileClasses)) {
            return null;
        }

        $class = $this->newInstanceClassFromFile($filepath);

        if (! is_null($class) AND is_subclass_of($class, Model::class)) {
            return $class;
        }
    }

    /**
     * instantiate class model by class file
     *
     * @param string $filepath
     * @return Object
     */
    protected function newInstanceClassFromFile($filepath)
    {
        if (file_exists($filepath)) {

            extract(pathinfo($filepath));

            $namespace = (new FileGetContent($filepath))->getNamespace();
            $refClass = new ReflectionClass($namespace.'\\'.$filename);

            if ($refClass->isAbstract()
                OR $refClass->isInterface()) {
                return null;
            }

            return $refClass->newInstanceWithoutConstructor();
        }

        throw new ModelNotFound($filename);
    }
    
    /**
     * get class name
     *
     * @param Object $class
     * @return string
     */
    public function getClassName($class)
    {
        if (is_object($class)) {
            $refClass = new ReflectionClass($class);
            return $refClass->getShortName();
        }

        return '';
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
     * get model by names
     *
     * @param string $name
     * @return Model
     */
    public function getModel($name)
    {
        if (isset($this->models[$name])) {
            return $this->models[$name];
        }
    }

    /**
     * get model summary
     *
     * @param string $name
     * @return array
     */
    public function getModelSummary($name)
    {
        $model = $this->getModel($name);

        return $this->petchModel($model);
    }

    /**
     * check is model exists
     *
     * @param string $name
     * @return bool
     */
    public function isModelExists($name)
    {
        return isset($this->models[$name]);
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
     * check is model has method
     *
     * @param Model $class
     * @param string $method
     * @return bool
     */
    public function isMethodExists(Model $class, $method)
    {
        return method_exists($class, $method);
    }

    /**
     * check is relations available
     *
     * @param string $relation
     * @return bool
     */
    public function isRelationAvailable($relation, $both=false)
    {
        return $both
                ? in_array($relation, $this->both_relations)
                : in_array($relation, $this->relations);
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

    /**
     * table to model
     *
     * @param string $table
     * @return Model
     */
    public function tableToModel($table)
    {
        foreach ($this->models as $model) {
            if ($model->getTable() === $table) {
                return $model;
            }
        }

        throw new ModelNotFound($table);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [];

        foreach ($this->models as $name => $model) {
            $data[] = $this->petchModel($model);
        }

        return $data;
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
}
