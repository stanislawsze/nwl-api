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
        ],
        'admin' => [
            'view permissions',
            'view roles',
            'view users',
            'create users',
            'edit users',
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
];
