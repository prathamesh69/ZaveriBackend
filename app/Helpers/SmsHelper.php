<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SmsHelper
{
    public static function sendTransactional($mobile, $message)
    {
        if (!env('OTP_ENABLED')) {
            return 'success';
        }

        $json = [
            'sender' => env('MSG91_SENDER'), 'route' => '4', 'country' => '91',
            'unicode' => '1', 'sms' => [
                ['message' => $message, 'to' => [$mobile]]
            ]
        ];

        $headers = [
            'authkey' => env('MSG91_AUTH_KEY'),
            'Content-Type' => 'application/json',
        ];

        $client = new Client();
        $response = $client->request('POST', 'https://api.msg91.com/api/v2/sendsms', [
            'headers' => $headers, 'json' => $json,
            'http_errors' => false,
        ]);

        $result = null;
        if ($response->getStatusCode() == 200) {
            $result = json_decode((string) $response->getBody(), true);
        } else {
            $result = ['type' => 'failure'];
            Log::error(['error' => 'MSG91 OTP error', 'data' => [
                "status code" => $response->getStatusCode(),
                "reason" => $response->getReasonPhrase(),
                "body" => (string) $response->getBody(),
            ]]);
        }

        return $result['type'];
    }

    public static function sendOTP($mobile, $message, $otp)
    {
        if (!env('OTP_ENABLED')) {
            return 'success';
        }

        $query = [
            'sender' => env('MSG91_SENDER'), 'message' => $message, 'mobile' => $mobile, 'otp' => $otp,
            'authkey' => env('MSG91_AUTH_KEY'), 'unicode' => '1'
        ];

        $client = new Client();
        $response = $client->request('GET', 'http://control.msg91.com/api/sendotp.php', [
            'query' => $query, 'http_errors' => false,
        ]);

        $result = null;
        if ($response->getStatusCode() == 200) {
            $result = json_decode((string) $response->getBody(), true);
        } else {
            $result = ['type' => 'failure'];
            Log::error(['error' => 'MSG91 OTP error', 'data' => [
                "status code" => $response->getStatusCode(),
                "reason" => $response->getReasonPhrase(),
                "body" => (string) $response->getBody(),
            ]]);
        }

        return $result['type'];
    }
}
