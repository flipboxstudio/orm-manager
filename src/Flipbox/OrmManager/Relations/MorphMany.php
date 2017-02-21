<?php 

namespace Flipbox\OrmManager\Relations;

use Illuminate\Support\Str;

class MorphMany extends MorphOne
{
    /**
     * get method name form class
     *
     * @param string $name
     * @return string
     */
    protected function getMethodName($name)
    {
        return Str::plural($name);
    }

    /**
     * get stub method file
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../Stubs/morphMany.stub';
    }
}
