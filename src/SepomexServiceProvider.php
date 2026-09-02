<?php

namespace Wafto\Sepomex;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Wafto\Sepomex\Console\ImporterCommand;
use Wafto\Sepomex\Contracts\SepomexContract;
use Wafto\Sepomex\Models\Sepomex;
use Wafto\Sepomex\Repositories\CachedRepository;
use Wafto\Sepomex\Repositories\DatabaseRepository;

class SepomexServiceProvider extends ServiceProvider {
    public function boot(): void {
        $configPath = __DIR__.'/../config/sepomex.php';
        $this->publishes([$configPath => config_path('sepomex.php')], 'sepomex');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole())
            $this->commands([ImporterCommand::class]);
    }

    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../config/sepomex.php', 'sepomex');

        $this->app->singleton(SepomexContract::class, function ($app) {
            return new CachedRepository(
                new DatabaseRepository(new Sepomex),
                $app[CacheRepository::class]
            );
        });
    }
}
