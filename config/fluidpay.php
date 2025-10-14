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
        'starter' => env('FLUIDPAY_STARTER_URL', 'https://app.fluidpay.com/spp/starter-plan'),
        'monthly' => env('FLUIDPAY_MONTHLY_URL', 'https://app.fluidpay.com/spp/monthly-plan'),
        'yearly' => env('FLUIDPAY_YEARLY_URL', 'https://app.fluidpay.com/spp/yearly-plan'),
    ],

    'webhook_secret' => env('FLUIDPAY_WEBHOOK_SECRET'),
    'webhook_url' => env('APP_URL') . '/api/webhooks/fluidpay',

    'plans' => [
        'starter' => [
            'id' => 'starter',
            'name' => 'Starter Plan',
            'amount' => 17.00,
            'duration' => 'monthly',
            'events_limit' => 2,
            'features' => [
                'Join 2 events per month',
                'Access to community features',
                'Email notifications',
                'Event calendar access',
                'Mobile app access',
            ],
        ],
         'monthly' => [
            'id' => 'monthly',
            'name' => 'Monthly Unlimited',
            'amount' => 24.00,
            'duration' => 'monthly',
            'events_limit' => 'unlimited',
            'features' => [
                'Unlimited events per month',
                'Priority event registration',
                'All Starter features',
                'Advanced search filters',
                'Priority customer support',
                'Event reminders',
            ],
        ],
        'yearly' => [
            'id' => 'yearly',
            'name' => 'Yearly Unlimited',
            'amount' => 247.00,
            'duration' => 'yearly',
            'events_limit' => 'unlimited',
            'features' => [
                'Unlimited events for 1 year',
                'Best value - Save $41/year',
                'All Monthly Unlimited features',
                'Exclusive yearly member badge',
                'Early access to new features',
                'VIP customer support',
                'Annual member perks',
            ],
        ],
    ],
];