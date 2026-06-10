<?php

use Illuminate\Support\Facades\Route;
use Webkul\Razorpay\Http\Controllers\RazorpayController;

Route::group(['middleware' => ['web']], function () {
    Route::get('/razorpay-redirect', [RazorpayController::class, 'redirect'])->name('razorpay.payment.redirect');
    Route::post('/razorpay-callback', [RazorpayController::class, 'callback'])->name('razorpay.payment.callback');
});
