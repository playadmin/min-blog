<?php
namespace root\state\session;

use root\state\tags;

class user extends session
{
    const TABLE = 'user';
    public static function getInfoInit()
    {
        return [
            'site' => [
                'name' => SYS['SITE']['name'] ?? '',
                'path' => SYS['SITE']['path'] ?? '',
                'url' => SYS['SITE']['url'] ?? '',
                'style' => SYS['SITE']['style'] ?? '',
            ],
            'tags' => tags::get(),
        ];
    }
}
user::init();
