<?php 

namespace Flipbox\OrmManager\Exceptions;

use Exception;

class ModelNotFound extends Exception
{
	/**
     * Create a new MethodAlreadyExists instance.
     *
     * @param string $name
     * @return void
     */
    public function __construct($name)
    {
        parent::__construct("Model {$name} is not found");
    }
}
