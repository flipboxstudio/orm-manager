<?php 

namespace Flipbox\OrmManager\Consoles;

use Exception;
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
        try {
            $models = $this->manager->toArray();
        } catch (Exception $e) {
			return $this->error($e->getMessage());
		}

		if (!$this->db->isConnected()) {
			$this->warn("Not Connected to databse, please check your connection config");
		}

		if (count($models) > 0) {
			$header = ['Namespace', 'Model', 'Table', 'PrimaryKey', 'Relations', 'Mutators', 'Accessor', 'Scope', 'Soft Deletes'];

			return $this->table($header, array_map(function($model){
                $table = $model['table'];
                $model['namespace'] = $this->paintString($model['namespace'], 'brown');
                $model['name'] = $this->paintString($model['name'], 'green');
                $model['table'] = $this->paintTable($table);
                $model['primary_key'] = $this->paintPrimaryKey($table, $model['primary_key']);
                $model['soft_deletes'] = $this->paintSoftDeletes($model['soft_deletes']);
                return $model;
            }, $models));
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
        if ($this->db->isConnected()) {
            if ($this->db->isTableExists($table)) {
                return $this->paintString($table, 'brown');
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
        if ($this->db->isConnected()) {
            if ($this->db->isTableExists($table)
                AND $this->db->isFieldExists($table, $primaryKey)) {

                return $this->paintString($primaryKey, 'brown');
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
