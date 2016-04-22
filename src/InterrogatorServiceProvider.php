<?php

namespace MetricLoop\Interrogator;


use \Illuminate\Support\ServiceProvider;

class InterrogatorServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishConfig();
        $this->publishMigration();
    }

    /**
     * Publish Interrogator configuration.
     */
    protected function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/interrogator.php' => config_path('interrogator.php'),
        ]);
    }

    /**
     * Publish Interrogator migration.
     */
    protected function publishMigration()
    {
        $published_migration = glob(database_path('/migrations/*_interrogator_tables.php'));
        if(count($published_migration) === 0) {
            $this->publishes([
                __DIR__ . '/database/migrations.stub' => database_path('/migrations/' . date('Y_m_d_His') . '_interrogator_tables.php'),
            ], 'migrations');
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfig();
        $this->registerInterrogator();
        $this->registerFacade();
    }

    /**
     * Register application bindings.
     */
    protected function registerInterrogator()
    {
        $this->app->bind('interrogator', function ($app) {
            return new Interrogator($app);
        });
    }

    /**
     * Register the facade without the user having to add it to app.php.
     */
    public function registerFacade()
    {
        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Interrogator', 'Facades\Interrogator');
        });
    }

    /**
     * Merges user's and Interrogator's configs.
     */
    protected function mergeConfig()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/interrogator.php', 'interrogator'
        );
    }
}