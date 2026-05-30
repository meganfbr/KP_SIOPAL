<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BladeUI\Icons\Factory;

use Illuminate\Support\Facades\Event;
use App\Listeners\LogAuthenticationActions;
use App\Models\LaporanPerbaikan;
use App\Observers\LaporanPerbaikanObserver;

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
        Event::subscribe(LogAuthenticationActions::class);

        // Register Observers
        LaporanPerbaikan::observe(LaporanPerbaikanObserver::class);

        $this->callAfterResolving(Factory::class, function (Factory $factory) {
            $factory->add('img', [
                'path' => base_path('img'),
                'prefix' => 'img',
            ]);
        });
    }
}
