<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Webkul\Product\Models\ProductFlat;

class GenerateSitemap extends Command
{
    protected $signature = 'generate:sitemap';

    protected $description = 'Generate sitemap';

    public function handle()
    {
        $sitemap = Sitemap::create();

        $products = ProductFlat::where('status', 1)
            ->where('visible_individually', 1)
            ->get();

        foreach ($products as $product) {

            if ($product->url_key) {

                $sitemap->add(
                    Url::create(url('/' . $product->url_key))
                );
            }
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully!');
    }
}