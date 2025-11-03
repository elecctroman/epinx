<?php
use App\Core\Env;

return [
    'recaptcha' => [
        'enabled' => (bool) Env::get('RECAPTCHA_ENABLED', false),
        'type' => Env::get('RECAPTCHA_TYPE', 'v2'),
        'site_key' => Env::get('RECAPTCHA_SITE_KEY'),
        'secret_key' => Env::get('RECAPTCHA_SECRET_KEY'),
        'score_threshold' => (float) Env::get('RECAPTCHA_SCORE_THRESHOLD', 0.5),
    ],
];
