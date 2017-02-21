<?php 

namespace Flipbox\OrmManager\BothRelations;

class OneToMany extends OneToOne
{
    /**
     * build relations model to model
     *
     * @return void
     */
    public function buildRelations()
    {
        $this->command->buildMethod($this->model, 'hasMany', $this->toModel, $this->options);
        $this->command->buildMethod($this->toModel, 'belongsTo', $this->model, $this->options);
    }
}
