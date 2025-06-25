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
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bird' => [
        'api_key' => env('BIRD_API_KEY', ''),
        'api_url' => env('BIRD_API_URL', 'https://go.messagebird.com/1/messages'),
    ],

    'messagebird' => [
        'api_key' => env('BIRD_API_KEY_WA'),
        'webhooks' => [
            'appointment_flow_webhook' => env('FLOW_APPOINMENT_WEBHOOK_URL'),
            'appointment_flow_webhook_header' => env('FLOW_APPOINMENT_WEBHOOK_API_HEADER'),
            'appointment_flow_webhook_secret' => env('FLOW_APPOINMENT_WEBHOOK_API_KEY'),
        ],
        'chat_key' => env('CHAT_KEY'),
    ],

];
