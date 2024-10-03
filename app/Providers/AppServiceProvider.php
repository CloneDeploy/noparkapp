<?php

namespace App\Providers;

use App\Models\User;
use Torann\GeoIP\Facades\GeoIP;
use Illuminate\Support\Facades\Gate;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('GeoIP', GeoIP::class);
        $loader->alias('QrCode', QrCode::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        URL::forceScheme('https');
        // FilamentAsset::register([
        //     Js::make('custom-script', __DIR__ . '/../../resources/js/custom.js'),
        // ]);
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START, fn (): View => view('livewire.custom-head'),
        );
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END, fn (): View => view('livewire.custom-javascript'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, fn (): View => view('livewire.custom-head'),
        );
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE, fn (): View => view('livewire.custom-head'),
        );

        // Gate::before(function ($user, $ability) {
        //     return $user->hasRole('Super Admin') ? true : null;
        // });
        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true: null;
        });

    }
}
