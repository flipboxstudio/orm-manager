<?php 

namespace Flipbox\OrmManager\BothRelations;

class OneToOne extends BothRelation
{
    /**
     * set default options
     *
     * @param array $options
     * @return void
     */
    protected function setDefaultOptions(array $options=[])
    {
        $this->defaultOptions = [
            'foreign_key' => $this->model->getForeignKey(),
            'primary_key' => $this->model->getKeyName()
        ];
    }

    /**
     * styling text
     *
     * @return void
     */
    protected function stylingText()
    {
        $modelTable = $this->model->getTable();
        $toModelTable = $this->toModel->getTable();
        $foreignKey = $this->defaultOptions['foreign_key'];
        $primaryKey = $this->defaultOptions['primary_key'];

        $this->text = [
            'table' => "[".$this->command->paintString($modelTable ,'green')."]",
            'to_table' => "[".$this->command->paintString($toModelTable ,'green')."]",
            'foreign_key' => "[".$this->command->paintString($foreignKey ,'green')."]",
            'primary_key' => "[".$this->command->paintString($primaryKey ,'green')."]",
            'primary_text' => $this->command->paintString('primary key', 'brown'),
            'foreign_text' => $this->command->paintString('foreign key', 'brown')
        ];
    }

    /**
     * get connected db relation options
     *
     * @return void
     */
    protected function setConnectedRelationOptions()
    {
        $modelTable = $table = $this->model->getTable();
        $toModelTable = $table = $this->toModel->getTable();
        $foreignKey = $this->defaultOptions['foreign_key'];
        $primaryKey = $this->defaultOptions['primary_key'];
        
        if (! $this->db->isFieldExists($toModelTable, $foreignKey)) {
            $question = "Can't find field {$this->text['foreign_key']} in the table {$this->text['to_table']} as {$this->text['foreign_text']} of table {$this->text['table']}, choice one!";
            $this->options['foreign_key'] = $this->command->choice($question, $this->getFields($toModelTable));
        }

        if (! $this->db->isFieldExists($modelTable, $primaryKey)) {
            $question = "Can't find field {$this->text['primary_key']} in the table {$this->text['table']} as {$this->text['primary_text']} of table {$this->text['table']}, choice one!";
            $this->options['primary_key'] = $this->command->choice($question, $this->getFields($modelTable));
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
            "There should be field {$this->text['foreign_key']} in table {$this->text['to_table']} as {$this->text['foreign_text']} of table {$this->text['table']}",
            "There should be field {$this->text['primary_key']} in table {$this->text['table']} as {$this->text['primary_text']}"
        ];
    }

    /**
     * ask to use custome options
     *
     * @return void
     */
    protected function askToUseCustomeOptions()
    {
        $question = "The {$this->text['foreign_text']} of table {$this->text['table']} in the table {$this->text['to_table']}, will be?";
        $this->options['foreign_key'] = $this->command->ask($question, $this->defaultOptions['foreign_key']);

        $question = "The {$this->text['primary_text']} of the table {$this->text['table']}, will be?";
        $this->options['primary_key'] = $this->command->ask($question, $this->defaultOptions['primary_key']);
    }

    /**
     * build relations between models
     *
     * @return void
     */
    public function buildRelations()
    {
        $this->command->buildMethod($this->model, 'hasOne', $this->toModel, $this->options);
        $this->command->buildMethod($this->toModel, 'belongsTo', $this->model, $this->options);
    }
}
