<?php 

namespace Flipbox\OrmManager\BothRelations;

use Illuminate\Console\Command;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\Relations\Relation;

abstract class BothRelation extends Relation
{
    /**
     * to models to connect
     *
     * @var array
     */
    protected $toModels;

    /**
     * Create a new Model instance.
     *
     * @param Command $command
     * @param ModelManager $manager
     * @param Model $model
     * @param mixed $toModel
     * @param array $options
     * @return void
     */
    public function __construct(Command $command,
                                ModelManager $manager,
                                Model $model,
                                $toModel=null,
                                array $options=[])
    {
        $this->options = $options;

        parent::__construct($command, $manager, $model, $toModel, $options);

        $this->options = array_merge($this->defaultOptions, $this->options);
    }

    /**
     * show captions process
     *
     * @param Model $model
     * @param Model $toModel
     * @return void
     */
    protected function showCaptionProcess(Model $model, Model $toModel=null) {}

    /**
     * get stub method file
     *
     * @return string
     */
    protected function getStub() {}
}
