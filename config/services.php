<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'otp' => [
        'driver' => env('OTP_DRIVER', 'email'),
        'ttl' => env('OTP_TTL', 300),
    ],

    'passkeys' => [
        'relying_party' => [
            'id' => env('PASSKEY_RELYING_PARTY_ID', 'localhost'),
            'name' => env('PASSKEY_RELYING_PARTY_NAME', 'Laravel API'),
            'icon' => env('PASSKEY_RELYING_PARTY_ICON'),
        ],
    ],
];
