<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,

    'shopify_app' => [
        /**
         * setup webhooks of the application
         */
        'webhooks' => [
            'app/uninstalled',
            'orders/create',
            'orders/updated',
            'fulfillments/create',
            'fulfillments/update',
        ],
        'carrier_services' => [
            /**
             * format of the carrier services:
             * `Name` => `action controller`
             */
            'BoxIt' => 'boxit',
            'Shop&Collect'  => 'shopandcollect'
        ]
    ],

    'boxit_api_url' => 'http://212.199.98.176:5057'
];
