<?php 

namespace Flipbox\OrmManager\BothRelations;

class MorphOneToMany extends MorphOneToOne
{
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
            $this->command->buildMethod($model, 'morphMany', $this->model, $this->options);
        }
    }
}
