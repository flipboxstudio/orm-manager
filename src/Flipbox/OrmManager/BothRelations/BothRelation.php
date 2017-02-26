<?php 

namespace Flipbox\OrmManager\BothRelations;

use Illuminate\Console\Command;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\DatabaseConnection;

abstract class BothRelation
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
     * options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new Both instance.
     *
     * @param Command $command
     * @param Model $model
     * @param Model $toModel
     * @return void
     */
    public function __construct(Command $command, ModelManager $manager, Model $model, Model $toModel)
    {
        $this->command = $command;
        $this->manager = $manager;
        $this->database = new DatabaseConnection;
        $this->model = $model;
        $this->toModel = $toModel;

        $this->preparation();

        if ($this->database->isConnected()) {
            $this->repositionModelByKeys();
        }
    }

    /**
     * preparation oprations
     *
     * @return void
     */
    protected function preparation() {}

    /**
     * reposition model by relations key
     *
     * @return void
     */
    protected function repositionModelByKeys()
    {
        if (! $this->isPositionModelValid()) {
            $this->exchangeModelPosition();
            
            if (! $this->isPositionModelValid()) {
                $this->askWhereForeignKeyTable();
            }
        }
    }

    /**
     * exchange position model
     *
     * @param  
     * @return void
     */
    protected function exchangeModelPosition()
    {
        $model = $this->model;
        $this->model = $this->toModel;
        $this->toModel = $model;
    }

    /**
     * check is position model is valid
     *
     * @return bool
     */
    protected function isPositionModelValid()
    {
        return true;
    }

    /**
     * ask which table where foreign key filed exists
     *
     * @return void
     */
    protected function askWhereForeignKeyTable() {}

    /**
     * get model fileds
     *
     * @param string $table
     * @return array
     */
    protected function getFields($table)
    {
        $fileds = $this->database->getTableFields($table);

        return $fileds->pluck('name')->toArray();
    }

    /**
     * build relations model to model
     *
     * @return void
     */
    abstract public function buildRelations();
}
