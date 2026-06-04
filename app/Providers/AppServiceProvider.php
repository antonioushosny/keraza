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
            return new MultiUserEloquentProvider($app['hash'], $config['model'], $config['type'] ?? null);
        });

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::head.end',
            fn () => new \Illuminate\Support\HtmlString('
                <style>
                    @media (min-width: 640px) {
                        .fi-pagination-items {
                            display: flex !important;
                        }
                        .fi-pagination-overview {
                            display: inline !important;
                        }
                        .fi-pagination-records-per-page-select:not(.fi-compact) {
                            display: inline !important;
                        }
                        .fi-pagination-records-per-page-select.fi-compact {
                            display: none !important;
                        }
                        .fi-pagination:not(.fi-simple) > .fi-pagination-previous-btn {
                            display: none !important;
                        }
                        .fi-pagination:not(.fi-simple) > .fi-pagination-next-btn {
                            display: none !important;
                        }
                    }
                </style>
            ')
        );
    }
}
