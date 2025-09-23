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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_number' => env('TWILIO_WHATSAPP_FROM'),
        
        'webhook_url' => env('APP_URL') . '/webhooks/twilio',
        'verify_signature' => env('TWILIO_VERIFY_SIGNATURE', true),
        
        // Configuraciones específicas de WhatsApp
        'whatsapp' => [
            'sandbox_mode' => env('TWILIO_WHATSAPP_SANDBOX', false),
            'max_message_length' => 1600,
            'rate_limit_per_minute' => 1, // 1 mensaje por minuto por número
            'verification_expiry_minutes' => 30,
        ],
    ],
];
