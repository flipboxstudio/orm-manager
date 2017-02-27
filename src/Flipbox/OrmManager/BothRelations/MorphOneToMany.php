<?php 

namespace Flipbox\OrmManager\BothRelations;

class MorphOneToMany extends MorphOneToOne
{
    /**
     * build relations between models
     *
     * @return void
     */
    public function buildRelations()
    {
        $this->command->buildMethod($this->model, 'morphTo', $this->toModel, $this->options);

        foreach ($this->toModels as $key => $toModel) {
            $options = $this->options;
            $options['primary_key'] = $this->options['primary_key'][$key];
            $this->command->buildMethod($toModel, 'morphMany', $this->model, $options);
        }
    }
}
