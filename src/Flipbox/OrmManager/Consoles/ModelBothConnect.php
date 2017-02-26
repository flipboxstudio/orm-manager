<?php 

namespace Flipbox\OrmManager\Consoles;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Config\Repository;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\Exceptions\ModelNotFound;
use Flipbox\OrmManager\Exceptions\RelationNotAvailable;

class ModelBothConnect extends ModelConnect
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'orm:both-connect {model?} {relation?} {to-model?}
                            {--i|interactive : Interactive question connect}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate connections method of both class model';
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            if ($this->option('interactive')) {
                extract($this->runInteractiveConnect());
            } else {
                extract($this->getArgumentConnect());
            }
            $this->buildRelations($model, $relation, $toModel);        
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    /**
     * get data input from arguments
     *
     * @return array
     */
    protected function getArgumentConnect()
    {
        if ($this->isRequiredArgFulfilled($this->arguments())) {
            $data['model'] = $this->getModel($this->argument('model'));
            $data['relation'] = $this->getRelation($this->argument('relation'));
            $data['toModel'] = $this->getModel($this->argument('to-model'));
    
            return $data;
        }

        throw new Exception("There are required argument not exists");
    }

    /**
     * check is fulfilled required arguments
     *
     * @param array $arguments
     * @return bool
     */
    protected function isRequiredArgFulfilled($arguments)
    {
        if (! $this->option('interactive')) {
            return ! is_null($arguments['model'])
                AND ! is_null($arguments['relation'])
                AND ! is_null($arguments['to-model']);
        }

        return true;
    }

    /**
     * run interactive connect
     *
     * @return void
     */
    protected function runInteractiveConnect()
    {
        $models = $this->manager->getModels()->pluck('name')->toArray();

        $search = array_search($this->argument('model'), $models);
        $default = $search === false ? null : $search;
        $askModel = $this->choice('Which model would you like to connect?', $models, $default);
        $data['model'] = $this->getModel($askModel);

        $search = array_search($this->argument('relation'), $this->manager->both_relations);
        $default = $search === false ? null : $search;
        $data['relation'] = $this->choice('Which relation between two models?', $this->manager->both_relations, $default);

        $search = array_search($this->argument('model'), $models);
        $default = $search === false ? null : $search;
        $askToModel = $this->choice('Which model that you want to connect with '.$askModel, $models, $default);
        $data['toModel'] = $this->getModel($askToModel);

        return $data;
    }
    
    /**
     * get relation
     *
     * @param string $relation
     * @return string
     */
    protected function getRelation($relation)
    {
        if ($this->manager->isRelationAvailable($relation, true)) {
            return $relation;
        }
        
        throw new RelationNotAvailable("Relation {$relation} doesn't available");
    }

    /**
     * build relation methods both class
     *
     * @param Model $model
     * @param string $relation
     * @param mix Model $toModel
     * @return void
     */
    protected function buildRelations(Model $model, $relation, Model $toModel)
    {
        try {
            $bothRelation = $this->newBothRelationInstance($relation, $model, $toModel);

            $bothRelation->buildRelations();
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * create new model instance by relation
     *
     * @param string $relation
     * @param Model $model
     * @param mix Model|null $toModel
     * @return Model
     */
    protected function newBothRelationInstance($relation, Model $model, $toModel)
    {
        $class = 'Flipbox\OrmManager\BothRelations\\'.Str::studly($relation);

        return new $class($this, $this->manager, $model, $toModel);
    }
}
