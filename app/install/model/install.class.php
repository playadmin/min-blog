<?php
namespace model;

use libs\pdo;

class install
{
    const HASH_KEY = 'User @ 加密字符串 +- ==';
    private $error;
    private $CFG_DIR = P_ROOT . 'config/';
    private $CFG_DEF = P_ROOT . 'config/system.default.php';
    private $CFG = P_ROOT . 'config/system.config.php';
    public function getError()
    {
        return $this->error;
    }
    public function checkDir()
    {
        $error = [];
        $success = [];
        $notempty = [];
        is_writeable($this->CFG_DIR) ? $success[] = $this->CFG_DIR : $error[] = $this->CFG_DIR;
        if (is_file($this->CFG)) {
            is_writeable($this->CFG) ? $success[] = $this->CFG : $error[] = $this->CFG;
        }
        is_writeable(P_TMP) ? $success[] = P_TMP : $error[] = P_TMP;
        ($scan = scandir(P_TMP)) && count($scan) > 3 && $notempty[] = P_TMP;
        is_writeable($dir = P_PUBLIC . 'files/') ? $success[] = $dir : $error[] = $dir;
        ($scan = scandir($dir)) && count($scan) > 2 && $notempty[] = $dir;
        is_writeable($dir = P_PUBLIC . 'tmp/') ? $success[] = $dir : $error[] = $dir;
        ($scan = scandir($dir)) && count($scan) > 3 && $notempty[] = $dir;
        return [$success, $error, $notempty];
    }
    public function initCfg()
    {
        ($db_host = trim($_POST['db_host'] ?? '')) || $this->error[] = '数据库地址不能空';
        ($db_name = trim($_POST['db_name'] ?? '')) || $this->error[] = '数据库名不能空';
        ($db_user = trim($_POST['db_user'] ?? '')) || $this->error[] = '数据库用户不能空';
        ($db_passwd = trim($_POST['db_passwd'] ?? '')) || $this->error[] = '数据密码不能空';
        ($site_name = trim($_POST['site_name'] ?? '')) || $this->error[] = '站点名称不能空';
        $user_nick = $_POST['user_nick'] ?? '';
        $user_id = $_POST['user_id'] ?? '';
        $user_passwd = $_POST['user_passwd'] ?? '';
        $user_repasswd = $_POST['user_repasswd'] ?? '';
        if ((!$len = mb_strlen($user_nick)) || $len < 2 || $len > 32) {
            $this->error[] = '昵称错误：2-32个字符';
        }

        if ((!$len = mb_strlen($user_id)) || $len < 5 || $len > 32) {
            $this->error[] = '登录账号错误：5-32个字符';
        }

        if ((!$len = strlen($user_passwd)) || $len < 5 || $len > 16) {
            $this->error[] = '登录密码错误：5-16个字符';
        }

        if ($user_passwd !== $user_repasswd) {
            $this->error[] = '确认登录密码错误';
        }

        $db_port = trim($_POST['db_port'] ?? '3306');
        $db_prefix = trim($_POST['db_prefix'] ?? '');
        if ($this->error) {
            return false;
        }

        $cfg['USER'] = ['userid' => $user_id, 'passwd' => $user_passwd, 'nickname' => $user_nick];
        $db_prefix && '_' !== $db_prefix[0] && $db_prefix = '_' . $db_prefix;
        $cfg['DB'] = [
            'host' => $db_host,
            'port' => $db_port,
            'db' => $db_name,
            'user' => $db_user,
            'pass' => $db_passwd,
            'prefix' => $db_prefix,
            'dsn' => "mysql:host={$db_host};dbname={$db_name};port={$db_port}",
        ];
        $cfg['HASH_KEY'] = [
            'user' => self::HASH_KEY,
        ];
        $path = explode('install.php', $_SERVER['REQUEST_URI']);
        $cfg['SITE'] = [
            'name' => $site_name,
            'title' => trim($_POST['site_title'] ?? $site_name),
            'keywords' => trim($_POST['site_keywords'] ?? ''),
            'description' => trim($_POST['site_description'] ?? ''),
            'path' => $path[0],
            'login_uri' => $path[0] . 'login',
            'url' => "http://{$_SERVER['HTTP_HOST']}{$path[0]}",
        ];
        return $cfg;
    }
    public function initDataBase(&$cfg)
    {
        $file = $this->CFG_DIR . 'blog.sql';
        if (!is_file($file) || !$str = file_get_contents($file)) {
            $this->error[] = '没有找到数据库文件';
            return false;
        }
        $date = date('Ymd');
        $time = TIME;
        $hash = md5($cfg['USER']['passwd'] . $cfg['HASH_KEY']['user'] . $time);
        $str = preg_replace('/--.+[\r\n]*/', '', $str);
        $preg = '/(TABLE\s+[^`]*|INSERT\s+INTO\s+)`(\w+)`/';
        $cfg['DB']['prefix'] && $str = preg_replace($preg, "\$1`{$cfg['DB']['prefix']}\$2`", $str);
        $arr = explode(';', $str);
        $arr[] = "INSERT INTO `{$cfg['DB']['prefix']}user` SET `uid`=1,`loginid`='{$cfg['USER']['userid']}',`nickname`='{$cfg['USER']['nickname']}',`regtime`= {$time},`settime`= {$time},`status`=1,`passwd`='{$hash}'";
        $pdo = pdo::init($cfg['DB']);
        $i = 0;
        try {
            foreach ($arr as $v) {
                ($v = trim($v)) && $i += $pdo->exec($v);
            }
        } catch (\PDOException $e) {
            $this->error[] = $e->getMessage();
            return false;
        }
        unset($cfg['USER']);
        return $i;
    }
    public function saveCfg($cfg)
    {
        if (!is_file($this->CFG_DEF)) {
            $this->error[] = '没有找到默认配置文件';
            return false;
        }
        $sets = require $this->CFG_DEF;
        $cfg['SITE'] += $sets['SITE'];
        $sets = $cfg + $sets;
        $str = "<?php\nreturn " . var_export($sets, true) . ';';
        if (file_put_contents($this->CFG, $str)) {
            return true;
        } else {
            $this->error[] = '保存配置失败';
            return false;
        }
    }
    public function checkDb($cfg)
    {
        $pdo = pdo::init($cfg['DB']);
        try {
            $info = $pdo->getAttribute(\PDO::ATTR_SERVER_INFO);
            return $info;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    public function cleanDir()
    {
        DelDir(P_TMP);
        foreach (SYS['PATH'] as $v) {
            DelDir($v);
        }
    }
}
