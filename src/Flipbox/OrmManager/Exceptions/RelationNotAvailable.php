<?php 

namespace Flipbox\OrmManager\Exceptions;

use Exception;

class RelationNotAvailable extends Exception
{
    /**
     * Constructor
     *
     * @param string $relation
     * @return void
     */
    public function __construct($relation)
    {
        parent::__construct("Relation {$relation} doesn't available");
    }
}
