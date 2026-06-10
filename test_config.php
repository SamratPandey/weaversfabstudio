<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$core = config('core');
echo "Core config count: " . count($core) . "\n";

$foundInConfig = false;
foreach ($core as $item) {
    if (isset($item['key']) && $item['key'] == 'sales.payment_methods.razorpay') {
        $foundInConfig = true;
    }
}
echo "Found in config('core'): " . ($foundInConfig ? "YES" : "NO") . "\n";

$items = system_config()->getItems();

foreach ($items as $item) {
    if ($item->key == 'sales') {
        foreach ($item->children as $child) {
            if ($child->key == 'sales.payment_methods') {
                echo "Children of sales.payment_methods count: " . $child->children->count() . "\n";
                foreach ($child->children as $grandChild) {
                    echo "  - " . $grandChild->key . " (Name: " . $grandChild->getName() . ")\n";
                }
            }
        }
    }
}
