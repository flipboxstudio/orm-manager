<?php 

namespace Flipbox\OrmManager\Relations;

use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Flipbox\OrmManager\ModelManager;
use Illuminate\Database\Eloquent\Model as EloquentModel;

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
