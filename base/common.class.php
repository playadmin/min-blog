<?php
namespace root\base;

class common
{
    public static function init()
    {
        define('SYS', require P_ROOT . 'config/system.config.php');
    }
    public static function error_log()
    {
        $m = new model;
        $m->error_log();
    }

    /**
     * 图片验证码
     * @return array [data=>图片的base64, ct=>当前时间, ctk=>验证用的hash]
     */
    public static function vercode()
    {
        $data = ver::img();
        response(200, $data);
    }
}
