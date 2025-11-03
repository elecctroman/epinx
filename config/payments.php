<?php

use App\Core\Env;
return [
    'providers' => [
        'manual' => [
            'name' => 'Manual Payment',
            'description' => 'Record manual payments received via offline channels.',
        ],
        'paytr' => [
            'name' => 'PayTR',
            'merchant_id' => Env::get('PAYTR_MERCHANT_ID'),
            'merchant_key' => Env::get('PAYTR_MERCHANT_KEY'),
            'merchant_salt' => Env::get('PAYTR_MERCHANT_SALT'),
            'endpoint' => 'https://www.paytr.com/odeme/api/',
            'debug' => Env::get('PAYTR_DEBUG', false),
        ],
        'iyzico' => [
            'name' => 'Iyzico',
        ],
        'param' => [
            'name' => 'ParamPOS',
        ],
        'papara' => [
            'name' => 'Papara',
        ],
        'bank_transfer' => [
            'name' => 'Bank Transfer',
        ],
    ],
];
