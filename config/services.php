<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'transaction_image_api' => [
        'url' => env('TRANSACTION_IMAGE_API_URL'),
        'key' => env('TRANSACTION_IMAGE_API_KEY'),
        'field' => env('TRANSACTION_IMAGE_API_FIELD', 'image'),
    ],

    'manual_response_api' => [
        'url' => env('MANUAL_RESPONSE_API_URL'),
        'key' => env('MANUAL_RESPONSE_API_KEY'),
        'field' => env('MANUAL_RESPONSE_API_FIELD', 'image'),
    ],

    'manual_failed_response_api' => [
        'url' => env('MANUAL_FAILED_RESPONSE_API_URL'),
        'key' => env('MANUAL_FAILED_RESPONSE_API_KEY'),
    ],

];
