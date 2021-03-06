<?php 

namespace Flipbox\OrmManager;

use Illuminate\Support\ServiceProvider;

class OrmManagerServiceProvider extends ServiceProvider
{
	/**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/orm.php' => base_path('config/orm.php'),
        ], 'config');
    }
	
	/**
     * Register any application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/Config/orm.php', 'orm');

        $this->app->singleton('orm.database', function ($app) {
            return new DatabaseConnection($app['db']);
        });

        $this->app->singleton('orm.manager', function ($app) {
            return new ModelManager($app['config'], $app['orm.database']);
        });

        $this->commands([
            Consoles\ModelList::class,
            Consoles\ModelDetail::class,
            Consoles\ModelConnect::class,
            Consoles\ModelBothConnect::class,
            Consoles\ModelAutoConnect::class,
        ]);
    }
}
