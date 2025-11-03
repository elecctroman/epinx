<?php
return [
    'main' => [
        ['label' => 'Home', 'route' => '/'],
        ['label' => 'Store', 'route' => '/store'],
        ['label' => 'Account', 'route' => '/account', 'requires_auth' => true],
    ],
    'admin' => [
        ['label' => 'Dashboard', 'route' => '/admin'],
        ['label' => 'Products', 'route' => '/admin/products'],
        ['label' => 'Orders', 'route' => '/admin/orders'],
    ],
];
