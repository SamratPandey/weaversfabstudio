<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShiprocketService
{
    /**
     * Authenticate and get Bearer Token.
     * Caches the token to avoid repeated authentication requests.
     *
     * @return string|null
     */
    protected function authenticate()
    {
        $email = core()->getConfigData('sales.shiprocket.settings.email');
        $password = core()->getConfigData('sales.shiprocket.settings.password');

        if (empty($email) || empty($password)) {
            Log::error('[Shiprocket] API credentials are not configured.');
            return null;
        }

        $cacheKey = 'shiprocket_token_' . md5($email);

        // Cache token for 8 days (Shiprocket tokens expire in 10 days)
        return Cache::remember($cacheKey, now()->addDays(8), function () use ($email, $password) {
            try {
                $response = Http::post('https://apiv2.shiprocket.in/v1/external/auth/login', [
                    'email'    => $email,
                    'password' => $password,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['token'] ?? null;
                }

                Log::error('[Shiprocket] Authentication failed', [
                    'status' => $response->status(),
                    'body'   => $response->body()
                ]);
            } catch (\Exception $e) {
                Log::error('[Shiprocket] Authentication Exception: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Create an order in Shiprocket.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     * @return array|bool
     */
    public function createOrder($order)
    {
        if (!core()->getConfigData('sales.shiprocket.settings.active')) {
            Log::info('[Shiprocket] Integration is disabled.');
            return false;
        }

        $token = $this->authenticate();
        if (!$token) {
            Log::error('[Shiprocket] Order creation aborted due to authentication failure.');
            return false;
        }

        $payload = $this->buildPayload($order);
        
        Log::info('[Shiprocket] Sending order creation request', [
            'order_id' => $order->id,
            'payload'  => $payload
        ]);

        try {
            $response = Http::withToken($token)
                ->post('https://apiv2.shiprocket.in/v1/external/orders/create/adhoc', $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('[Shiprocket] Order created successfully in Shiprocket', [
                    'bagisto_order_id'      => $order->id,
                    'shiprocket_order_id'   => $data['order_id'] ?? null,
                    'shiprocket_shipment_id'=> $data['shipment_id'] ?? null,
                    'shiprocket_response'   => $data
                ]);
                return $data;
            }

            Log::error('[Shiprocket] Order creation failed', [
                'bagisto_order_id' => $order->id,
                'status'           => $response->status(),
                'response'         => $response->json() ?? $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('[Shiprocket] Order creation Exception', [
                'bagisto_order_id' => $order->id,
                'message'          => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Build Shiprocket API Order creation payload.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     * @return array
     */
    public function buildPayload($order)
    {
        $billingAddress  = $order->billing_address;
        $shippingAddress = $order->shipping_address ?: $billingAddress;

        // Split address lines
        $billingAddressLines = explode("\n", $billingAddress->address);
        $billingAddress1 = trim($billingAddressLines[0] ?? '');
        $billingAddress2 = trim($billingAddressLines[1] ?? '');

        $shippingAddressLines = explode("\n", $shippingAddress->address);
        $shippingAddress1 = trim($shippingAddressLines[0] ?? '');
        $shippingAddress2 = trim($shippingAddressLines[1] ?? '');

        // Fetch state name if code is provided
        $billingState = core()->findStateByCountryCode($billingAddress->country, $billingAddress->state);
        $billingStateName = ($billingState ? ($billingState->name ?: $billingState->default_name) : null) ?: $billingAddress->state;

        $shippingState = core()->findStateByCountryCode($shippingAddress->country, $shippingAddress->state);
        $shippingStateName = ($shippingState ? ($shippingState->name ?: $shippingState->default_name) : null) ?: $shippingAddress->state;

        // Fetch country name
        $billingCountryName  = core()->country_name($billingAddress->country) ?: $billingAddress->country;
        $shippingCountryName = core()->country_name($shippingAddress->country) ?: $shippingAddress->country;

        // Determine Payment Method (COD vs Prepaid)
        $paymentMethod = (strtolower($order->payment->method) === 'cashondelivery') ? 'COD' : 'Prepaid';

        // Prepare Order Items
        $orderItems = [];
        foreach ($order->items as $item) {
            $orderItems[] = [
                'name'          => $item->name,
                'sku'           => $item->sku,
                'units'         => (int) $item->qty_ordered,
                'selling_price' => (float) $item->price,
                'discount'      => (float) $item->discount_amount,
                'tax'           => (float) $item->tax_amount,
                'hsn'           => '',
            ];
        }

        // Configure Package Weight and Dimensions
        $defaultWeight  = (float) core()->getConfigData('sales.shiprocket.settings.default_weight') ?: 0.5;
        $defaultLength  = (float) core()->getConfigData('sales.shiprocket.settings.default_length') ?: 10;
        $defaultBreadth = (float) core()->getConfigData('sales.shiprocket.settings.default_breadth') ?: 10;
        $defaultHeight  = (float) core()->getConfigData('sales.shiprocket.settings.default_height') ?: 10;

        // Calculate total weight of the order from its items
        $orderWeight = 0;
        foreach ($order->items as $item) {
            $orderWeight += (float) ($item->total_weight ?: ($item->weight * $item->qty_ordered));
        }

        // Convert weight from store weight unit to kgs if store unit is lbs
        $weightUnit = core()->getConfigData('general.general.locale_options.weight_unit') ?: 'kgs';
        if ($orderWeight <= 0) {
            $orderWeight = $defaultWeight;
        } elseif (strtolower($weightUnit) === 'lbs') {
            $orderWeight = $orderWeight * 0.45359237;
        }

        $payload = [
            'order_id'            => $order->increment_id ?: $order->id,
            'order_date'          => $order->created_at->format('Y-m-d H:i'),
            'pickup_location'     => core()->getConfigData('sales.shiprocket.settings.pickup_location') ?: 'Primary',
            'comment'             => 'Order #' . ($order->increment_id ?: $order->id),
            'billing_customer_name'=> $billingAddress->first_name,
            'billing_last_name'   => $billingAddress->last_name,
            'billing_address'     => $billingAddress1,
            'billing_address_2'   => $billingAddress2,
            'billing_city'        => $billingAddress->city,
            'billing_pincode'     => $billingAddress->postcode,
            'billing_state'       => $billingStateName,
            'billing_country'     => $billingCountryName,
            'billing_email'       => $billingAddress->email ?: ($order->customer_email ?: 'no-email@example.com'),
            'billing_phone'       => $billingAddress->phone ?: '0000000000',
            
            'shipping_is_billing' => false,
            'shipping_customer_name' => $shippingAddress->first_name,
            'shipping_last_name'  => $shippingAddress->last_name,
            'shipping_address'    => $shippingAddress1,
            'shipping_address_2'  => $shippingAddress2,
            'shipping_city'       => $shippingAddress->city,
            'shipping_pincode'    => $shippingAddress->postcode,
            'shipping_state'      => $shippingStateName,
            'shipping_country'    => $shippingCountryName,
            'shipping_email'      => $shippingAddress->email ?: ($order->customer_email ?: 'no-email@example.com'),
            'shipping_phone'      => $shippingAddress->phone ?: '0000000000',

            'order_items'         => $orderItems,
            'payment_method'      => $paymentMethod,
            'sub_total'           => (float) $order->sub_total,
            'length'              => $defaultLength,
            'breadth'             => $defaultBreadth,
            'height'              => $defaultHeight,
            'weight'              => $orderWeight,
        ];

        // Optional Channel ID
        $channelId = core()->getConfigData('sales.shiprocket.settings.channel_id');
        if (!empty($channelId)) {
            $payload['channel_id'] = $channelId;
        }

        return $payload;
    }
}
