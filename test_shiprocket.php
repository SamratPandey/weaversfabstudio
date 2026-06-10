<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use App\Services\ShiprocketService;

echo "--- ADDRESS COLUMNS ---\n";
print_r(\Illuminate\Support\Facades\Schema::getColumnListing('addresses'));
echo "\n";

echo "--- CHECKING CONFIGURATION ---\n";
$configs = config('core') ?: [];
$found = false;
foreach ($configs as $c) {
    if (isset($c['key']) && strpos($c['key'], 'shiprocket') !== false) {
        echo "Found Key: " . $c['key'] . " | Name: " . $c['name'] . "\n";
        $found = true;
    }
}
if (!$found) {
    echo "Shiprocket config key NOT found in core config!\n";
}

echo "\n--- RUNNING MOCK ORDER GENERATION (DATABASE TRANSACTION) ---\n";

DB::beginTransaction();

try {
    // Let's create an order, address, and items in the database, test them, and then rollback.
    // We will build objects manually if factories need configuration.
    
    // 1. Create a dummy order
    $order = new Order();
    $order->increment_id = '999999';
    $order->status = 'pending';
    $order->customer_email = 'customer@example.com';
    $order->customer_first_name = 'John';
    $order->customer_last_name = 'Doe';
    $order->sub_total = 1200.00;
    $order->grand_total = 1250.00;
    $order->save();

    // 2. Create Order Payment
    $payment = new \Webkul\Sales\Models\OrderPayment();
    $payment->method = 'cashondelivery';
    $payment->order_id = $order->id;
    $payment->save();

    // 3. Create Billing Address
    $billingAddress = new OrderAddress();
    $billingAddress->order_id = $order->id;
    $billingAddress->address_type = OrderAddress::ADDRESS_TYPE_BILLING;
    $billingAddress->first_name = 'John';
    $billingAddress->last_name = 'Doe';
    $billingAddress->address = "Flat 101, Galaxy Apartments\nNear Market";
    $billingAddress->city = 'Mumbai';
    $billingAddress->state = 'MH'; // State code or name
    $billingAddress->country = 'IN';
    $billingAddress->postcode = '400001';
    $billingAddress->email = 'billing@example.com';
    $billingAddress->phone = '9876543210';
    $billingAddress->save();

    // 4. Create Shipping Address
    $shippingAddress = new OrderAddress();
    $shippingAddress->order_id = $order->id;
    $shippingAddress->address_type = OrderAddress::ADDRESS_TYPE_SHIPPING;
    $shippingAddress->first_name = 'John';
    $shippingAddress->last_name = 'Doe';
    $shippingAddress->address = "Flat 101, Galaxy Apartments\nNear Market";
    $shippingAddress->city = 'Mumbai';
    $shippingAddress->state = 'MH';
    $shippingAddress->country = 'IN';
    $shippingAddress->postcode = '400001';
    $shippingAddress->email = 'shipping@example.com';
    $shippingAddress->phone = '9876543210';
    $shippingAddress->save();

    // 5. Create Order Item
    $item = new OrderItem();
    $item->order_id = $order->id;
    $item->name = 'Swadesi Cotton Saree';
    $item->sku = 'SAREE-COT-001';
    $item->qty_ordered = 2;
    $item->price = 600.00;
    $item->total = 1200.00;
    $item->weight = 0.75;
    $item->save();

    // Reload relationships
    $order->load(['addresses', 'items', 'payment']);

    echo "Items count loaded: " . count($order->items) . "\n";
    echo "Billing address state raw: " . var_export($order->billing_address->state, true) . "\n";
    $billingState = core()->findStateByCountryCode($order->billing_address->country, $order->billing_address->state);
    echo "Billing state object returned: " . var_export($billingState, true) . "\n";
    $billingStateName = ($billingState ? ($billingState->name ?: $billingState->default_name) : null) ?: $order->billing_address->state;
    echo "Billing state name resolved: " . var_export($billingStateName, true) . "\n";
    
    $service = new ShiprocketService();
    $payload = $service->buildPayload($order);
    
    echo "\nGenerated Shiprocket Payload:\n";
    echo json_encode($payload, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "Error during test order generation: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    // ALWAYS rollback so database is not polluted!
    DB::rollBack();
    echo "\nDatabase transaction rolled back. Database remains clean.\n";
}
