<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextLkSmsService
{
    /**
     * Send an SMS via Text.lk API v3.
     *
     * @param string $recipient Raw phone number (e.g. 0771234567, +94771234567)
     * @param string $message   SMS message text
     * @param bool   $isFlash   Send as high-priority Flash SMS (ideal for OTPs)
     * @return array ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public static function sendSms(string $recipient, string $message, bool $isFlash = false): array
    {
        $enabled = config('services.textlk.enabled', true);
        $apiToken = config('services.textlk.api_token') ?? env('TEXTLK_API_TOKEN', '246|EmUsVd5IT0gSxUSCpvUV8GUGr061V9TTNew968J245d6a230');
        $senderId = config('services.textlk.sender_id') ?? env('TEXTLK_SENDER_ID', 'TextLKDemo');

        if (!$enabled) {
            Log::info("[TextLk] SMS disabled in config. Message for {$recipient}: {$message}");
            return ['success' => true, 'message' => 'SMS disabled in config (dry run).', 'data' => null];
        }

        if (empty($apiToken)) {
            Log::warning("[TextLk] Missing API token. Message not sent.");
            return ['success' => false, 'message' => 'Text.lk API token not configured.', 'data' => null];
        }

        $formattedNumber = self::formatPhoneNumber($recipient);
        if (!$formattedNumber) {
            Log::warning("[TextLk] Invalid Sri Lankan phone number: {$recipient}");
            return ['success' => false, 'message' => "Invalid Sri Lankan phone number: {$recipient}", 'data' => null];
        }

        try {
            $payload = [
                'recipient' => $formattedNumber,
                'sender_id' => $senderId,
                'type'      => 'plain',
                'message'   => $message,
            ];

            if ($isFlash) {
                $payload['is_flash'] = true;
            }

            $response = Http::withToken($apiToken)
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10)
                ->post('https://app.text.lk/api/v3/sms/send', $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === 'success') {
                Log::info("[TextLk] SMS sent successfully to {$formattedNumber}. UID: " . ($data['data']['uid'] ?? 'N/A'));
                return ['success' => true, 'message' => 'SMS sent successfully.', 'data' => $data];
            }

            Log::error("[TextLk] SMS failed to {$formattedNumber}. Response: " . $response->body());
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to send SMS via Text.lk',
                'data'    => $data,
            ];
        } catch (\Throwable $e) {
            Log::error("[TextLk] Exception while sending SMS to {$formattedNumber}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'SMS service communication exception: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Send OTP Verification code to user's mobile.
     */
    public static function sendOtp(string $phone, string $otp): array
    {
        $message = "Your The Menu verification code is: {$otp}. Valid for 15 minutes. Do not share this code.";
        return self::sendSms($phone, $message, true);
    }

    /**
     * Send Order Confirmation SMS.
     */
    public static function sendOrderConfirmation(string $phone, string $orderNumber, float $amount, ?string $customerName = null): array
    {
        $nameGreeting = !empty($customerName) ? "Dear {$customerName}, " : "";
        $formattedAmount = number_format($amount, 2);
        $message = "{$nameGreeting}your order #{$orderNumber} (Rs. {$formattedAmount}) has been confirmed by The Menu Kitchen. Track your order in the app!";
        return self::sendSms($phone, $message);
    }

    /**
     * Send Order Status Update SMS (Preparing, Out for Delivery, Delivered).
     */
    public static function sendOrderStatusUpdate(string $phone, string $orderNumber, string $status, ?string $driverName = null): array
    {
        $statusKey = strtolower(str_replace([' ', '-'], '_', $status));

        switch ($statusKey) {
            case 'out_for_delivery':
            case 'on_the_way':
                $driverText = !empty($driverName) ? " Driver {$driverName} is on the way." : "";
                $message = "The Menu: Your order #{$orderNumber} is OUT FOR DELIVERY!{$driverText} Please be ready to receive your meal.";
                break;

            case 'delivered':
            case 'completed':
                $message = "The Menu: Your order #{$orderNumber} has been DELIVERED. Enjoy your meal! Thank you for ordering with us.";
                break;

            case 'preparing':
            case 'in_kitchen':
                $message = "The Menu: Chef is now PREPARING your order #{$orderNumber} fresh in our central kitchen.";
                break;

            case 'cancelled':
                $message = "The Menu: Your order #{$orderNumber} has been cancelled. If you have questions, please contact support.";
                break;

            default:
                $message = "The Menu: Your order #{$orderNumber} status has been updated to: " . ucfirst($status) . ".";
                break;
        }

        return self::sendSms($phone, $message);
    }

    /**
     * Send Event Catering Inquiry Confirmation SMS.
     */
    public static function sendCateringInquiryConfirmation(string $phone, string $eventName, ?string $customerName = null): array
    {
        $nameGreeting = !empty($customerName) ? "Dear {$customerName}, " : "";
        $message = "{$nameGreeting}thank you for your event catering request for '{$eventName}'. The Menu team will contact you shortly with a personalized quote!";
        return self::sendSms($phone, $message);
    }

    /**
     * Normalize phone numbers to 947XXXXXXXX international format required by Text.lk.
     *
     * Supports:
     * - 0771234567  -> 94771234567
     * - +94771234567 -> 94771234567
     * - 771234567   -> 94771234567
     * - 94771234567 -> 94771234567
     */
    public static function formatPhoneNumber(string $phone): ?string
    {
        // Remove all non-numeric characters (+, -, spaces, parentheses)
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Leading 0: e.g. 0771234567 (10 digits) -> 94771234567
        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 10) {
            $cleaned = '94' . substr($cleaned, 1);
        }

        // Without 0 or 94: e.g. 771234567 (9 digits) -> 94771234567
        if (strlen($cleaned) === 9 && str_starts_with($cleaned, '7')) {
            $cleaned = '94' . $cleaned;
        }

        // Valid Sri Lankan mobile: 11 digits starting with 947
        if (strlen($cleaned) === 11 && str_starts_with($cleaned, '947')) {
            return $cleaned;
        }

        return null;
    }
}
