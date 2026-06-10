<?php

namespace App\Listeners;

use App\Services\ShiprocketService;
use Illuminate\Support\Facades\Log;

class ShiprocketOrderListener
{
    /**
     * Create a new listener instance.
     *
     * @param  \App\Services\ShiprocketService  $shiprocketService
     * @return void
     */
    public function __construct(protected ShiprocketService $shiprocketService)
    {
    }

    /**
     * Handle the checkout.order.save.after event.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     * @return void
     */
    public function handle($order)
    {
        try {
            Log::info('[Shiprocket] Order Listener triggered for Order #' . ($order->increment_id ?: $order->id));
            
            // Only process if order contains physical items to ship
            if (!$order->shipping_address) {
                Log::info('[Shiprocket] Order #' . ($order->increment_id ?: $order->id) . ' has no shipping address. Skipping.');
                return;
            }

            $this->shiprocketService->createOrder($order);
        } catch (\Exception $e) {
            Log::error('[Shiprocket] Error in listener for Order #' . ($order->increment_id ?: $order->id) . ': ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }
}
