<?php 

namespace Flipbox\OrmManager\Consoles;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use Flipbox\OrmManager\ModelManager;
use Flipbox\OrmManager\DatabaseConnection;

class ModelList extends Command
{
	/**
	 * database connection
	 *
	 * @var DatabaseConnection
	 */
	protected $database;

	/**
	 * model manager
	 *
	 * @var ModelManager
	 */
	protected $model;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'orm:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show Model list';

    /**
     * Create a new queue listen command.
     *
     * @return void
     */
    public function __construct(Repository $config)
    {
    	parent::__construct();

    	$this->database = new DatabaseConnection;
    	$this->model = new ModelManager($config->get('orm'));
    }

    /**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try {
			$models = $this->model->getModels(true);
		} catch (Exception $e) {
			return $this->error($e->getMessage());
		}

		if (!$this->database->isConnected()) {
			$this->warn("Not Connected to databse, please check your connection config\r");
		}
		
		if (count($models) > 0) {
			$header = ['Model', 'Table', 'PrimaryKey', 'Relations', 'Mutators', 'Accessor', 'Scope', 'Soft Deletes'];
			return $this->table($header, $models);
		}

		$this->error('No models found');
	}
}
