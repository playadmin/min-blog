<?php
namespace root\state\session;

use libs\db;

class session
{
    const LOGIN_EXPIRE = SYS['LOGIN_EXPIRE'][APP_NAME] ?? 86400;
    const RESET_EXPIRE_LIMIT = 300; // 登录的过期时间小于此值，续期
    const RESET_EXPIRE = 3600; // 续期的时长
    protected static $error, $cache, $login, $account, $field, $ext, $wxinfo;
    public static function init()
    {
        $cache = 'root\\state\\session\\' . (SYS['CACHE_MOD'] ?? 'file') . '_cache';
        static::$cache || static::$cache = new $cache;
    }
    public static function getError()
    {
        return static::$error;
    }
    public static function clean($table = 'admin', $type = '')
    {
        return static::$cache->table($table)->clean($type);
    }
    public static function getCacheNumber()
    {
        return static::$cache->getCacheNumber();
    }
    public static function passwd(string $str, $time = null, $table = null)
    {
        $time ?? $time = TIME;
        $table || $table = static::TABLE;
        $md = SYS['HASH_KEY'][$table];
        return md5("{$str}{$md}{$time}");
    }
    public static function saveLogin()
    {
        $save = static::getSaveLogin(static::$account, static::$wxinfo);
        if (isset(static::$wxinfo)) {
            // 更新微信用户的信息
            static::$account['sex'] = $save['sex'] = intval(static::$wxinfo['sex']);
            static::$account['nickname'] = $save['nickname'] = static::$wxinfo['nickname'];
            static::$account['headimgurl'] = $save['headimgurl'] = static::$wxinfo['headimgurl'];
        }
        $save['lasttime'] = static::$account['lasttime'];
        $save['lastip'] = static::$account['lastip'];
        $save['lastid'] = static::$account['lastid'];
        db::init(static::TABLE)->where('`uid`=' . static::$account['uid'])->update($save, false);
    }
    public static function getUserExt(array $account)
    {
        // 登录时获取角色等扩展信息
        return [];
    }
    public static function getSaveLogin(array $account, $wxinfo)
    {
        // 登录成功后需要保存的用户信息
        return [];
    }
    public static function initUserExt($account)
    {
        // 添加用户时初始化 角色等相关信息
        return [];
    }
    public static function setLogout()
    {
        static::$cache->table(static::TABLE)->setLogout();
    }
    public static function setLogin()
    {
        isset(static::$ext) || static::$ext = static::getUserExt(static::$account) ?: [];
        static::$account = static::$ext + static::$account;
        if (isset(static::$account['lasttime'])) {
            static::$account['prev_time'] = static::$account['lasttime'];
            static::$account['lasttime'] = TIME;
        } else {
            static::$account['prev_time'] = static::$account['lasttime'] = TIME;
        }
        if (isset(static::$account['lastip'])) {
            static::$account['prev_ip'] = static::$account['lastip'];
            static::$account['lastip'] = getip(true);
        } else {
            static::$account['prev_ip'] = static::$account['lastip'] = getip(true);
        }
        if (isset(static::$account['lastid'])) {
            static::$account['prev_id'] = static::$account['lastid'];
            static::$account['lastid'] = static::$account[static::$field];
        } else {
            static::$info['prev_id'] = static::$info['lastid'] = static::$account[static::$field];
        }
        static::saveLogin();
        static::$account['token'] = static::$cache->table(static::TABLE)->saveToken(static::$account, static::$login);
        $user = static::$account;
        unset($user['tokens']);
        unset($user['passwd']);
        return $user;
    }

    public static function getLogin()
    {
        static::$login = static::$cache->table(static::TABLE)->getLogin();
        return static::$login;
    }

    public static function getUser($r = false)
    {
        if (!static::$login && !static::$login = static::$cache->table(static::TABLE)->getLogin()) {
            static::$error = '请登录';
            return false;
        }
        if (static::$account) {
            return static::$account;
        }

        static::$account = static::$cache->table(static::TABLE)->getUser(static::$login);
        if (!static::$account || empty(static::$account['.expire']) || static::$account['.expire'] < TIME) {
            static::$error = '请重新登录';
            return false;
        }

        if (static::$account['.expire'] - TIME < self::RESET_EXPIRE_LIMIT) { // 续期
            static::$account['.expire'] += self::RESET_EXPIRE;
            static::$cache->table(static::TABLE)->setAccount(static::$account, static::$login, self::RESET_EXPIRE);
        }
        static::$account['app'] = APP_NAME;
        return static::$account;
    }

