<?php

return [
    'superadministrator' => [
        'users' => 'c,r,u,d',
        'acl' => 'c,r,u,d',
        'profile' => 'r,u'
    ],
    'administrator' => [
        'users' => 'c,r,u,d',
        'profile' => 'r,u'
    ],
    'user' => [
        'profile' => 'r,u'
    ]
];
