<?php
// config/fluidpay.php

return [
    'api_key' => env('FLUIDPAY_API_KEY'),
    'merchant_id' => env('FLUIDPAY_MERCHANT_ID'),
    'environment' => env('FLUIDPAY_ENVIRONMENT', 'sandbox'),

    'api_url' => env('FLUIDPAY_ENVIRONMENT', 'sandbox') === 'production'
        ? 'https://api.fluidpay.com/api'
        : 'https://sandbox.fluidpay.com/api',

    'payment_urls' => [
        'basic' => env('FLUIDPAY_BASIC_URL', 'https://app.fluidpay.com/spp/basic-plan'),
        'pro' => env('FLUIDPAY_PRO_URL', 'https://app.fluidpay.com/spp/pro-plan'),
        'premium' => env('FLUIDPAY_PREMIUM_URL', 'https://app.fluidpay.com/spp/premium-plan'),
    ],

    'webhook_secret' => env('FLUIDPAY_WEBHOOK_SECRET'),
    'webhook_url' => env('APP_URL') . '/api/webhooks/fluidpay',

    'plans' => [
        'basic' => [
            'id' => 'basic',
            'name' => 'Basic Plan',
            'amount' => 29.00,
            'features' => [
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
            ],
        ],
        'pro' => [
            'id' => 'pro',
            'name' => 'Pro Plan',
            'amount' => 79.00,
            'features' => [
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
            ],
        ],
        'premium' => [
            'id' => 'premium',
            'name' => 'Premium Plan',
            'amount' => 149.00,
            'features' => [
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
                'Lorem Ipsum features',
            ],
        ],
    ],
];