<?php 

namespace Flipbox\OrmManager\BothRelations;

use ReflectionClass;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

class MorphOneToOne extends Both
{
    /**
     * name
     *
     * @var string
     */
    protected $name;

    /**
     * to models
     *
     * @var array
     */
    protected $toModels = [];

    /**
     * preparation oprations
     *
     * @param  
     * @return void
     */
    protected function preparation()
    {
        $refModel = new ReflectionClass($this->model);
        $name = strtolower($refModel->getShortName().'able');
        $this->options['name'] = $this->name = $this->command->ask('What relation name do you use?', $name);

        $this->offerAddRelationModels();
    }

    /**
     * offer to add more models
     *
     * @return void
     */
    protected function offerAddRelationModels()
    {
        $models = $this->manager->getModels()->pluck('name');

        $refToModel = new ReflectionClass($this->toModel);
        $toModelName = $refToModel->getShortName();
        $toModelKey = array_search($toModelName, $models->toArray());
        
        $refModel = new ReflectionClass($this->model);
        $modelName = $refModel->getShortName();

        if ($this->command->confirm("Do you want to add more relations model that connecting with {$modelName}? no if you want to connect {$toModelName} only")) {
            $addModelKeys = $this->command->choice('You can add multiple model with sparated comma, choice models', $models->except($toModelKey)->toArray(), null, 1, true);

            foreach ($addModelKeys as $model) {
                $this->toModels[] = $this->manager->makeClass($model);
            }
        }
    }

    /**
     * check is position model is valid
     *
     * @return bool
     */
    protected function isPositionModelValid()
    {
        return $this->database->isFieldExists($this->model->getTable(), $this->name.'_type')
                AND $this->database->isFieldExists($this->model->getTable(), $this->name.'_id');
    }

    /**
     * ask which table where foreign key filed exists
     *
     * @return void
     */
    protected function askWhereForeignKeyTable()
    {
        if (! is_null($this->model)) {
            $tables[] = $this->model->getTable();
        }

        if (! is_null($this->toModel)) {
            $tables[] = $this->toModel->getTable();
        }

        $foreignTable = $this->command->choice("Which table that conatain {$this->name}_type and {$this->name}_id key?", $tables);

        $this->options['type'] = $this->command->choice("what {$this->name}_type field in {$foreignTable}?",
            $this->database->getTableFields($foreignTable)
        );

        $this->options['id'] = $this->command->choice("what {$this->name}_id field in {$foreignTable}?",
            $this->database->getTableFields($foreignTable)
        );

        $foreignModel = $this->manager->tableToModel($foreignTable);
        
        if ($foreignModel != $this->toModel) {
            $this->exchangeModelPosition();
        }
    }

    /**
     * build relations model to model
     *
     * @return void
     */
    public function buildRelations()
    {
        $this->command->buildMethod($this->model, 'morphTo', null, $this->options);

        $this->toModels[] = $this->toModel;

        foreach ($this->toModels as $model) {
            $this->command->buildMethod($model, 'morphOne', $this->model, $this->options);
        }
    }
}
