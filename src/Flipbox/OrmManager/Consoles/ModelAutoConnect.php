<?php 

namespace Flipbox\OrmManager\Consoles;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Config\Repository;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\DatabaseConnection;
use Flipbox\OrmManager\Exceptions\ModelNotFound;
use Flipbox\OrmManager\Exceptions\TableNotExists;

class ModelAutoConnect extends ModelBothConnect
{

    /**
     * model manager
     *
     * @var ModelManager
     */
    protected $manager;

    /**
     * database
     *
     * @var ModelManager
     */
    protected $db;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'orm:auto-connect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto detect primary or foreign or related key then generate connections method of class model';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->db->isConnected() OR count($tables = $this->db->getTables()) === 0) {
            return $this->error('Auto connect only if you has been created your database and connected.');
        }

        try {
            $this->checkTables();
            $this->searchOnModels();
            $this->searchOnTables();
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * check tables
     *
     * @return void
     */
    protected function checkTables()
    {
        foreach ($this->manager->getModels() as $model) {
            if (! $this->db->isTableExists($table = $model->getTable())) {
                throw new TableNotExists($table, $this->manager->getClassName($model));
            }
        }
    }

    /**
     * search relations on the models
     *
     * @return void
     */
    protected function searchOnModels()
    {
        foreach ($this->manager->getModels() as $model) {
            foreach ($this->manager->getModels() as $toModel) {
                if ($this->db->isFieldExists($toModel->getTable(),
                    $model->getForeignKey())) {
                    $this->buildOneToOneOrMany($model, $toModel);
                    
                    foreach ($this->manager->getModels() as $intermediateModel) {
                        if ($this->db->isFieldExists($intermediateModel->getTable(),
                            $toModel->getForeignKey())) {
                            $this->buildhasManyThrough($model, $toModel, $intermediateModel);
                        }
                    }
                }

                $pivotTable = implode('_', array_map('strtolower', [
                    $this->manager->getClassName($model),
                    $this->manager->getClassName($toModel)
                ]));

                if ($this->db->isTableExists($pivotTable)
                    AND $this->db->isFieldExists($pivotTable, $model->getForeignKey())
                    AND $this->db->isFieldExists($pivotTable, $toModel->getForeignKey())) {
                    $this->buildManyToMany($model, $toModel);
                }
            }
        }
    }

    /**
     * search on the tables
     *
     * @return void
     */
    protected function searchOnTables()
    {
        $tables = $this->db->getTables();
        unset($tables['migrations']);

        foreach ($tables as $name => $table) {
            $type = false;
            foreach ($table as $filed) {
                if (Str::contains($filed['name'], '_type')) $type = $filed['name'];
            }

            $id = false;
            foreach ($table as $filed) {
                if (Str::contains($filed['name'], '_id')) $id = $filed['name'];
            }

            if ($type AND $id) {
                $type = str_replace('_type', '', $type);
                $id = str_replace('_id', '', $id);
            }

            if ($type !== false AND $id !== false) {
                try {
                    $model = $this->manager->tableToModel($name);
                    $this->buildMorphOneToOneOrMany($model, $type);
                } catch (ModelNotFound $e) {
                    foreach ($this->manager->getModels() as $model) {
                        if ($this->db->isFieldExists($name, $model->getForeignKey())) {
                            $this->buildMorphManyToMany($model, $type);
                        }
                    }
                } catch (Exception $e) {
                    throw $e;
                }
            }
        }
    }

    /**
     * build relation one to one or many
     *
     * @param Model $model
     * @param Model $toModel
     * @return void
     */
    protected function buildOneToOneOrMany(Model $model, Model $toModel)
    {
        $modelName = $this->manager->getClassName($model);
        $toModelName = $this->manager->getClassName($toModel);
        
        $this->title(" >>> Relation {$modelName} and {$toModelName} are detected. ", 'light_gray', 'red');
        
        $bothRelation = $this->choice("What relation between {$modelName} and {$toModelName}?", ['oneToOne', 'oneToMany']);
        
        return $this->buildRelations($model, $bothRelation, $toModel);
    }

    /**
     * build relation has many through
     *
     * @param Model $model
     * @param Model $toModel
     * @param Model $intermediateModel
     * @return void
     */
    protected function buildhasManyThrough(Model $model, Model $intermediateModel, Model $toModel)
    {
        $modelName = $this->manager->getClassName($model);
        $intermediateModelName = $this->manager->getClassName($intermediateModel);
        $toModelName = $this->manager->getClassName($toModel);
        
        $this->title(" >>> Relation {$modelName} has many {$toModelName} through {$intermediateModelName} are detected. ", 'light_gray', 'red');
        
        return $this->buildMethod($model, 'hasManyThrough', $toModel, ['intermediate_model'=>$intermediateModel]);
    }

    /**
     * build relation many to many
     *
     * @param Model $model
     * @param Model $toModel
     * @return void
     */
    protected function buildManyToMany(Model $model, Model $toModel)
    {
        $modelName = $this->manager->getClassName($model);
        $toModelName = $this->manager->getClassName($toModel);
        
        $this->title(" >>> Relation many to many between {$modelName} and {$toModelName} are detected. ", 'light_gray', 'red');

        return $this->buildRelations($model, 'manyToMany', $toModel);
    }

    /**
     * build relation morph one to one or many
     *
     * @param Model $model
     * @param string $name
     * @return void
     */
    protected function buildMorphOneToOneOrMany(Model $model, $name)
    {
        $modelName = $this->manager->getClassName($model);
        
        $this->title(" >>> Polymorphic Relations {$modelName} are detected. ", 'light_gray', 'red');

        $bothRelation = $this->choice("{$modelName} morph relation will be?", ['morphOneToOne', 'morphOneToMany']);
        $toModels = $this->choice("choice multiple models (comma sparated) that will be morph connected with {$modelName}!", $this->manager->getModels()->keys()->toArray(), null, null, true);
        $toModels = array_map([$this->manager, 'getModel'], $toModels);

        return $this->buildRelations($model, $bothRelation, $toModels, ['name'=>$name]);
    }

    /**
     * build relation morph many to many
     *
     * @param Model $model
     * @param string $name
     * @return void
     */
    protected function buildMorphManyToMany(Model $model, $name)
    {
        $modelName = $this->manager->getClassName($model);
        
        $this->title(" >>> Polymorphic Relations {$name} of model {$modelName} are detected. ", 'light_gray', 'red');

        $toModels = $this->choice("choice multiple models (comma sparated) that will be morph many to many with {$modelName}!", $this->manager->getModels()->keys()->toArray(), null, null, true);
        $toModels = array_map([$this->manager, 'getModel'], $toModels);

        return $this->buildRelations($model, 'morphManyToMany', $toModels, ['name'=>$name]);
    }
}
