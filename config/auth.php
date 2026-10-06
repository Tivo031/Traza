<?php

use App\Models\Usuario;

return [
    'defaults' => ['guard' => 'web', 'passwords' => 'usuarios'],
    'guards' => ['web' => ['driver' => 'session', 'provider' => 'usuarios']],
    'providers' => ['usuarios' => ['driver' => 'eloquent', 'model' => Usuario::class]],
    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => 10800,
];
