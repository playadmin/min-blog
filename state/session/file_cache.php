<?php
namespace root\state\session;

class file_cache
{
    private $table, $type, $tokens;
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }
    public static function getCacheNumber()
    {
        $account0 = $wx0 = $account1 = $wx1 = $info1 = 0;
        if ($scan = scandir(P_CACHE)) {
            foreach ($scan as $v) {
                if ('.' === $v && '..' === $v) {
                    continue;
                }

                $preg = '/^user-wx-(openid|unionid)-\d+$/';
                switch ($v) {
                    case 'user-account':
                        $account0 = SumDir(P_CACHE . "/{$v}/admin");
                        $account1 = SumDir(P_CACHE . "/{$v}/member");
                        break;
                    default:
                        if (preg_match($preg, $v)) {
                            $wx0 += SumDir(P_CACHE . "/{$v}/admin");
                            $wx1 += SumDir(P_CACHE . "/{$v}/member");
                        }
                }
            }
        }
        return [
            'admin' => [
                ['type' => '注册账号', 'key' => 'account', 'num' => $account0],
                ['type' => '微信账号', 'key' => 'wx', 'num' => $wx0],
            ],
            'member' => [
                ['type' => '注册账号', 'key' => 'account', 'num' => $account1],
                ['type' => '微信账号', 'key' => 'wx', 'num' => $wx1],
            ],
        ];
    }
    public static function clean($key = '')
    {
        $i = 0;
        if ($scan = scandir(P_CACHE)) {
            foreach ($scan as $v) {
                if ('.' === $v && '..' === $v) {
                    continue;
                }

                $preg = '/^user-wx-(openid|unionid)-\d+$/';
                switch ($v) {
                    case 'user-account':
                        if ('all' === $key || 'account' === $key) {
                            $path = $this->table ? P_CACHE . "/{$v}/{$this->table}" : P_CACHE . "/{$v}";
                            $i += DelDir($path);
                        }
                        break;
                    default:
                        if (('all' === $key || 'wx' === $key) && preg_match($preg, $v)) {
                            $path = $this->table ? P_CACHE . "/{$v}/{$this->table}" : P_CACHE . "/{$v}";
                            $i += DelDir($path);
                        }
                }
            }
        }
        return $i;
    }
    private function accountKey(array $login)
    {
        return P_CACHE . "user-{$login[0]}/{$this->table}/" . $login[1];
    }
    public function getToken($account)
    {
        return md5("{$account['type']}:{$account['uid']}:" . SYS['HASH_KEY'][$this->table] . microtime(true));
    }
    public function getLogin()
    {
        if (empty($_COOKIE["{$this->table}-a"])) {
            return false;
        }

        $login = explode('/', $_COOKIE["{$this->table}-a"]);
        $this->type = $login[0];
        return $login;
    }
    public function setLogout()
    {
        setcookie(SYS['TOKEN_NAME'][APP_NAME], '', 0, '/');
        setcookie("{$this->table}-a", '', 0, '/');
    }
    public function getAccount(string $type, int $uid)
    {
        $key = $this->accountKey([$type, $uid]);
        return getLazyCache($key);
    }
    public function getUser(array $login, $account = null)
    {
        $key = $this->accountKey($login);
        if ($account = getLazyCache($key)) {
            if ((!$this->tokens = $account['tokens'] ?? []) || !in_array(TOKEN, $this->tokens)) {
                return false;
            }
            $account['type'] = $login[0];
        }
        return $account;
    }
    public function setAccount(array $account, array $login)
    {
        if (isset($account['type'])) {
            unset($account['type']);
        }

        $key = $this->accountKey($login);
        setLazyCache($key, $account);
    }
    public function saveToken($account, $login)
    {
        $token = $this->getToken($account);
        if (!$this->tokens) {
            $account['tokens'] = [$token];
        } elseif (!in_array($token, $this->tokens)) {
            $account['tokens'] = $this->tokens;
            $max = SYS['MAX_TOKEN'][APP_NAME] ?? 1;
            $len = count($account['tokens']);
            switch ($len <=> $max) {
                case 0:
                    array_shift($account['tokens']);
                    break;
                case 1:
                    array_splice($account['tokens'], 0, $len - $max + 1);
                    break;
            }
            $account['tokens'][] = $token;
        }
        $this->setAccount($account, [$account['type'], $account['uid']]);
        setcookie(SYS['TOKEN_NAME'][APP_NAME], $token, $account['.expire'], '/');
        setcookie("{$this->table}-a", implode('/', $login), $account['.expire'], '/');
        return $token;
    }
    public function delAccount(int $uid)
    {
        return $this->delAccountCache($uid, 'account');
    }
    public function delAccounts(array $uid)
    {
        $n = 0;
        foreach ($uid as $u) {
            $n += $this->delAccountCache($u, 'account');
        }
        return $n;
    }
    public function delWxAccount(int $id, int $mpid = 0)
    {
        $n = $this->delAccountCache($id, "wx-{$mpid}-openid");
        $n = $this->delAccountCache($id, "wx-{$mpid}-unionid");
        return $n;
    }
    public function delWxAccounts(array $arr)
    {
        $n = 0;
        foreach ($arr as $mpid => $id) {
            foreach ($id as $i) {
                $n += $this->delAccountCache($i, "wx-{$mpid}-openid");
                $n += $this->delAccountCache($i, "wx-{$mpid}-unionid");
            }
        }
        return $n;
    }
    public function delAccountCache(int $uid, string $type)
    {
        $key = $this->accountKey([$type, $uid]);
        return setLazyCache($key, false);
    }
    public function patchAccount(int $uid, array $data, string $type)
    {
        $key = $this->accountKey([$type, $uid]);
        if ($account = getLazyCache($key)) {
            $account = $data + $account;
            return setLazyCache($key, $account);
        }
        return false;
    }
}
