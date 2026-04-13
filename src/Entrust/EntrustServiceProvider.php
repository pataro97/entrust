<?php

declare(strict_types=1);

namespace Trebol\Entrust;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Trebol\Entrust
 */

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class EntrustServiceProvider extends ServiceProvider
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
    public function boot(): void
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/config.php' => $this->app->configPath('entrust.php'),
        ]);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrationCommand::class,
            ]);
        }

        // Register blade directives
        $this->bladeDirectives();
    }

    /**
     * Register the service provider.
     */
    #[\Override]
    public function register(): void
    {
        $this->registerEntrust();

        $this->registerCommands();

        $this->mergeConfig();
    }

    /**
     * Register the blade directives
     */
    private function bladeDirectives(): void
    {
        if (!class_exists('\Blade')) {
            return;
        }

        // Call to Entrust::hasRole
        Blade::directive('role', fn($expression): string => sprintf('<?php if (\Entrust::hasRole(%s)) : ?>', $expression));

        Blade::directive('endrole', fn($expression): string => "<?php endif; // Entrust::hasRole ?>");

        // Call to Entrust::can
        Blade::directive('permission', fn($expression): string => sprintf('<?php if (\Entrust::can(%s)) : ?>', $expression));

        Blade::directive('endpermission', fn($expression): string => "<?php endif; // Entrust::can ?>");

        // Call to Entrust::ability
        Blade::directive('ability', fn($expression): string => sprintf('<?php if (\Entrust::ability(%s)) : ?>', $expression));

        Blade::directive('endability', fn($expression): string => "<?php endif; // Entrust::ability ?>");
    }

    /**
     * Register the application bindings.
     */
    private function registerEntrust(): void
    {
        $this->app->bind('entrust', fn($app): \Trebol\Entrust\Entrust => new Entrust($app));

        $this->app->alias('entrust', \Trebol\Entrust\Entrust::class);
    }

    /**
     * Register the artisan commands.
     */
    private function registerCommands(): void
    {
        $this->app->singleton(MigrationCommand::class, fn($app): \Trebol\Entrust\MigrationCommand => new MigrationCommand());
        $this->app->alias(MigrationCommand::class, 'command.entrust.migration');
    }

    /**
     * Merges user's and entrust's configs.
     */
    private function mergeConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/config.php', 'entrust'
        );
    }

    /**
     * Get the services provided.
     *
     * @return array
     */
    #[\Override]
    public function provides()
    {
        return [
            MigrationCommand::class,
            'command.entrust.migration'
        ];
    }
}
