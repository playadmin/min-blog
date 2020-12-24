<?php
namespace ctrl;

use base\ctrl;
use root\base\ver;
use root\state\session\user;

class login extends ctrl
{
    public static function index()
    {
        if (!$loginid = F('username')) {
            response(400, '参数错误username');
        }

        if (TOKEN && ($login = user::getLogin()) && $login[2] === $loginid && ($user = user::getUser())) {
            // 已登录时直接返回
            $user['status'] || response(400, '用户已被禁用');
            $data['info'] = parent::filterUserInfo($user);
            $data['info']['token'] = TOKEN;
            response(200, ['data' => $data]);
        }
        if (!$password = F('password')) {
            response(400, '参数错误password');
        }

        if ($msg = ver::ckimg()) {
            response(400, $msg);
        }

        $expire = ID('remember') ? SYS['REMEMBER_EXPIRE'][APP_NAME] ?? 0 : 0;
        if (!user::getLoginByLoginid('loginid', $loginid, $password, $expire) || !$user = user::getUser()) {
            response(400, user::getError());
        }
        empty($user['status']) && response(400, '用户已被禁用');
        if ($data['info'] = user::setLogin()) {
            $data['info'] = parent::filterUserInfo($data['info']);
            $data += user::getInfoInit();
            response(200, ['data' => $data]);
        }
        response(400, '登陆失败');
    }
    public static function reg_account()
    {
        if ($msg = ver::ckimg()) {
            response(400, $msg);
        }
        $account = [
            'loginid' => F('username') ?: '',
            'passwd' => F('password') ?: '',
            'repasswd' => F('repassword') ?: '',
            'status' => 1,
        ];
        ($s = F('phone')) && $account['phone'] = $s;
        ($s = F('email')) && $account['email'] = $s;

        if ($result = user::addAccount($account, true)) {
            $data['info'] = parent::filterUserInfo($result);
            $data += user::getInfoInit();
            response(200, $data);
        } else {
            response(400, user::getError() ?: '登陆失败');
        }
    }
    public static function logout()
    {
        user::setLogout();
        response(200);
    }
}
