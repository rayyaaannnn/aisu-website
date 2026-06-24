<?php
// =============================================================
//  sms_service.php — AISU SMS Automation (Twilio)
// =============================================================

require_once __DIR__ . '/config.php';

// Autoload Composer (for Twilio SDK)
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

/**
 * Send an OTP via SMS using Twilio.
 * Falls back gracefully if TWILIO_ACCOUNT_SID is not configured.
 *
 * @param string $phone  Recipient phone number (e.g. '+919876543210')
 * @param string $name   User's name for the message
 * @param string $otp    6-digit OTP code
 * @return bool          True if sent successfully (or logged), false on failure
 */
function send_sms_otp(string $phone, string $name, string $otp): bool {
    // Require at least 10 digits
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) < 10) {
        error_log("[SMS] Invalid phone number: $phone");
        return false;
    }

    // Format to E.164 if not already
    if (strlen($digits) === 10) {
        $phone = '+91' . $digits; // Default to India
    } elseif (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
        $phone = '+' . $digits;
    } elseif ($digits[0] !== '+') {
        $phone = '+' . $digits;
    }

    // Check if Twilio is configured
    if (!TWILIO_ACCOUNT_SID || !TWILIO_AUTH_TOKEN) {
        error_log("[SMS] Twilio not configured. Would send OTP $otp to $phone for $name.");
        return false;
    }

    if (!class_exists(\Twilio\Rest\Client::class)) {
        error_log("[SMS] Twilio SDK not loaded. Install via: composer require twilio/sdk");
        return false;
    }

    try {
        $client = new \Twilio\Rest\Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

        $message = $client->messages->create($phone, [
            'from' => TWILIO_PHONE_NUMBER,
            'body' => "Your AISU password reset OTP is: $otp. It is valid for 2 minutes. Do not share it with anyone. - AISU4India",
        ]);

        error_log("[SMS] OTP sent to $phone | SID: {$message->sid}");
        return true;

    } catch (\Exception $e) {
        error_log("[SMS] FAILED: OTP to $phone | Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mask a phone number for safe API responses (e.g. "+9198******10")
 */
function mask_phone(string $phone): string {
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) >= 10) {
        $country = strlen($digits) > 10 ? '+' . substr($digits, 0, strlen($digits) - 10) . ' ' : '';
        $last4 = substr($digits, -4);
        $prefix = substr($digits, -10, 2);
        return $country . $prefix . '******' . $last4;
    }
    return $phone;
}
