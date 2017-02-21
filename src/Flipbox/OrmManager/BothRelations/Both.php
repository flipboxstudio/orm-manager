<?php 

namespace Flipbox\OrmManager\BothRelations;

use Illuminate\Console\Command;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;

abstract class Both
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
        $this->database = $manager->database;
        $this->model = $model;
        $this->toModel = $toModel;

        $this->preparation();

        if ($this->database->isConnected()) {
            $this->repositionModelByKeys();
        }
    }
    
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
     * preparation oprations
     *
     * @return void
     */
    protected function preparation() {}

    /**
     * build relations model to model
     *
     * @return void
     */
    abstract public function buildRelations();
}
