<?php 

namespace Flipbox\OrmManager\BothRelations;

use ReflectionClass;

class OneToOne extends BothRelation
{
    /**
     * check is position model is valid
     *
     * @return bool
     */
    protected function isPositionModelValid()
    {
        $foreignTable = $this->toModel->getTable();
        $refModel = new ReflectionClass($this->model);
        $foreignKey = strtolower($refModel->getShortName()).'_'.$this->model->getKeyName();

        return $this->database->isFieldExists($foreignTable, $foreignKey);
    }

    /**
     * ask which table where foreign key filed exists
     *
     * @return void
     */
    protected function askWhereForeignKeyTable()
    {
        $foreignTable = $this->command->choice('Which table that conatain foreign key?', [
            $this->toModel->getTable(), $this->model->getTable(),
        ]);

        $this->options['foreign_key'] = $this->command->choice('what foreign key of both relation?',
            $this->getFields($foreignTable)
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
        $this->command->buildMethod($this->model, 'hasOne', $this->toModel, $this->options);
        $this->command->buildMethod($this->toModel, 'belongsTo', $this->model, $this->options);
    }
}
