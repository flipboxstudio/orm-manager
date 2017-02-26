<?php 

namespace Flipbox\OrmManager\Relations;

class MorphedByMany extends MorphToMany
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
        return __DIR__.'/../Stubs/morphedByMany.stub';
    }
}
