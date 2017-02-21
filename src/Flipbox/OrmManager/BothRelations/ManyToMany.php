<?php 

namespace Flipbox\OrmManager\BothRelations;

use ReflectionClass;
use Illuminate\Support\Str;

class ManyToMany extends Both
{
    /**
     * reposition model by relations key
     *
     * @return void
     */
    protected function repositionModelByKeys()
    {
        $tables = [
            Str::singular($this->model->getTable()),
            Str::singular($this->toModel->getTable())
        ];

        asort($tables, SORT_REGULAR);

        $pivotTable = implode('_', $tables);

        if (! $this->database->isTableExists($pivotTable)) {
            $pivotTable = $this->options['pivot_table'] = $this->command->choice(
                "Can't find table {$pivotTable} in the database as pivot table, choice one!",
                $this->database->getTables()
            );
        }

        $foreignKey1 = Str::singular(strtolower($this->model->getTable())).'_'.$this->model->getKeyName();
        if (! $this->database->isFieldExists($pivotTable, $foreignKey1)) {
            $this->options['foreign_key_1'] = $this->command->choice(
                "Can't find field {$foreignKey1} in the table {$pivotTable} as foreign key of table {$table1}, choice one!",
                $this->database->getTableFields($pivotTable)
            );
        }

        $foreignKey2 = Str::singular(strtolower($this->toModel->getTable())).'_'.$this->toModel->getKeyName();
        if (! $this->database->isFieldExists($pivotTable, $foreignKey2)) {
            $this->options['foreign_key_2'] = $this->command->choice(
                "Can't find field {$foreignKey2} in the table {$pivotTable} as foreign key of table {$table2}, choice one!",
                $this->database->getTableFields($pivotTable)
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
        $this->command->buildMethod($this->model, 'belongsToMany', $this->toModel, $this->options);
        $this->command->buildMethod($this->toModel, 'belongsToMany', $this->model, $this->options);
    }
}
