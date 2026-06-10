<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Listeners\ShiprocketOrderListener;

class ShiprocketServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge our system settings into Bagisto's core configuration system
        $this->mergeConfigFrom(
            base_path('config/shiprocket_system.php'),
            'core'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Listen to checkout order save after event
        Event::listen('checkout.order.save.after', [ShiprocketOrderListener::class, 'handle']);
    }
}
