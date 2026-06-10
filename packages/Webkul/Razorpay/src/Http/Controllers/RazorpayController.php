<?php

namespace Webkul\Razorpay\Http\Controllers;

use Illuminate\Routing\Controller;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Razorpay\Api\Api;
use Webkul\Sales\Transformers\OrderResource;

class RazorpayController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Sales\Repositories\OrderRepository  $orderRepository
     * @return void
     */
    public function __construct(protected OrderRepository $orderRepository)
    {
    }

    /**
     * Redirects to the razorpay.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirect()
    {
        $cart = Cart::getCart();

        if (! $cart) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $keyId = core()->getConfigData('sales.payment_methods.razorpay.key_id') ?: env('RAZORPAY_KEY_ID');
        $keySecret = core()->getConfigData('sales.payment_methods.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET');

        if (! $keyId || ! $keySecret) {
            session()->flash('error', 'Razorpay API keys are not configured. Please check your admin configuration or .env file.');
            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            $api = new Api($keyId, $keySecret);

            $orderData = [
                'receipt'         => (string) $cart->id,
                'amount'          => (int) round($cart->grand_total * 100), // Amount in paise
                'currency'        => $cart->cart_currency_code,
                'payment_capture' => 1 // Auto capture
            ];

            $razorpayOrder = $api->order->create($orderData);

            return view('razorpay::checkout.razorpay-redirect', [
                'cart'          => $cart,
                'razorpayOrder' => $razorpayOrder,
                'keyId'         => $keyId,
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Razorpay Error: ' . $e->getMessage());
            return redirect()->route('shop.checkout.cart.index');
        }
    }

    /**
     * Razorpay callback.
     *
     * @return \Illuminate\Http\Response
     */
    public function callback()
    {
        $keyId = core()->getConfigData('sales.payment_methods.razorpay.key_id') ?: env('RAZORPAY_KEY_ID');
        $keySecret = core()->getConfigData('sales.payment_methods.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET');

        $api = new Api($keyId, $keySecret);

        try {
            $attributes = [
                'razorpay_order_id'   => request('razorpay_order_id'),
                'razorpay_payment_id' => request('razorpay_payment_id'),
                'razorpay_signature'  => request('razorpay_signature')
            ];

            $api->utility->verifyPaymentSignature($attributes);

            // Payment successful
            $cart = Cart::getCart();

            if (! $cart) {
                session()->flash('error', 'Cart session expired or not found.');
                return redirect()->route('shop.checkout.cart.index');
            }

            $data = (new OrderResource($cart))->jsonSerialize();

            $order = $this->orderRepository->create($data);
            
            Cart::deActivateCart();

            session()->flash('order_id', $order->id);

            return redirect()->route('shop.checkout.onepage.success');

        } catch (\Exception $e) {
            session()->flash('error', 'Razorpay payment failed: ' . $e->getMessage());
            return redirect()->route('shop.checkout.cart.index');
        }
    }

}
