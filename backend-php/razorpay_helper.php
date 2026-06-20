<?php
// =============================================================
//  razorpay_helper.php — Razorpay Payment Integration
//  Uses PHP stream wrappers (no cURL required)
// =============================================================

require_once __DIR__ . '/config.php';

class RazorpayHelper {
    private string $keyId;
    private string $keySecret;
    private string $baseUrl = 'https://api.razorpay.com/v1/';

    public function __construct() {
        $this->keyId     = defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : '';
        $this->keySecret = defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : '';
    }

    public function isConfigured(): bool {
        return !empty($this->keyId) && !empty($this->keySecret);
    }

    /**
     * Create a payment order.
     */
    public function createOrder(float $amount, string $currency = 'INR', array $notes = []): ?array {
        if (!$this->isConfigured()) return null;

        $payload = [
            'amount'          => intval(round($amount * 100)), // Razorpay uses paise
            'currency'        => strtoupper($currency),
            'payment_capture' => 1, // Auto-capture
            'notes'           => $notes,
        ];

        return $this->call('POST', 'orders', $payload);
    }

    /**
     * Verify payment signature.
     */
    public function verifyPayment(string $orderId, string $paymentId, string $signature): bool {
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);
        return hash_equals($expected, $signature);
    }

    /**
     * Fetch payment details by payment ID.
     */
    public function fetchPayment(string $paymentId): ?array {
        return $this->call('GET', 'payments/' . $paymentId);
    }

    /**
     * Fetch order details by order ID.
     */
    public function fetchOrder(string $orderId): ?array {
        return $this->call('GET', 'orders/' . $orderId);
    }

    /**
     * Make an HTTP request to Razorpay API using PHP stream wrappers.
     */
    private function call(string $method, string $endpoint, array $data = []): ?array {
        $url = $this->baseUrl . $endpoint;
        $auth = base64_encode($this->keyId . ':' . $this->keySecret);

        $contextOpts = [
            'http' => [
                'method'        => $method,
                'header'        => "Authorization: Basic $auth\r\n" .
                                   "Content-Type: application/json\r\n" .
                                   "Accept: application/json\r\n",
                'timeout'       => 15,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ];

        // Use custom CA cert if available
        $caPath = __DIR__ . '/php/extras/ssl/cacert.pem';
        if (file_exists($caPath)) {
            $contextOpts['ssl']['cafile'] = $caPath;
        } else {
            // Fallback: disable peer verification if no CA bundle
            $contextOpts['ssl']['verify_peer'] = false;
            $contextOpts['ssl']['verify_peer_name'] = false;
        }

        if ($method === 'POST') {
            $contextOpts['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($contextOpts);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            error_log('[RAZORPAY] HTTP Error: ' . ($error['message'] ?? 'Unknown error'));
            return null;
        }

        $result = json_decode($response, true);

        if (!$result || isset($result['error'])) {
            error_log('[RAZORPAY] API Error: ' . json_encode($result['error'] ?? $response));
            return null;
        }

        return $result;
    }
}

/**
 * Convenience function: Create a Razorpay order and return order_id/amount.
 * Used by the payment API endpoint.
 */
function create_razorpay_order(string $membershipType, float $amount): ?array {
    $razorpay = new RazorpayHelper();
    if (!$razorpay->isConfigured()) return null;

    $order = $razorpay->createOrder($amount, 'INR', [
        'membership_type' => $membershipType,
    ]);

    if (!$order || !isset($order['id'])) return null;

    return [
        'order_id' => $order['id'],
        'amount'   => $order['amount'], // in paise
        'currency' => $order['currency'],
        'key_id'   => RAZORPAY_KEY_ID,
    ];
}
