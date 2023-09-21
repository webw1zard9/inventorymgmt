<?php

return [
    'date_format' => 'm/d/y',
    'date_time_format' => 'm/d/y g:ia',
    'transpo_tax_rate' => 0.01,
    'sales_commission_start_date' => '2018-09-01',
    'payment_terms' => [
        '0' => 'Due On Receipt',
        '7' => 'Net 7',
        '15' => 'Net 15',
        '21' => 'Net 21',
        '30' => 'Net 30',
        '45' => 'Net 45',
        '60' => 'Net 60',
    ],
    'location_colors' => [
        'Nest' => [
            'primary' => '#74c479',
            'secondary' => '#66bd6d',
        ],
    ],
    'coingate_api' => env('APP_URL').'/coingate/rates',
    'payment_methods' => [
        'Cash' => [
            'label' => 'Cash',
            'fee_label' => '',
            'fee' => 0,
            'crypto' => 0,
        ],
        'BTC' => [
            'label' => 'BTC (3%)',
            'fee_label' => '3%',
            'fee' => 0.03,
            'crypto' => 1,
        ],
        'ETH' => [
            'label' => 'ETH (3%)',
            'fee_label' => '3%',
            'fee' => 0.03,
            'crypto' => 1,
        ],
        'USDT' => [
            'label' => 'USDT (2%)',
            'fee_label' => '2%',
            'fee' => 0.02,
            'crypto' => 1,
        ],
        'Credit' => [
            'label' => 'Customer Credit',
            'fee_label' => '',
            'fee' => 0,
            'crypto' => 0,
        ],
    ],
    'vendor_payment_methods' => [
        'Cash' => [
            'label' => 'Cash',
            'fee_label' => '',
            'fee' => 0,
            'crypto' => 0,
        ],
        'BTC' => [
            'label' => 'BTC',
            'fee_label' => '',
            'fee' => 0,
            'crypto' => 1,
        ],
        'ETH' => [
            'label' => 'ETH',
            'fee_label' => '',
            'fee' => 0,
            'crypto' => 1,
        ],
    ],
    'crypto_payment_methods' => ['BTC', 'ETH', 'USDT'],
    'uom' => explode(',', env('PRODUCT_UOM', '')),
    'product_type' => explode(',', env('PRODUCT_TYPES', '')),
    'po_statuses' => [
        'open', 'closed', 'voided',
    ],
    'order_statuses' => [
        'hold', 'ready to pack', 'ready for delivery', 'delivered', 'voided',
    ],
//    'conversions' => [
//        'grams_per_ounce' => 28.35,
//        'grams_per_pound' => 453.5924,
//        'oz_per_g' => 0.035274,
//        'oz_per_lb' => 16,
//    ],
    'license_name' => 'Night Owl, Inc.',
    'license_name_DBA' => '',
    'license_number_adult' => '',
    'license_number_med' => '',
    'license' => [
        'legal_name' => 'Night Owl, Inc.',
        'address' => '101 Test Dr.',
        'address2' => 'Los Angeles, CA 90064',
        'adult' => '',
        'med' => '',
    ],
    'vault_log_access' => [

    ],
    'vault_log_sms_ids' => [

    ],
];
