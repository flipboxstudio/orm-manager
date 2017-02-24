<?php 

namespace Flipbox\OrmManager\Consoles;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use Flipbox\OrmManager\FontColor;
use Flipbox\OrmManager\ModelManager;
use Flipbox\OrmManager\DatabaseConnection;

class ModelList extends Command
{
    use FontColor;

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
			$header = ['Namespace', 'Model', 'Table', 'PrimaryKey', 'Relations', 'Mutators', 'Accessor', 'Scope', 'Soft Deletes'];

			return $this->table($header, $models->map(function($model){
                $table = $model['table'];
                $model['name'] = $this->paintString($model['name'], 'white');
                $model['table'] = $this->paintTable($table);
                $model['primary_key'] = $this->paintPrimaryKey($table, $model['primary_key']);
                $model['soft_deletes'] = $this->paintSoftDeletes($model['soft_deletes']);
                return $model;
            }));
		}

		$this->error('No models found');
	}

    /**
     * paint table
     *
     * @param string $table
     * @return string
     */
    protected function paintTable($table)
    {
        if ($this->database->isConnected()) {
            if ($this->database->isTableExists($table)) {
                return $this->paintString($table, 'green');
            }

            return $this->paintString($table, 'white', 'red');
        }

        return $this->paintString($table, 'red');
    }

    /**
     * paint primary key
     *
     * @param bool $primaryKey
     * @return string
     */
    protected function paintPrimaryKey($table, $primaryKey)
    {
        if ($this->database->isConnected()) {
            if ($this->database->isTableExists($table)
                AND $this->database->isFieldExists($table, $primaryKey)) {

                return $this->paintString($primaryKey, 'green');
            }

            return $this->paintString($primaryKey, 'white', 'red');
        }

        return $this->paintString($primaryKey, 'red');
    }

    /**
     * paint soft delete
     *
     * @param bool $use
     * @return string
     */
    protected function paintSoftDeletes($use)
    {
        return $use ? $this->paintString('yes', 'green')
                    : $this->paintString('no', 'red');
    }
}
