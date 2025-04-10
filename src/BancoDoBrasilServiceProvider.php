<?php

namespace WandersonBarradas\BancoDoBrasil;

use Illuminate\Support\ServiceProvider;
use WandersonBarradas\BancoDoBrasil\Services\BoletoService;
use WandersonBarradas\BancoDoBrasil\Services\AuthService;

class BancoDoBrasilServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços do pacote.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/banco-do-brasil.php',
            'banco-do-brasil'
        );

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService(
                config('banco-do-brasil.client_id'),
                config('banco-do-brasil.client_secret'),
                config('banco-do-brasil.developer_key'),
                config('banco-do-brasil.environment'),
                config('banco-do-brasil.cache_method')
            );
        });

        $this->app->singleton(BoletoService::class, function ($app) {
            return new BoletoService(
                $app->make(AuthService::class),
                config('banco-do-brasil')
            );
        });

        $this->app->bind('bb-boleto', function ($app) {
            return $app->make(BoletoService::class);
        });
    }

    /**
     * Inicializa os serviços do pacote.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/banco-do-brasil.php' => config_path('banco-do-brasil.php'),
        ], 'config');
    }
}
