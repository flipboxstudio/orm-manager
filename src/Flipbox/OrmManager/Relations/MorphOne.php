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
     * @param $options
     * @return void
     */
    protected function setDefaultOptions(array $options=[])
    {
        parent::setDefaultOptions();

        $this->defaultOptions['primary_key'] = $this->toModel->getKeyName();
        $this->checkingOptions = array_merge($this->defaultOptions, $options);
    }

    /**
     * styling text
     *
     * @return void
     */
    protected function stylingText()
    {
        parent::stylingText();

        $this->text['model_table'] = "[".$this->command->paintString($this->model->getTable(), 'green')."]";
        $this->text['primary_key'] = "[".$this->command->paintString($this->checkingOptions['primary_key'], 'green')."]";
        $this->text['primary_text'] = $this->command->paintString('primary key', 'brown');
    }
    
    /**
     * set connected db relation options
     *
     * @return void
     */
    protected function setConnectedRelationOptions()
    {
        parent::setConnectedRelationOptions();

        if (! $this->db->isFieldExists($table = $this->model->getTable(), $this->defaultOptions['primary_key'])) {
            $this->options['primary_key'] = $this->command->choice(
                "Can't find field {$this->text['primary_key']} in the table {$this->text['model_table']} as {$this->text['primary_text']}, choice one!",
                $this->getFields($table)
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
        return $name;
    }

    /**
     * get relation options rules
     *
     * @return array
     */
    protected function getRelationOptionsRules()
    {
        $rules = parent::getRelationOptionsRules();

        $rules[] = "There should be field {$this->text['primary_key']} in table {$this->text['model_table']} as {$this->text['primary_text']}";

        return $rules;
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
                                            "The {$this->text['primary_text']} of the table {$this->text['model_table']} will be?",
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
