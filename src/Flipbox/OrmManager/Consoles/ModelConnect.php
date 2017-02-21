<?php 

namespace Flipbox\OrmManager\Consoles;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model;
use Flipbox\OrmManager\Exceptions\ModelNotFound;
use Flipbox\OrmManager\Exceptions\RelationNotAvailable;

class ModelConnect extends Command
{
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
    protected $signature = 'orm:connect {model?} {relation?} {to-model?}
    						{--i|interactive : Interactive question connect}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate connections method of class model';

    /**
	 * Create a new ModelConnect instance.
	 *
	 * @param Repository $config
	 * @return void
	 */
	public function __construct(Repository $config)
	{
		parent::__construct();

		$this->manager = new ModelManager($config['orm']);
	}

    /**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try {
			if ($this->option('interactive')) {
				extract($this->runInteractiveConnect());
			} else {
				extract($this->getArgumentConnect());
			}

			$this->buildMethod($model, $relation, $toModel);		
		} catch (Exception $e) {
			return $this->error($e->getMessage());
		}
	}
	
	/**
	 * get data input from arguments
	 *
	 * @return array
	 */
	protected function getArgumentConnect()
	{
		if ($this->isRequiredArgFulfilled($this->arguments())) {
			$data['model'] = $this->getModel($this->argument('model'));
			$data['relation'] = $this->getRelation($this->argument('relation'));
			
			if ($this->argument('relation') !== 'morphTo') {
				$data['toModel'] = $this->getModel($this->argument('to-model'));
			} else {
				$data['toModel'] = null;
			}
	
			return $data;
		}

		throw new Exception("There are required argument not exists");
	}

	/**
	 * check is fulfilled required arguments
	 *
	 * @param array $arguments
	 * @return bool
	 */
	protected function isRequiredArgFulfilled($arguments)
	{
		if (! $this->option('interactive')) {		
			if ($arguments['relation'] !== 'morphTo') {
				return ! is_null($arguments['model'])
					AND ! is_null($arguments['relation'])
					AND ! is_null($arguments['to-model']);
			} else {
				return ! is_null($arguments['model'])
					AND ! is_null($arguments['relation']);				
			}
		}

		return true;
	}

	/**
	 * run interactive connect
	 *
	 * @return void
	 */
	protected function runInteractiveConnect()
	{
        $models = $this->manager->getModels()->pluck('name')->toArray();
		
		$search = array_search($this->argument('model'), $models);
		$default = $search === false ? null : $search;
		$askModel = $this->choice('Which model would you like to connect?', $models, $default);
		$data['model'] = $this->getModel($askModel);

		$search = array_search($this->argument('relation'), $this->manager->relations);
		$default = $search === false ? null : $search;
		$data['relation'] = $this->choice('Which relation between two models?', $this->manager->relations, $default);

		$data['toModel'] = null;
		if ($data['relation'] !== 'morphTo') {
			$search = array_search($this->argument('model'), $models);
			$default = $search === false ? null : $search;
			$askToModel = $this->choice('Which model that you want to connect with '.$askModel, $models, $default);
			$data['toModel'] = $this->getModel($askToModel);
		}

		return $data;
	}

	/**
	 * get model
	 *
	 * @param string $name
	 * @return Object
	 */
	protected function getModel($name)
	{
		if ($this->manager->isModelExists($name)) {
			return $this->manager->makeClass($name);
		}
		
		throw new ModelNotFound($name);
	}

	/**
	 * get relation
	 *
	 * @param string $relation
	 * @return string
	 */
	protected function getRelation($relation)
	{
		if ($this->manager->isRelationAvailable($relation)) {
			return $relation;
		}
		
		throw new RelationNotAvailable("Relation {$relation} doesn't available");
	}

	/**
	 * build method
	 *
	 * @param Model $model
	 * @param string $relation
	 * @param mix Model|null $toModel
	 * @param array $options
	 * @return void
	 */
	public function buildMethod(Model $model, $relation, $toModel, $options=[])
	{
		try {
			$relation = $this->newRelationInstance($relation, $model, $toModel, $options);

			$relation->createMethod();

			$this->info('Connection has been created');
		} catch (Exception $e) {
			return $this->error($e->getMessage());
		}
	}

	/**
	 * create new model instance by relation
	 *
	 * @param string $relation
	 * @param Model $model
	 * @param mix Model|null $toModel
	 * @param array $options
	 * @return Model
	 */
	protected function newRelationInstance($relation, Model $model, $toModel, array $options = [])
	{
		$class = 'Flipbox\OrmManager\Relations\\'.Str::studly($relation);

		return new $class($this, $this->manager, $model, $toModel, $options);
	}
}
