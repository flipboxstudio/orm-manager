<?php 

namespace Flipbox\OrmManager\Exceptions;

use Exception;

class TableNotExists extends Exception
{
	/**
     * Create a new TableNotExists instance.
     *
     * @param string $name
     * @param string $model
     * @return void
     */
    public function __construct($name, $model)
    {
        parent::__construct("Table {$name} of model {$model} is not exists");
    }
}
