<?php
return [
    'PATH' => '',

    '/' => [
        'ctrl' => 'index',
        'act' => 'index',
    ],
    '/index' => [
        'ctrl' => 'index',
        'act' => 'index',
        'params' => ['p'],
    ],
    '/a' => [
        'ctrl' => 'index',
        'act' => 'art',
    ],
    '/t' => [
        'ctrl' => 'index',
        'act' => 'tag',
        'params' => ['tid', 'p'],
    ],
    '/p' => [
        'ctrl' => 'index',
        'act' => 'page',
        'params' => ['name', 'p'],
    ],
    '/s' => [
        'ctrl' => 'index',
        'act' => 'search',
    ],
    '/login' => [
        'ctrl' => 'login',
        'act' => '*',
    ],
    '/info' => [
        'ctrl' => 'info',
        'act' => '*',
    ],
    '/common' => [
        'ctrl' => 'common',
        'act' => '*',
    ],
    '/upload' => [
        'ctrl' => 'upload',
        'act' => '*',
    ],
    '/sets' => [
        'ctrl' => 'sets',
        'act' => '*',
    ],
    '/art' => [
        'ctrl' => 'art',
        'act' => '*',
    ],
    '*' => [
        'ctrl' => 'index',
        'act' => '_404',
    ],
];
