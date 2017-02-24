<?php 

namespace Flipbox\OrmManager;

use Flipbox\OrmManager\Console;
use Illuminate\Support\ServiceProvider;

class OrmManagerServiceProvider extends ServiceProvider
{
	/**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/orm.php' => config_path('orm.php'),
        ], 'config');
    }
	
	/**
     * Register any application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/Config/orm.php', 'orm');

    	$this->registerCommand();
    }

    /**
     * register commands
     *
     * @return void
     */
    protected function registerCommand()
    {
    	$this->commands([
            Consoles\ModelList::class,
    		Consoles\ModelDetail::class,
            Consoles\ModelConnect::class,
            Consoles\ModelBothConnect::class,
    	]);
    }
}
