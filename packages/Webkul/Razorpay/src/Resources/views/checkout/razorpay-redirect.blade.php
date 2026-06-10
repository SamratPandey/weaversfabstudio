<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to Razorpay...</title>
    <style>
        .loader {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            font-family: sans-serif;
        }
    </style>
</head>
<body>
    <div class="loader">
        <h2>Please wait...</h2>
        <p>Redirecting to Razorpay payment gateway.</p>
    </div>

    <form id="razorpay-payment-form" action="{{ route('razorpay.payment.callback') }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
    </form>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        var options = {
            "key": "{{ $keyId }}",
            "amount": "{{ $razorpayOrder['amount'] }}",
            "currency": "{{ $razorpayOrder['currency'] }}",
            "name": "{{ config('app.name') }}",
            "description": "Order Payment",
            "image": "{{ asset('vendor/webkul/ui/assets/images/logo.png') }}",
            "order_id": "{{ $razorpayOrder['id'] }}",
            "handler": function (response){
                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                document.getElementById('razorpay_signature').value = response.razorpay_signature;
                document.getElementById('razorpay-payment-form').submit();
            },
            "prefill": {
                "name": "{{ $cart->customer_full_name }}",
                "email": "{{ $cart->customer_email }}",
                "contact": "{{ $cart->billing_address->phone }}"
            },
            "theme": {
                "color": "#ff7529"
            },
            "modal": {
                "ondismiss": function() {
                    window.location.href = "{{ route('shop.checkout.cart.index') }}";
                }
            }
        };
        var rzp1 = new Razorpay(options);
        rzp1.open();
    </script>
</body>
</html>

