<?php

use Illuminate\Support\Facades\Route;
use Webkul\GoogleFeed\Http\Controllers\GoogleFeedController;

Route::group([
    'middleware' => ['web'],
], function () {
    Route::get('/google-feed.xml', [GoogleFeedController::class, 'index']);
    Route::get('/pinterest-feed.xml', [GoogleFeedController::class, 'pinterest']);
    
    
});