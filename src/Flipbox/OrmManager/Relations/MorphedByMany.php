<?php 

namespace Flipbox\OrmManager\Relations;

use ReflectionClass;
use Illuminate\Support\Str;

class MorphedByMany extends Relation
{
    /**
     * required options to replace
     *
     * @var array
     */
    protected $requiredOptions = ['name'];

    /**
     * method suffix
     *
     * @var string
     */
    protected $nameSuffix = 'able';

    /**
     * set default options
     *
     * @param array $options
     * @return void
     */
    protected function setDefaultOptions(array $options=[])
    {
        $this->text['relation_name_text'] = $this->command->paintString('relation name', 'brown');

        if (! isset($options['name'])) {
            $refModel = new ReflectionClass($this->model);
            $name = $this->getRelationName(strtolower($refModel->getShortName()));
            $name = $this->command->ask("What {$this->text['relation_name_text']} do you use?", $name);
        } else {
            $name = $options['name'];
        }
        
        $this->defaultOptions = [
            'name' => $name,
            'pivot_table' => Str::plural($name),
            'foreign_key' => $name.'_id',
            'related_key' => $this->model->getForeignKey()
        ];
    }

    /**
     * styling text
     *
     * @return void
     */
    protected function stylingText()
    {
        $this->text = array_merge($this->text, [
            'name' => "[".$this->command->paintString($this->defaultOptions['name'], 'green')."]",
            'pivot_table' => "[".$this->command->paintString($this->defaultOptions['pivot_table'], 'green')."]",
            'table' => "[".$this->command->paintString($this->model->getTable(), 'green')."]",
            'to_table' => "[".$this->command->paintString($this->toModel->getTable(), 'green')."]",
            'foreign_key' => "[".$this->command->paintString($this->defaultOptions['foreign_key'], 'green')."]",
            'related_key' => "[".$this->command->paintString($this->defaultOptions['related_key'], 'green')."]",
            'pivot_text' => $this->command->paintString('pivot table', 'brown'),
            'foreign_text' => $this->command->paintString('foreign key', 'brown'),
            'related_text' => $this->command->paintString('related key', 'brown'),
        ]);
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
        $foreignKey = $this->defaultOptions['foreign_key'];

        if (! $this->db->isTableExists($table = $this->defaultOptions['pivot_table'])) {
            $table = $this->options['pivot_table'] = $this->command->choice(
                "Can't find table {$this->text['pivot_table']} as {$this->text['pivot_text']}, what are you using?",
                $this->getTables()
            );

            $this->text['pivot_table'] = "[".$this->command->paintString($table, 'green')."]";
            $name = Str::singular($table);
            $foreignKey = $name.'_id';
        }

        if (! $this->db->isFieldExists($table, $foreignKey)) {
            $this->options['foreign_key'] = $this->command->choice(
                "Can't find field {$this->text['foreign_key']} in the table {$this->text['pivot_table']} as {$this->text['foreign_text']} of table {$this->text['to_table']}, choice one!",
                $this->getFields($table)
            );
        }

        if (! $this->db->isFieldExists($table, $this->defaultOptions['related_key'])) {
            $this->options['related_key'] = $this->command->choice(
                "Can't find field {$this->text['related_key']} in the table {$this->text['pivot_table']} as {$this->text['related_text']} of table {$this->text['table']}, choice one!",
                $this->getFields($table)
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
            "There should be table {$this->text['pivot_table']} as {$this->text['pivot_text']} of both relation",
            "There should be field {$this->text['foreign_key']} in the table {$this->text['pivot_table']} as {$this->text['foreign_text']} of table {$this->text['to_table']}",
            "There should be field {$this->text['related_key']} in the table {$this->text['pivot_table']} as {$this->text['related_text']} of table {$this->text['table']}",
        ];
    }

    /**
     * ask to use custome options
     *
     * @return void
     */
    protected function askToUseCustomeOptions()
    {
        $this->options['pivot_table'] = $this->command->ask(
                                        "The {$this->text['pivot_text']} of both relation will be?",
                                        $this->defaultOptions['pivot_table']
                                    );

        $name = Str::singular($this->options['pivot_table']);
        $foreignKey = $name.'_id';

        $this->options['foreign_key'] = $this->command->ask(
                                            "The {$this->text['foreign_text']} of table {$this->text['to_table']} in the table {$this->text['pivot_table']} will be?",
                                            $foreignKey
                                        );

        $this->options['related_key'] = $this->command->ask(
                                            "The {$this->text['related_text']} of table {$this->text['table']} in the table {$this->text['pivot_table']} will be?",
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
        return __DIR__.'/../Stubs/morphedByMany.stub';
    }
}
