<?php 

namespace Flipbox\OrmManager\Consoles;

use ReflectionClass;
use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\DatabaseConnection;
use Flipbox\OrmManager\Consoles\Command as LocalComand;

class ModelDetail extends Command
{
    use LocalComand, FontColor {
        FontColor::paintString insteadof LocalComand;
    }

    /**
     * model manager
     *
     * @var ModelManager
     */
    protected $manager;

    /**
     * database
     *
     * @var ModelManager
     */
    protected $db;

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
     * @return void
     */
    public function __construct(Repository $config)
    {
        parent::__construct();

        $this->db = new DatabaseConnection;
        $this->manager = new ModelManager($config->get('orm'), $this->db);
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
        $this->showProperty($model);
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
     * show table property
     *
     * @param Model $model
     * @return void
     */
    protected function showProperty(Model $model)
    {
        $name = $this->manager->getClassName($model);

        $this->title("Property of Model {$name}: ");

        $properties = [
            'mutators' => $this->manager->getMutators($model),
            'accessors' => $this->manager->getAccessors($model),
            'scopes' => $this->manager->getScopes($model),
        ];

        $rows = [];

        foreach (max($properties) as $property => $method) {
            $mutator = '';
            if (($mutators = &$properties['mutators'])->count() > 0) {
                $mutator = $this->paintString($mutators->first()->getName(), 'brown');
                unset($mutators[0]);
            }

            $accessor = '';
            if (($accessors = &$properties['accessors'])->count() > 0) {
                $accessor = $this->paintString($accessors->first()->getName(), 'brown');
                unset($accessors[0]);
            }

            $scope = '';
            if (($scopes = &$properties['scopes'])->count() > 0) {
                $scope = $this->paintString($scopes->first()->getName(), 'brown');
                unset($scopes[0]);
            }

            $rows[] = [$mutator, $accessor, $scope];
        }

        $headers = ['mutator', 'accessor', 'scope'];

        $this->table($headers, $rows);
        
    }
}
