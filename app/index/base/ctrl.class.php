<?php
namespace base;

use root\state\session\user;

class ctrl
{
    public static function init()
    {
        if (!is_file($sys = P_ROOT . 'config/system.config.php')) {
            Header('Location: ' . U_HOME . 'install.php');
            die;
        }
        define('SYS', require $sys);
        define('TOKEN', $_SERVER['HTTP_TOKEN'] ?? $_COOKIE[SYS['TOKEN_NAME'][APP_NAME]] ?? '');
        SetConfig('DB', SYS['DB']);
        self::login();
    }
    protected static function login()
    {
        if (!TOKEN) {
            define('LOGIN', '请登录');
        } elseif (!$user = user::getUser()) {
            define('LOGIN', user::getError() ?: '请登录');
        } elseif (!$user['status']) {
            define('LOGIN', '账号已被禁用');
        } else {
            define('USER', $user);
            define('UPLOAD_TMP', P_PUBLIC . "tmp/upload/{$user['uid']}");
        }
    }
    protected static function filterUserInfo($user)
    {
        unset($user['tmp']);
        unset($user['passwd']);
        unset($user['settime']);
        unset($user['.expire']);
        unset($user['tokens']);
        return $user;
    }
    public static function _301($url)
    {
        Header("HTTP/1.1 301 Moved Permanently");
        Header("Location: {$url}");
        die;
    }
    public static function _404()
    {
        require P_IN . '404.html';
        die;
    }
    public static function _500()
    {
        require P_IN . '500.html';
        die;
    }
}
