<?php

return [
    'relying_party' => [
        'id' => env('PASSKEY_RELYING_PARTY_ID', 'localhost'),
        'name' => env('PASSKEY_RELYING_PARTY_NAME', 'Laravel API'),
        'icon' => env('PASSKEY_RELYING_PARTY_ICON'),
    ],

    'timeout' => env('PASSKEY_CHALLENGE_TIMEOUT', 60000),

    'challenge_bytes' => env('PASSKEY_CHALLENGE_BYTES', 32),
];
