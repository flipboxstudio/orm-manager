<?php 

namespace Flipbox\OrmManager\BothRelations;

use ReflectionClass;
use Illuminate\Console\Command;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;

class MorphOneToOne extends BothRelation
{
    /**
     * method suffix
     *
     * @var string
     */
    protected $nameSuffix = 'able';

    /**
     * type suffix
     *
     * @var string
     */
    protected $typeSuffix = '_type';

    /**
     * id suffix
     *
     * @var string
     */
    protected $idSuffix = '_id';
    
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
     * @param $options
     * @return void
     */
    protected function setDefaultOptions(array $options=[])
    {
        $this->text['relation_name_text'] = $this->command->paintString('relation name', 'brown');

        $refModel = new ReflectionClass($this->model);
        $name = $this->getRelationName(strtolower($refModel->getShortName()));
        $name = $this->command->ask("What {$this->text['relation_name_text']} do you use?", $name);

        $this->defaultOptions = [
            'name' => $name,
            'type' => $this->getTypeName($name),
            'id' => $this->getIdName($name),
        ];

        foreach ($this->toModels as $key => $toModel) {
            $this->defaultOptions['primary_key'][$key] = $toModel->getKeyName();
        }
    }

    /**
     * styling text
     *
     * @return void
     */
    protected function stylingText()
    {
        $this->text = array_merge($this->text, [
            'table' => "[".$this->command->paintString($this->model->getTable(), 'green')."]",
            'name' => "[".$this->command->paintString($this->defaultOptions['name'], 'green')."]",
            'type' => "[".$this->command->paintString($this->defaultOptions['type'], 'green')."]",
            'id' => "[".$this->command->paintString($this->defaultOptions['id'], 'green')."]",
            'related_type_text' => $this->command->paintString('related type', 'brown'),
            'related_id_text' => $this->command->paintString('related id', 'brown'),
            'primary_text' => $this->command->paintString('primary key', 'brown'),
        ]);

        foreach ($this->toModels as $key => $toModel) {
            $this->text['to_table'][$key] = "[".$this->command->paintString($toModel->getTable(), 'green')."]";
            $this->text['primary_key'][$key] = "[".$this->command->paintString($this->defaultOptions['primary_key'][$key], 'green')."]";
        }
    }

    /**
     * get connected db relation options
     *
     * @return void
     */
    protected function setConnectedRelationOptions()
    {
        $fields = $this->getFields($this->model->getTable());
        $name = $this->defaultOptions['name'];

        if (! in_array($this->defaultOptions['type'], $fields) 
            OR ! in_array($this->defaultOptions['id'], $fields)) {
            $this->options['name'] = $name = $this->command->ask("Can't find {$this->text['type']} or {$this->text['id']} in the table {$this->text['table']}, you may not use {$this->text['name']} as {$this->text['relation_name_text']}, what are you using?");
            $this->text['type'] = "[".$this->command->paintString($this->getTypeName($name), 'green')."]";
            $this->text['id'] = "[".$this->command->paintString($this->getIdName($name), 'green')."]";
        }

        if (! in_array($this->getTypeName($name), $fields)) {
            $this->options['type'] = $this->command->choice("Can't find {$this->text['type']} as {$this->text['related_type_text']}, what are you using?", $fields);
        }

        if (! in_array($this->getIdName($name), $fields)) {
            $this->options['id'] = $this->command->choice("Can't find {$this->text['id']} as {$this->text['related_id_text']}, what are you using?", $fields);
        }

        foreach ($this->toModels as $key => $toModel) {
            if (! $this->db->isFieldExists($table = $toModel->getTable(), $this->defaultOptions['primary_key'][$key])) {
                $this->options['primary_key'][$key] = $this->command->choice(
                    "Can't find field {$this->text['primary_key'][$key]} in the table {$this->text['to_table'][$key]} as {$this->text['primary_text']}, choice one!",
                    $this->getFields($table)
                );
            }
        }
    }

    /**
     * get relation options rules
     *
     * @return array
     */
    protected function getRelationOptionsRules()
    {
        $ruels = [
            "The {$this->text['relation_name_text']} is {$this->text['name']}",
            "There should be field {$this->text['type']} in table {$this->text['table']} as {$this->text['related_type_text']}",
            "There should be field {$this->text['id']} in table {$this->text['table']} as {$this->text['related_id_text']}",
        ];

        foreach ($this->toModels as $key => $toModel) {
            $rules[] = "There should be field {$this->text['primary_key'][$key]} in table {$this->text['to_table'][$key]} as {$this->text['primary_text']}";
        }

        return $rules;
    }

    /**
     * ask to use custome options
     *
     * @return void
     */
    protected function askToUseCustomeOptions()
    {
        $this->options = [
            'name' => $this->command->ask("The {$this->text['relation_name_text']} of relation is will be?", $this->defaultOptions['name']),
            'type' => $this->command->ask("The {$this->text['related_type_text']} of relation is will be?", $this->getTypeName($this->options['name'])),
            'id' => $this->command->ask("The {$this->text['related_id_text']} of relation is will be?", $this->getIdName($this->options['name'])),
        ];

        foreach ($this->toModels as $key => $toModel) {
            $this->options['primary_key'][$key] = $this->command->ask("The {$this->text['primary_text']} of the table {$this->text['to_table'][$key]} will be?", $this->defaultOptions['primary_key'][$key]);
        }
    }

    /**
     * get method name form class
     *
     * @param string $name
     * @return string
     */
    protected function getRelationName($name)
    {
        return $name.$this->nameSuffix;
    }

    /**
     * get type name
     *
     * @param string $name
     * @return string
     */
    protected function getTypeName($name)
    {
        return ($name ?: $this->defaultOptions['name']).$this->typeSuffix;
    }

    /**
     * get id name
     *
     * @param string $name
     * @return string
     */
    protected function getIdName($name)
    {
        return ($name ?: $this->defaultOptions['name']).$this->idSuffix;
    }

    /**
     * build relations between models
     *
     * @return void
     */
    public function buildRelations()
    {
        $this->command->buildMethod($this->model, 'morphTo', null, $this->options);

        foreach ($this->toModels as $key => $toModel) {
            $options = $this->options;
            $options['primary_key'] = $this->options['primary_key'][$key];
            $this->command->buildMethod($toModel, 'morphOne', $this->model, $options);
        }
    }
}
