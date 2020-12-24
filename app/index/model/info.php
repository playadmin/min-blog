<?php
namespace model;

use root\base\model;
use root\state\session\user;

class info extends model
{
    const TABLE = 'user';
    public function setInfo()
    {
        $loginid = F('loginid');
        $nickname = F('nickname');
        $email = F('email');
        if ($loginid && $loginid !== USER['loginid'] && ($len = mb_strlen($loginid)) && (5 > $len || 32 < $len)) {
            return $this->error('请输入用户名(5-32位字符)');
        }
        if ($nickname && $nickname !== USER['nickname'] && ($len = mb_strlen($nickname)) && (2 > $len || 32 < $len)) {
            return $this->error('请输入昵称(2-32位字符)');
        }
        if ($email && $email !== USER['email'] && !$email = filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('请输入正确的电子邮箱');
        }
        $loginid && $save['loginid'] = $loginid;
        $nickname && $save['nickname'] = $nickname;
        $email && $save['email'] = $email;
        if (!isset($save)) {
            return $this->error('没有要修改的内容');
        }
        $db = $this->db(self::TABLE);
        if ($db->where(['uid' => USER['uid']])->update($save)) {
            $data = ['data' => $save];
            if ($loginid) {
                user::delAccount(USER['uid']);
                $data['url'] = '/login';
                $data['msg'] = '修改登录账号需要重新登录';
            } else {
                $data['msg'] = '保存成功';
                user::patchAccount(USER['uid'], $save);
            }
            return $data;
        } else {
            return $this->error($db->getError() ?: '没有变更');
        }
    }
    public function setPasswd()
    {
        if ((!$org = F('org')) || USER['passwd'] !== user::passwd($org, USER['settime'])) {
            return $this->error('原密码不正确');
        }
        if ((!$len = strlen($passwd = F('passwd'))) || (5 > $len || 16 < $len)) {
            return $this->error('请输入5-16位密码');
        }
        if (USER['passwd'] === user::passwd($passwd, USER['settime'])) {
            return $this->error('没有变更');
        }
        if (!($repasswd = F('repasswd')) || $repasswd !== $passwd) {
            return $this->error('确认密码不正确');
        }
        $data['passwd'] = user::passwd($passwd, TIME);
        $data['settime'] = TIME;
        $db = $this->db(self::TABLE);
        if ($db->where(['uid' => USER['uid']])->update($data)) {
            user::patchAccount(USER['uid'], $data);
            return ['msg' => '修改密码成功，请重新登录', 'url' => '/login'];
        } else {
            return $this->error($db->getError() ?: '没有变更');
        }
    }
}
