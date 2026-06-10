<?php

namespace Webkul\GoogleFeed\Providers;

use Illuminate\Support\ServiceProvider;

class GoogleFeedServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'googlefeed');
    }

    public function register()
    {
        //
    }
}