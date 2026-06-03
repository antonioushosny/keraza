<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\Facades\Auth;
use App\Auth\MultiUserEloquentProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentIcon::register([
            'forms::components.text-input.actions.show-password' => 'heroicon-o-eye-slash',
            'forms::components.text-input.actions.hide-password' => 'heroicon-o-eye',
        ]);

        Auth::provider('multi-user-eloquent', function ($app, array $config) {
            return new MultiUserEloquentProvider($app['hash'], $config['model']);
        });
    }
}
