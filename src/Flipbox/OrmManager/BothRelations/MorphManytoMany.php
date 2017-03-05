<?php 

namespace Flipbox\OrmManager\BothRelations;

use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;

class MorphManytoMany extends BothRelation
{
    /**
     * method suffix
     *
     * @var string
     */
    protected $nameSuffix = 'able';
    
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
        $this->command = $command;
        $this->manager = $manager;
        $this->db = $this->manager->db;
        $this->model = $model;

        if (is_array($toModel)) {
            foreach ($toModel as $model) {
                $this->toModels[] = $model;
            }
        } else {
            $this->toModels = [$toModel];
        }

        $this->setDefaultOptions($options);
        $this->stylingText();
        $this->setRelationOptions($options);
        $this->options = array_merge($this->defaultOptions, $this->options);
    }

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
                "Can't find field {$this->text['foreign_key']} in the table {$this->text['pivot_table']} as {$this->text['foreign_text']}, choice one!",
                $this->getFields($table)
            );
        }

        if (! $this->db->isFieldExists($table, $this->defaultOptions['related_key'])) {
            $this->options['related_key'] = $this->command->choice(
                "Can't find field {$this->text['related_key']} in the table {$this->text['pivot_table']} as {$this->text['related_text']}, choice one!",
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
            "There should be field {$this->text['foreign_key']} in the table {$this->text['pivot_table']} as {$this->text['foreign_text']}",
            "There should be field {$this->text['related_key']} in the table {$this->text['pivot_table']} as {$this->text['related_text']}",
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
                                            "The {$this->text['foreign_text']} in the table {$this->text['pivot_table']} will be?",
                                            $foreignKey
                                        );

        $this->options['related_key'] = $this->command->ask(
                                            "The {$this->text['related_text']} in the table {$this->text['pivot_table']} will be?",
                                            $this->defaultOptions['related_key']
                                        );
    }

    /**
     * build relations between models
     *
     * @return void
     */
    public function buildRelations()
    {
        foreach ($this->toModels as $key => $toModel) {
            $this->command->buildMethod($this->model, 'morphedByMany', $toModel, $this->options);
            $this->command->buildMethod($toModel, 'morphToMany', $this->model, $this->options);
        }
    }
}
