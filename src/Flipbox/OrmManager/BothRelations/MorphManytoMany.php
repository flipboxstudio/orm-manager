<?php 

namespace Flipbox\OrmManager\BothRelations;

use ReflectionClass;
use Illuminate\Support\Str;

class MorphManytoMany extends Both
{
    /**
     * name
     *
     * @var string
     */
    protected $name;

    /**
     * table
     *
     * @var string
     */
    protected $table;

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
        $this->options['table'] = $this->table = Str::plural($this->name);
        $this->options['foreign_key'] = $this->name.'_id';
        $this->options['related_key'] = strtolower($refModel->getShortName()).'_id';

        $this->offerAddRelationModels();
    }
    
    /**
     * reposition model by relations key
     *
     * @return void
     */
    protected function repositionModelByKeys()
    {
        if (! $this->isPositionModelValid()) {
            $this->askWhereForeignKeyTable();
        }
    }

    /**
     * check is position model is valid
     *
     * @return bool
     */
    protected function isPositionModelValid()
    {
        return $this->database->isTableExists($this->table)
                AND $this->database->isFieldExists($this->table, $this->options['foreign_key'])
                AND $this->database->isFieldExists($this->table, $this->options['related_key']);
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
     * ask which table where foreign key filed exists
     *
     * @return void
     */
    protected function askWhereForeignKeyTable()
    {
        $this->options['table'] = $this->table = $this->command->choice(
            "Can't find table {$this->table}, what are you using?",
            $this->database->getTables()
        );

        if (! $this->database->isFieldExists($this->table, $foreignKey = $this->options['foreign_key'])) {
            $this->options['foreign_key'] = $this->command->choice(
                "Can't find field {$foreignKey} in the table {$this->table} as foreign key of table {$this->model->getTable()}, choice one!",
                $this->database->getTableFields($this->table)
            );
        }

        if (! $this->database->isFieldExists($this->table, $relatedKey = $this->options['related_key'])) {
            $this->options['related_key'] = $this->command->choice(
                "Can't find field {$relatedKey} in the table {$this->table} as related key of table {$this->model->getTable()}, choice one!",
                $this->database->getTableFields($this->table)
            );
        }
    }

    /**
     * build relations model to model
     *
     * @return void
     */
    public function buildRelations()
    {
        $this->toModels[] = $this->toModel;

        foreach ($this->toModels as $model) {
            $this->command->buildMethod($model, 'morphToMany', $this->model, $this->options);
            $this->command->buildMethod($this->model, 'morphedByMany', $model, $this->options);
        }
    }
}
