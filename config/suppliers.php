<?php

use App\Core\Env;
return [
    'default' => [
        'name' => 'Default Supplier',
        'api_key' => 'your-api-key',
        'endpoint' => 'https://api.supplier.example.com',
    ],
    'turkpin' => [
        'name' => 'Turkpin',
        'api_key' => Env::get('TURKPIN_API_KEY'),
        'endpoint' => Env::get('TURKPIN_ENDPOINT', 'https://turkpin.example.com'),
        'ip_whitelist' => Env::get('TURKPIN_IPS', ''),
    ],
    'pinabi' => [
        'name' => 'Pinabi',
        'username' => Env::get('PINABI_USERNAME'),
        'password' => Env::get('PINABI_PASSWORD'),
        'endpoint' => Env::get('PINABI_ENDPOINT', 'https://pinabi.example.com'),
    ],
    'lotus' => [
        'name' => 'Lotus Lisans',
        'token' => Env::get('LOTUS_TOKEN'),
        'endpoint' => Env::get('LOTUS_ENDPOINT', 'https://lotus.example.com'),
    ],
];
