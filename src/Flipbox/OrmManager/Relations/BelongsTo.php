<?php 

namespace Flipbox\OrmManager\Relations;

class BelongsTo extends HasOne
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
		return __DIR__.'/../Stubs/belongsTo.stub';
	}
}
