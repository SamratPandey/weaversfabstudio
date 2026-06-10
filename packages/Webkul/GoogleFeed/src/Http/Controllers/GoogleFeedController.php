<?php

namespace Webkul\GoogleFeed\Http\Controllers;

use Illuminate\Support\Facades\DB;

class GoogleFeedController
{
    public function index()
    {
         $products = DB::table('product_flat')
            ->leftJoin('product_images', 'product_flat.product_id', '=', 'product_images.product_id')
            ->select(
                'product_flat.*',
                'product_images.path as image'
            )
            ->where('product_flat.status', 1)
            ->where('product_flat.visible_individually', 1)
            ->groupBy('product_flat.product_id')
            ->get();
            //return response()->json($products);

        return response()
            ->view('googlefeed::feed', compact('products'))
            ->header('Content-Type', 'text/xml');
    }
    public function pinterest()
{
    $products = DB::table('product_flat')
        ->leftJoin('product_images', 'product_flat.product_id', '=', 'product_images.product_id')
        ->select(
            'product_flat.*',
            'product_images.path as image'
        )
        ->where('product_flat.status', 1)
        ->where('product_flat.visible_individually', 1)
        ->groupBy('product_flat.product_id')
        ->get();

    return response()
        ->view('googlefeed::pinterest-feed', compact('products'))
        ->header('Content-Type', 'text/xml');
}
}