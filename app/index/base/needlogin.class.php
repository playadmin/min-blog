<?php
namespace base;

class needlogin extends ctrl
{
    const LOGIN_PATH = U_HOME . 'login';
    public static function init()
    {
        parent::init();
        if (defined('LOGIN')) {
            response(401, ['msg' => LOGIN, 'url' => self::LOGIN_PATH]);
        }
    }
    public static function _404()
    {
        response(404, '非法请求');
    }
    public static function _500()
    {
        response(500, '出错了，请联系管理员');
    }
}
