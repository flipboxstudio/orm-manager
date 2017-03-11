<?php 

namespace Flipbox\OrmManager\Consoles;

use Illuminate\Support\Str;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\DatabaseConnection;

class ModelDetail extends Command
{
    /**
     * database
     *
     * @var DatabaseConnection
     */
    protected $db;

    /**
     * model manager
     *
     * @var ModelManager
     */
    protected $manager;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'orm:detail {model : A model that would see the details}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show detail of model';

    /**
     * Create a new queue listen command.
     *
     * @param DatabaseConnection $db
     * @param ModelManager $manager
     * @return void
     */
    public function __construct(DatabaseConnection $db, ModelManager $manager)
    {
        parent::__construct();

        $this->db = $db;
        $this->manager = $manager;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $model = $this->argument('model');

        if ($this->manager->isModelExists($model)) {
            return $this->showDetail($this->manager->getModel($model));
        }

        $this->error("Model {$model} is not found");
    }

    /**
     * show detail of model
     *
     * @param Model $model
     * @return void
     */
    protected function showDetail(Model $model)
    {
        $name = $this->manager->getClassName($model);

        $this->title("Summary of Model {$name} :");

        $summary = $this->manager->getModelSummary($name);
        $rows = [];

        foreach ($summary as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'yes' : 'no';
            }

            $valueText = $this->paintString($value, 'brown');

            $rows[] = [$key, $valueText];
        }

        $this->table(['Key', 'Value'], $rows);

        $this->showDatabaseFields($model);
        $this->showRelatoins($model);
        $this->showModifier($model);
    }

    /**
     * show database fileds
     *
     * @param Model $model
     * @return void
     */
    protected function showDatabaseFields(Model $model)
    {
        $this->title("Table {$model->getTable()} :");

        if ( ! $this->db->isConnected()) {
            $this->warn("Not Connected to databse, please check your connection config\r");
        } else {
            $fields = $this->db->getFields($model->getTable());
            $headers = ['name', 'type', 'null', 'length', 'unsigned', 'autoincrement', 'primary_key', 'foreign_key'];

            $this->table($headers, $fields->toArray());
        }
    }

    /**
     * show relations of model
     *
     * @param Model $model
     * @return void
     */
    protected function showRelatoins(Model $model)
    {
        $name = $this->manager->getClassName($model);

        $this->title("Relations of Model {$name}: ");

        $relations = $this->manager->getRelations($model);
        $tbody = [];

        foreach ($relations as $relation) {
            $relationName = $this->manager->getClassName($relation);
            $related = $relation->getRelated() ?:null;

            $tbody[] = [
                $this->paintString($relationName, 'brown'),
                $related ? $this->manager->getClassName($related) : '',
            ];
        }

        $this->table(['relation', 'model'], $tbody);
    }

    /**
     * show model modifier
     *
     * @param Model $model
     * @return void
     */
    protected function showModifier(Model $model)
    {
        $name = $this->manager->getClassName($model);

        $this->title("Modifier of Model {$name}: ");

        $modifiers = [
            'mutators' => $this->manager->getMutators($model),
            'accessors' => $this->manager->getAccessors($model),
            'scopes' => $this->manager->getScopes($model),
        ];

         $rows = [];

        foreach (max($modifiers) as $modifier) {
            foreach (['mutator', 'accessor', 'scope'] as $type) {
                $$type = '';
                $types = &$modifiers[Str::plural($type)];

                if ($types->count() > 0) {
                    $$type = $this->paintString($types->first()->getName(), 'brown');
                    unset($types[0]);
                }
            }

            $rows[] = [$mutator, $accessor, $scope];
        }

        $headers = ['mutator', 'accessor', 'scope'];

        $this->table($headers, $rows);
        
    }
}
