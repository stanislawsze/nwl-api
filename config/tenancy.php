<?php

return [
    'membership_roles' => [
        'owner' => [
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view audit logs',
        ],
        'admin' => [
            'view permissions',
            'view roles',
            'view users',
            'create users',
            'edit users',
            'view audit logs',
        ],
        'moderator' => [
            'view users',
            'edit users',
        ],
        'support' => [
            'view users',
        ],
        'member' => [],
    ],
    'invitations' => [
        'default_expiration_hours' => 168,
        'accept_url' => env('TENANCY_INVITATION_ACCEPT_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/invitations/{token}'),
        'login_url' => env('TENANCY_INVITATION_LOGIN_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/login'),
        'register_url' => env('TENANCY_INVITATION_REGISTER_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/register'),
    ],
];