    /**
     * 账号密码登录
     * @param field 字段
     * @param loginid 账号
     * @param password 密码
     * @param expire 登录过期时间
     */
    public static function getLoginByLoginid(string $field, string $loginid, $password, $expire = 0)
    {
        if (static::getAccountFromDb("`{$field}`='{$loginid}'", static::TABLE)) {
            if (static::$account['passwd'] !== static::passwd($password, static::$account['settime'])) {
                static::$error = '密码不正确';
                return false;
            }
            static::$field = $field;
            static::$login = ['account', static::$account['uid'], $loginid];
            static::$account['type'] = 'account';
            static::$account['.expire'] = TIME + ($expire ?: static::LOGIN_EXPIRE);
            return static::$login;
        } else {
            static::$error = '账号不存在';
            return false;
        }
    }
    public static function resetPasswd($password)
    {
        $save['settime'] = TIME;
        $save['passwd'] = $password;
        $db = db::init(static::TABLE);
        if ($db->where(['uid' => static::$account['uid']])->update($save)) {
            static::$account['settime'] = TIME;
            static::$account['passwd'] = static::passwd($password, TIME);
        } else {
            throw new \Exception('重设密码失败');
        }
    }
    public static function getAccount($uid, $table = '', $type = 'account')
    {
        $table || $table = static::TABLE;
        return static::$cache->table($table)->getAccount($type, $uid);
    }
    public static function setAccount($account, $table = '', $type = 'account')
    {
        $table || $table = static::TABLE;
        return static::$cache->table($table)->setAccount($account, [$type, $account['uid']]);
    }
    public static function getSetAccount($uid, $table = '', $type = 'account')
    {
        $table || $table = static::TABLE;
        if (!$account = static::$cache->table($table)->getAccount($type, $uid)) {
            $tp = strstr($type, '-', true) ?: $type;
            if (!$account = static::getAccountFromDb($tp, "`uid`={$uid}", $table)) {
                return false;
            }
            static::$cache->table($table)->setAccount($account, [$type, $uid]);
        }
        return $account;
    }
    public static function getAccountFromDb($where, $table)
    {
        if (!static::$account = db::init($table)->where($where)->find()) {
            static::$error = '用户不存在';
            return false;
        }
        return static::$account;
    }
    public static function delAccount(int $uid, $table = '')
    {
        $table || $table = static::TABLE;
        return static::$cache->table($table)->delAccount($uid);
    }
    public static function delWxAccount(int $uid, int $mpid = 0, $table = '')
    {
        $table || $table = static::TABLE;
        return static::$cache->table($table)->delWxAccount($uid, $mpid);
    }
    public static function delAccounts(array $uid, string $table)
    {
        $table || $table = static::TABLE;
        return static::$cache->table($table)->delAccounts($uid);
    }
    public static function delWxAccounts(array $arr, string $table)
    {
        $table || $table = static::TABLE;
        return static::$cache->table($table)->delWxAccounts($arr);
    }
    public static function patchAccount(int $uid, array $data, string $type = 'account', $table = '')
    {
        static::$cache || static::init();
        static::$cache->table($table ?: static::TABLE);
        if (isset($data['passwd'])) {
            return static::$cache->delAccount($uid, $type);
        }
        if (is_array($uid)) {
            foreach ($uid as $u) {
                $data[$u] = static::$cache->patchAccount($u, $data, $type);
            }
        } else {
            $data = static::$cache->patchAccount($uid, $data, $type);
        }
        return $data;
    }

    public static function addAccount(array $account, $setLogin = false, $table = '')
    {
        $table || $table = static::TABLE;
        $db = db::init($table);
        $account['regtime'] = $account['settime'] = TIME;
        $date = date('Ymd');
        $account['regdate'] = $date;
        if (!$account['uid'] = $db->insert($account)) {
            static::$error = $db->getError();
            return false;
        }
        $res = static::initUserExt($account);
        if (is_array($res)) {
            $setLogin && static::$ext = $res;
        } elseif ($res) {
            static::$error = $res;
            return false;
        }
        $login = ['account', $account['uid'], $account['loginid']];
        if ($setLogin && $table === static::TABLE) {
            static::$account = $account;
            static::$account['type'] = 'account';
            static::$account['.expire'] = TIME + static::LOGIN_EXPIRE;
            static::$login = $login;
            static::$cache->table($table)->setAccount(static::$account, static::$login);
            return self::setLogin();
        } else {
            return $login;
        }
    }
}
