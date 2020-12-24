<?php
namespace ctrl;

use base\needlogin;
use model\info as model;
use root\state\session\user;

class info extends needlogin
{
    public static function get_info()
    {
        $data = user::getInfoInit();
        $data['info'] = parent::filterUserInfo(USER);
        response(200, ['data' => $data]);
    }
    public static function set_info()
    {
        $m = new model;
        $data = $m->setInfo();
        $data ? response(200, $data) : response(400, $m->getError());
    }
    public static function set_passwd()
    {
        $m = new model;
        $data = $m->setPasswd();
        $data ? response(200, $data) : response(400, $m->getError());
    }
}
