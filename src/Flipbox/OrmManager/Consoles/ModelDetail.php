<?php 

namespace Flipbox\OrmManager\Consoles;

use ReflectionClass;
use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\DatabaseConnection;

class ModelDetail extends Command
{
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
            return $this->showDetail($this->manager->makeClass($model));
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
        $refClass = new ReflectionClass($model);

        $this->question("Detail of Model {$refClass->getShortName()}");

        if (!$this->db->isConnected()) {
            $this->warn("Not Connected to databse, please check your connection config\r");
        }

        $this->info("table : {$model->getTable()}");
        $this->info("foreign key : {$model->getKeyName()}");
    
        if ($this->db->isConnected()) {
            $this->showTableFileds($model);
        }

        $this->showRelatoins($model);
        $this->showMutators($model);
        $this->showAccesors($model);
        $this->showScopes($model);
    }

    /**
     * show table fileds
     *
     * @param Model $model
     * @return void
     */
    protected function showTableFileds(Model $model)
    {
        $this->info("model table");
        
        $fields = $this->db->getTableFields($model->getTable());
        $header = ['name', 'type','null','length', 'unsigned', 'autoincrement', 'primary_key', 'foreign_key'];
        $this->table($header, $fields);
    }

    /**
     * show relations of model
     *
     * @param Model $model
     * @return void
     */
    protected function showRelatoins(Model $model)
    {
        $this->info("model relations");

        $refModel = new ReflectionClass($model);
        $relations = $this->manager->getRelations($model);
        $tbody = [];

        foreach ($relations as $relation) {
            $refToModel = new ReflectionClass($relation);
            $related = $relation->getRelated() ?:null;

            $tbody[] = [
                $refModel->getShortName(),
                "\033[33m{$refToModel->getShortName()}\033[0m",
                $related ? (new ReflectionClass($related))->getShortName() : '-',
            ];
        }

        $this->table(['model', 'relation', 'to-model'], $tbody);
    }

    /**
     * show mutators of model
     *
     * @param Model $model
     * @return void
     */
    protected function showMutators(Model $model)
    {
        $mutators = $this->manager->getMutators($model);

        if ($mutators->count() > 0) {
            $mutators = implode(', ', $mutators->map(function($method){
                return $method->getName();
            })->toArray());

            $this->info("model mutators : {$mutators}");
        }
    }

    /**
     * show accesors of model
     *
     * @param Model $model
     * @return void
     */
    protected function showAccesors(Model $model)
    {
        $accessors = $this->manager->getAccessors($model);

        if ($accessors->count() > 0) {
            $accessors = implode(', ', $accessors->map(function($method){
                return $method->getName();
            })->toArray());

            $this->info("model accessors : {$accessors}");
        }
    }


    /**
     * show scopes of model
     *
     * @param Model $model
     * @return void
     */
    protected function showScopes(Model $model)
    {
        $scopes = $this->manager->getScopes($model);

        if ($scopes->count() > 0) {
            $scopes = implode(', ', $scopes->map(function($method){
                return $method->getName();
            })->toArray());

            $this->info("model scopes : {$scopes}");
        }
    }
}
