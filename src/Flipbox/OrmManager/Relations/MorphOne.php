<?php 

namespace Flipbox\OrmManager\Relations;

use Illuminate\Support\Str;

class MorphOne extends MorphTo
{
    /**
     * required options to replace
     *
     * @var array
     */
    protected $requiredOptions = ['name'];
    
    /**
     * reverse operation
     *
     * @var bool
     */
    protected $reverse = true;

    /**
     * set default options
     *
     * @return void
     */
    protected function setDefaultOptions()
    {
        parent::setDefaultOptions();

        $this->defaultOptions['primary_key'] = $this->toModel->getKeyName();
    }
    
    /**
     * set connected db relation options
     *
     * @return void
     */
    protected function setConnectedRelationOptions()
    {
        parent::setConnectedRelationOptions();

        if (! $this->database->isFieldExists($table = $this->model->getTable(), $primaryKey = $this->defaultOptions['primary_key'])) {
            $this->options['primary_key'] = $this->command->choice(
                "Can't find field {$primaryKey} in the table {$table} as primary key of table {$table}, choice one!",
                $this->database->getTableFields($table)
            );
        }
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
     * get relation options rules
     *
     * @return array
     */
    protected function getRelationOptionsRules()
    {
        return [
            "Relation name is {$this->defaultOptions['name']}",
            "There should be field {$this->defaultOptions['type']} in table {$this->model->getTable()}",
            "There should be field {$this->defaultOptions['id']} in table {$this->model->getTable()}",
            "There should be field {$this->defaultOptions['primary_key']} in table {$this->toModel->getTable()} as primary key of table {$this->toModel->getTable()}"
        ];
    }

    /**
     * ask to use custome options
     *
     * @return void
     */
    protected function askToUseCustomeOptions()
    {
        parent::askToUseCustomeOptions();

        $this->options['primary_key'] = $this->command->ask(
                                            "The primary key of the table {$this->model->getTable()} will be?",
                                            $this->defaultOptions['primary_key']
                                        );
    }

    /**
     * get stub method file
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../Stubs/morphOne.stub';
    }
}
