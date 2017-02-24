<?php 

namespace Flipbox\OrmManager\Relations;

use ReflectionClass;
use Illuminate\Support\Str;

class MorphToMany extends Relation
{
    /**
     * required options to replace
     *
     * @var array
     */
    protected $requiredOptions = ['name'];

    /**
     * name of relations
     *
     * @var string
     */
    protected $name;

    /**
     * method suffix
     *
     * @var string
     */
    protected $nameSuffix = 'able';

    /**
     * set default options
     *
     * @return void
     */
    protected function setDefaultOptions()
    {
        $refToModel = new ReflectionClass($this->toModel);

        if (! isset($this->checkingOptions['name'])) {
            if (is_null($this->name)) {
                $name = $this->getRelationName(strtolower($refToModel->getShortName()));
                $name = $this->command->ask('What relation name do you use?', $name);
            } else {
                $name = $this->name;
            }
        } else {
            $name = $this->checkingOptions['name'];
        }
        
        $this->defaultOptions = [
            'name' => $name,
            'table' => Str::plural($name),
            'foreign_key' => $name.'_id',
            'related_key' => strtolower($refToModel->getShortName()).'_id'
        ];
    }
    
    /**
     * get relation name form table name
     *
     * @param string $tableName
     * @return string
     */
    protected function getRelationName($tableName)
    {
        $name = Str::singular($tableName);

        return $name.$this->nameSuffix;
    }

    /**
     * set connected db relation options
     *
     * @return void
     */
    protected function setConnectedRelationOptions()
    {
        if (! $this->database->isTableExists($table = $this->checkingOptions['table'])) {
            $table = $this->options['table'] = $this->command->choice(
                "Can't find table {$table}, what are you using?",
                $this->database->getTables()
            );

            $this->name = Str::singular($table);
            $this->setDefaultOptions();
        }

        if (! $this->database->isFieldExists($table, $foreignKey = $this->checkingOptions['foreign_key'])) {
            $this->options['foreign_key'] = $this->command->choice(
                "Can't find field {$foreignKey} in the table {$table} as foreign key of table {$this->model->getTable()}, choice one!",
                $this->database->getTableFields($table)
            );
        }

        if (! $this->database->isFieldExists($table, $relatedKey = $this->checkingOptions['related_key'])) {
            $this->options['related_key'] = $this->command->choice(
                "Can't find field {$relatedKey} in the table {$table} as related key of table {$this->toModel->getTable()}, choice one!",
                $this->database->getTableFields($table)
            );
        }
    }

    /**
     * get relation options rules
     *
     * @return array
     */
    protected function getRelationOptionsRules()
    {
        return [
            "There should be table {$this->defaultOptions['table']} as relation table",
            "There should be field {$this->defaultOptions['foreign_key']} in the table {$this->defaultOptions['table']} as foreign key of table {$this->model->getTable()}",
            "There should be field {$this->defaultOptions['related_key']} in the table {$this->defaultOptions['table']} as related key of table {$this->toModel->getTable()}",
        ];
    }

    /**
     * ask to use custome options
     *
     * @return void
     */
    protected function askToUseCustomeOptions()
    {
        $this->options['table'] = $this->command->ask(
                                        "The table of relation will be?",
                                        $this->defaultOptions['table']
                                    );

        $this->name = Str::singular($this->options['table']);
        $this->setDefaultOptions();

        $this->options['foreign_key'] = $this->command->ask(
                                            "The foreign key of table {$this->model->getTable()} in the table {$this->defaultOptions['table']} will be?",
                                            $this->defaultOptions['foreign_key']
                                        );

        $this->options['related_key'] = $this->command->ask(
                                            "The related key of table {$this->toModel->getTable()} in the table {$this->defaultOptions['table']} will be?",
                                            $this->defaultOptions['related_key']
                                        );
    }
    
    /**
     * get method name form class
     *
     * @param string $name
     * @return string
     */
    protected function getMethodName($name)
    {
        return Str::plural($name);
    }

    /**
     * get stub method file
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../Stubs/morphToMany.stub';
    }
}
