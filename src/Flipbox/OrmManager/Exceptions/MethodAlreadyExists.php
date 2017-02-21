<?php 

namespace Flipbox\OrmManager\Exceptions;

use Exception;

class MethodAlreadyExists extends Exception
{
	/**
	 * Create a new MethodAlreadyExists instance.
	 *
	 * @param string $name
	 * @return void
	 */
	public function __construct($name)
	{
		parent::__construct("Method {$name} is already exists, connection may has been created.");
	}
}
