<?php 

namespace Flipbox\OrmManager\Exceptions;

use Exception;

class FolderNotFound extends Exception
{
    /**
     * Create a new FolderNotFound instance.
     *
     * @param  string $path
     * @return void
     */
    public function __construct($path)
    {
        parent::__construct('Directory not found : '.$path);
    }
}
