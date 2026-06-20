<?php
// =============================================================
//  routes/payment.php — Razorpay Payment API
//  API prefix: /api/payment
// =============================================================

$path   = $GLOBALS['SUB_PATH'];
$method = $GLOBALS['METHOD'];

// ── Create Razorpay Order ───────────────────────────────────
if ($path === '/create-order' && $method === 'POST') {
    $data = get_request_data();
    $type = $data['type'] ?? ''; // 'primary' or 'student'

    if (!in_array($type, ['primary', 'student'])) {
        err('Invalid membership type. Must be "primary" or "student".');
    }

    $amount = $type === 'primary' ? PRIMARY_MEMBERSHIP_FEE : STUDENT_MEMBERSHIP_FEE;

    require_once __DIR__ . '/../razorpay_helper.php';
    $razorpay = new RazorpayHelper();

    if (!$razorpay->isConfigured()) {
        err('Payment gateway is not configured. Please contact admin.', 500);
    }

    $order = $razorpay->createOrder($amount, 'INR', [
        'membership_type' => $type,
        'source'          => 'aisu_membership_form',
    ]);

    if (!$order || !isset($order['id'])) {
        err('Failed to create payment order. Please try again.', 500);
    }

    ok([
        'order_id' => $order['id'],
        'amount'   => $order['amount'],    // in paise
        'currency' => $order['currency'],
        'key_id'   => RAZORPAY_KEY_ID,
    ]);
}

// ── Verify Payment (server-side) ─────────────────────────────
if ($path === '/verify' && $method === 'POST') {
    $data = get_request_data();
    $orderId   = $data['razorpay_order_id'] ?? '';
    $paymentId = $data['razorpay_payment_id'] ?? '';
    $signature = $data['razorpay_signature'] ?? '';

    if (!$orderId || !$paymentId || !$signature) {
        err('Missing payment verification parameters');
    }

    require_once __DIR__ . '/../razorpay_helper.php';
    $razorpay = new RazorpayHelper();

    if ($razorpay->verifyPayment($orderId, $paymentId, $signature)) {
        // Fetch payment details to confirm amount
        $payment = $razorpay->fetchPayment($paymentId);
        ok([
            'verified'   => true,
            'payment_id' => $paymentId,
            'order_id'   => $orderId,
            'amount'     => $payment['amount'] ?? null,
            'method'     => $payment['method'] ?? null,
        ], 'Payment verified successfully');
    } else {
        err('Payment verification failed. Invalid signature.', 400);
    }
}

err('Endpoint not found', 404);
