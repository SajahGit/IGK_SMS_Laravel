<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'payhere' => [
        'enabled' => env('PAYHERE_ENABLED', false),
        'merchant_id' => env('PAYHERE_MERCHANT_ID'),
        'merchant_secret' => env('PAYHERE_MERCHANT_SECRET'),
        'sandbox' => env('PAYHERE_SANDBOX', true),
        'currency' => env('PAYHERE_CURRENCY', 'LKR'),
    ],

    'sms' => [
        'enabled' => env('SMS_ENABLED', false),
        'endpoint' => env('SMS_API_URL'),
        'token' => env('SMS_API_TOKEN'),
        'sender_id' => env('SMS_SENDER_ID'),
        'timeout' => env('SMS_TIMEOUT', 10),
    ],

];
