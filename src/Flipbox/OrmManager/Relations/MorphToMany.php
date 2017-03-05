<?php 

namespace Flipbox\OrmManager\Relations;

class MorphToMany extends MorphedByMany
{   
    /**
     * reverse operation
     *
     * @var bool
     */
    protected $reverse = true;

    /**
     * get stub method file
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../Stubs/morphToMany.stub';
    }
}
