<?php
use rely\debug;
use rely\router;

function AppRun($entry)
{
    define('ZPHP_VER', '0.0.3');
    define('TIME', time());
    define('MTIME', microtime(true));
    $php = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
    define('PHP_FILE', array_shift($php));
    define('U_ROOT', $php ? '/' . implode('/', $php) : '');
    define('U_HOME', U_ROOT . '/');
    define('U_TMP', U_HOME . 'tmp');
    define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'xmlhttprequest' === strtolower($_SERVER['HTTP_X_REQUESTED_WITH']));
    define('IS_WX', false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger'));
    define('METHOD', $_SERVER['REQUEST_METHOD']);
    define('P_IN', str_replace('\\', '/', dirname($entry)) . '/');
    define('P_ROOT', str_replace('\\', '/', __DIR__) . '/');
    define('P_APP', P_ROOT . 'app/' . APP_NAME . '/');
    define('P_TMP', P_ROOT . 'tmp/');
    define('P_CACHE', P_TMP . 'cache/');
    define('LEN_IN', strlen(P_IN));
    if (P_IN === P_ROOT) {
        define('P_PUBLIC', P_IN . 'public/');
        define('U_PUBLIC', U_HOME . 'public');
    } else {
        define('P_PUBLIC', P_IN);
        define('U_PUBLIC', U_ROOT);
    }
    define('P_RES', P_PUBLIC . 'res/');
    define('P_RES_APP', P_PUBLIC . 'res/' . APP_NAME . '/');
    define('U_RES', U_PUBLIC . '/res');
    define('U_RES_APP', U_RES . '/' . APP_NAME);
    define('ZPHP_OS', 0 === stripos(strtoupper(PHP_OS), 'WIN') ? 'WINDOWS' : 'LINUX');
    spl_autoload_register('z::AutoLoad');
    set_error_handler('z::errorHandler');
    set_exception_handler('z::exceptionHandler');
    z::loadRely();
    define('DEBUGER', class_exists('rely\\debug', false));
    z::LoadConfig();
    ini_set('date.timezone', $GLOBALS['ZPHP_CONFIG']['TIME_ZONE'] ?? 'Asia/Shanghai');
    isset($GLOBALS['ZPHP_CONFIG']['DEBUG']['level']) || $GLOBALS['ZPHP_CONFIG']['DEBUG']['level'] = 3;
    $GLOBALS['ZPHP_MAPPING'] = [
        'root' => P_ROOT,
        'libs' => P_ROOT . 'libs/',
        'app' => P_APP,
    ];
    z::initRely('before-router');
    if (!defined('ROUTE')) {
        $router = [
            'ctrl' => empty($_GET['c']) ? 'index' : $_GET['c'],
            'act' => empty($_GET['a']) ? 'index' : $_GET['a'],
        ];
        empty($GLOBALS['ZPHP_CONFIG']['ROUTER']['module']) || $router['module'] = empty($_GET['m']) ? 'index' : $_GET['m'];
        define('ROUTE', $router);
    }
    if (isset(ROUTE['module'])) {
        define('P_MODULE', P_APP . ROUTE['module'] . '/');
        define('P_RES_MODULE', P_RES_APP . ROUTE['module'] . '/');
        define('U_RES_MODULE', U_RES_APP . '/' . ROUTE['module']);
    }
    if ($GLOBALS['ZPHP_CONFIG']['DEBUG']['level'] > 1) {
        error_reporting(E_ALL);
    } else {
        ini_set('expose_php', 'Off');
        error_reporting(0);
    }
    z::start();
}
function Zautoload(string $act)
{
    $GLOBALS['ZPHP_AUTOLOAD'] = $act;
}
function Debug(int $i, $type = '')
{
    $GLOBALS['ZPHP_CONFIG']['DEBUG']['level'] = $i;
    $type && $GLOBALS['ZPHP_CONFIG']['DEBUG']['type'] = $type;
}
function IsFullPath(string $path): bool
{
    return 'WINDOWS' === ZPHP_OS ? ':' === $path[1] : '/' === $path[0];
}
function SetConfig(string $key, $value)
{
    if (isset($GLOBALS['ZPHP_CONFIG'][$key]) && is_array($value)) {
        $GLOBALS['ZPHP_CONFIG'][$key] = $value + $GLOBALS['ZPHP_CONFIG'][$key];
    } else {
        $GLOBALS['ZPHP_CONFIG'][$key] = $value;
    }
}
function ReadFileSH($file)
{
    $h = fopen($file, 'r');
    if (!flock($h, LOCK_SH)) {
        throw new \Exception('获取文件共享锁失败');
    }
    $result = fread($h, filesize($file));
    flock($h, LOCK_UN);
    fclose($h);
    return $result;
}
function P($var, bool $echo = true)
{
    ob_start();
    var_dump($var);
    $html = preg_replace('/\]\=\>\n(\s+)/m', '] =>', htmlspecialchars_decode(ob_get_clean()));
    if ($echo) {
        echo "<pre>{$html}</pre>";
    } else {
        return $html;
    }
}
function FileSizeFormat(int $size = 0, int $dec = 2): string
{
    $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $pos = 0;
    while ($size >= 1024) {
        $size /= 1024;
        ++$pos;
    }
    return round($size, $dec) . $unit[$pos];
}
function TransCode($str)
{
    $encode = mb_detect_encoding($str, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5', 'EUC-CN']);
    return 'UTF-8' === $encode ? $str : mb_convert_encoding($str, 'UTF-8', $encode);
}
function MakeDir($dir, $mode = 0755, $recursive = true)
{
    if (!file_exists($dir) && !mkdir($dir, $mode, $recursive)) {
        throw new Error("创建目录{$dir}失败,请检查权限");
    }
    return true;
}
function DelDir($dir, $rmdir = false, $i = 0)
{
    if (file_exists($dir) && $h = opendir($dir)) {
        while (false !== ($item = readdir($h))) {
            if ('.' !== $item && '..' !== $item) {
                if (is_dir($dir . '/' . $item)) {
                    $i += DelDir($dir . '/' . $item, $rmdir);
                } elseif (unlink($dir . '/' . $item)) {
                    ++$i;
                }
            }
        }
        closedir($h);
    } else {
        return false;
    }

    $rmdir && rmdir($dir) && ++$i;
    return $i;
}
function Page($cfg, $return = false)
{
    $var = $cfg['var'] ?? 'p';
    $data['rows'] = $cfg['rows'] ?? 0;
    $data['num'] = ($cfg['num'] ?? 10);
    $data['p'] = $cfg['p'] ?? (isset($_GET[$var]) ? (int) $_GET[$var] : 1);
    $data['p'] || $data['p'] = 1;
    if (isset($cfg['max'])) {
        $maxRows = $data['num'] * $cfg['max'];
        if ($maxRows < $data['rows']) {
            $data['rows'] = $maxRows;
            $data['p'] > $cfg['max'] && $data['p'] = $cfg['max'];
        }
    }
    $data['pages'] = $data['rows'] ? (int) ceil($data['rows'] / $data['num']) : 1;
    $inrange = $cfg['inrange'] ?? true;
    $inrange && $data['pages'] < $data['p'] && $data['p'] = $data['pages'];
    $start = ($data['p'] - 1) * $data['num'];
    $data['limit'] = "{$start},{$data['num']}";
    if (!$return) {
        return $data['limit'];
    }
    switch ($data['pages'] <=> $data['p']) {
        case -1:
            $data['r'] = 0;
            break;
        case 0:
            $data['r'] = $data['rows'] % $data['num'] ?: ($data['rows'] ? $data['num'] : 0);
            break;
        case 1:
            $data['r'] = $data['num'];
            break;
    }
    if (is_array($return)) {
        $p = $data['p'];
        $var = $cfg['var'] ?? 'p';
        $ver = $cfg['ver'] ?? '';
        $mod = $cfg['mod'] ?? null;
        $nourl = $cfg['nourl'] ?? 'javascript:;';
        $params = ROUTE['params'] ?? false;
        $query = $_GET;
        foreach ($return as $v) {
            switch ($v) {
                case 'prev':
                    $params[$var] = $p - 1;
                    $data['prev'] = $params[$var] && $p !== $params[$var] ? rely\router::url([ROUTE['ctrl'], ROUTE['act']], ['params' => $params, 'query' => $query], $ver, $mod) : $nourl;
                    break;
                case 'next':
                    $params[$var] = $p + 1;
                    $data['next'] = $data['pages'] > $p ? rely\router::url([ROUTE['ctrl'], ROUTE['act']], ['params' => $params, 'query' => $query], $ver, $mod) : $nourl;
                    break;
                case 'first':
                    $params[$var] = 1;
                    $data['first'] = 1 === $p || 1 === $data['pages'] ? $nourl : rely\router::url([ROUTE['ctrl'], ROUTE['act']], ['params' => $params, 'query' => $query], $ver, $mod);
                    break;
                case 'last':
                    $params[$var] = $data['pages'];
                    $data['last'] = 1 === $data['pages'] || $data['pages'] === $p ? $nourl : rely\router::url([ROUTE['ctrl'], ROUTE['act']], ['params' => $params, 'query' => $query], $ver, $mod);
                    break;
                case 'list':
                    (int) $rolls = $cfg['rolls'] ?? 10;
                    if (1 < $data['pages']) {
                        $pos = intval($rolls / 2);
                        if ($pos < $p && $data['pages'] > $rolls) {
                            $i = $p - $pos;
                            $end = $i + $rolls - 1;
                            $end > $data['pages'] && ($end = $data['pages']) && ($i = $end - $rolls + 1);
                        } else {
                            $i = 1;
                            $end = $rolls > $data['pages'] ? $data['pages'] : $rolls;
                        }
                        for ($i; $i <= $end; $i++) {
                            $params[$var] = $i;
                            $data['list'][$i] = $p == $i ? 'javascript:;' : rely\router::url([ROUTE['ctrl'], ROUTE['act']], ['params' => $params, 'query' => $query], $ver, $mod);
                        }
                    } else {
                        $data['list'] = [];
                    }
                    break;
            }
        }
    }
    return $data;
}

class z
{
    private static $RELY;
    public static function start()
    {
        self::initRely('before-start');
        self::loadMapping();
        self::loadFunctions();
        self::setSession();
        self::setInput();
        headers_sent() || header('Content-type: text/html; charset=utf-8');
        header('X-Powered-By: ' . ($GLOBALS['ZPHP_CONFIG']['POWEREDBY'] ?? 'ZPHP-MIN'));
        $ctrl = 'ctrl\\' . ROUTE['ctrl'];
        $act = ROUTE['act'];
        is_file($file = $GLOBALS['ZPHP_MAPPING']['ctrl'] . ROUTE['ctrl'] . '.class.php') && require $file;
        if ($GLOBALS['ZPHP_CONFIG']['DEBUG']['level'] < 2) {
            if (!class_exists($ctrl)) {
                self::_404();
            }
            if (!method_exists($ctrl, $act)) {
                method_exists($ctrl, '_404') ? $ctrl::_404() : self::_404();
            }
        }
        self::initRely('before-action');
        method_exists($ctrl, 'init') && $ctrl::init();
        $result = $ctrl::$act();
        method_exists($ctrl, 'after') && $ctrl::after();
        if (isset($result)) {
            die(self::json($result));
        }
        DEBUGER && debug::ShowMsg();
        die;
    }
    public static function initRely($moment)
    {
        if (isset(self::$RELY[$moment])) {
            foreach (self::$RELY[$moment] as $c) {
                $c::init();
            }
        }
    }
    public static function loadRely()
    {
        if (file_exists($dir = P_ROOT . 'rely') && $scan = scandir($dir)) {
            foreach ($scan as $f) {
                if ('.' !== $f && '..' !== $f && ($arr = explode('.', $f)) && isset($arr[1]) && 'php' === end($arr) && $name = $arr[0]) {
                    require "{$dir}/{$f}";
                    $class = "\\rely\\{$name}";
                    defined("{$class}::MOMENT") && self::$RELY[$class::MOMENT][] = $class;
                }
            }
        }
    }
    public static function json($data)
    {
        ob_end_clean();
        header('Content-Type:application/json; charset=utf-8');
        die(json_encode($data, 320));
    }
    public static function _404()
    {
        header('status: 404');
        die('<h1 style="text-align:center;padding:1rem 0;">404</h1>');
    }
    public static function _500()
    {
        header('status: 500');
        die('<h1 style="text-align:center;padding:1rem 0;">500</h1>');
    }
    private static function setSession()
    {
        if (isset($GLOBALS['ZPHP_CONFIG']['SESSION']['auto']) && !$GLOBALS['ZPHP_CONFIG']['SESSION']['auto']) {
            return;
        }
        self::SessionStart();
    }
    public static function SessionStart()
    {
        if (!empty($GLOBALS['ZPHP_CONFIG']['SESSION']['name'])) {
            $org = session_name($GLOBALS['ZPHP_CONFIG']['SESSION']['name']);
            isset($_COOKIE[$org]) && setcookie($org, '', 0, '/');
        }
        if (!empty($GLOBALS['ZPHP_CONFIG']['SESSION']['httponly'])) {
            ini_set('session.cookie_httponly', true);
        }
        if (!empty($GLOBALS['ZPHP_CONFIG']['SESSION']['redis'])) {
            $cfg = empty($GLOBALS['ZPHP_CONFIG']['SESSION']['host']) ? $GLOBALS['ZPHP_CONFIG']['REDIS'] : $GLOBALS['ZPHP_CONFIG']['SESSION'];
            $database = $GLOBALS['ZPHP_CONFIG']['SESSION']['database'] ?? 1;
            $session_path = "tcp://{$cfg['host']}:{$cfg['port']}?database={$database}";
            empty($cfg['pass']) || $session_path .= "&auth={$cfg['pass']}";
            ini_set('session.save_handler', 'redis');
            ini_set('session.save_path', $session_path);
        }
        session_start();
    }
    public static function AutoLoad(string $r)
    {
        if (false !== strpos($r, '\\')) {
            $path_arr = explode('\\', $r);
            $path_root = array_shift($path_arr);
            if (!isset($GLOBALS['ZPHP_MAPPING'][$path_root])) {
                if (empty($GLOBALS['ZPHP_AUTOLOAD'])) {
                    throw new \Exception("命名空间 {$path_root} 未做映射");
                } else {
                    return $GLOBALS['ZPHP_AUTOLOAD']($r);
                }
            }
            $fileName = array_pop($path_arr);
            $sub_path = $path_arr ? implode('/', $path_arr) . '/' : '';
            $path = "{$GLOBALS['ZPHP_MAPPING'][$path_root]}{$sub_path}";
            if (is_file($file = "{$path}{$fileName}.class.php") || is_file($file = "{$path}{$fileName}.php")) {
                require $file;
            } else {
                throw new \Exception("file not fond: {$path}{$fileName}.class.php");
            }
        } else {
            empty($GLOBALS['ZPHP_AUTOLOAD']) || $GLOBALS['ZPHP_AUTOLOAD']($r);
        }
    }
    public static function LoadConfig($conf = false)
    {
        if ($conf) {
            is_file($conf) && is_array($conf = require $conf) && $GLOBALS['ZPHP_CONFIG'] = $conf + $GLOBALS['ZPHP_CONFIG'];
        } else {
            $GLOBALS['ZPHP_CONFIG'] = is_file($file = P_APP . 'config/config.php') && is_array($conf = require $file) ? $conf : [];
            is_file($file = P_ROOT . 'config/config.php') && is_array($conf = require $file) && $GLOBALS['ZPHP_CONFIG'] += $conf;
        }
    }
    public static function loadFunctions()
    {
        is_file($file = P_ROOT . 'libs/functions.php') && require $file;
        is_file($file = P_APP . 'lib/functions.php') && require $file;
    }
    public static function GetConfig($key = '')
    {
        return $key ? $GLOBALS['ZPHP_CONFIG'][$key] : $GLOBALS['ZPHP_CONFIG'];
    }
    private static function loadMapping()
    {
        is_file($file = P_APP . 'config/mapping.php') && is_array($map = require $file) && $GLOBALS['ZPHP_MAPPING'] += $map;
        is_file($file = P_ROOT . 'config/mapping.php') && is_array($map = require $file) && $GLOBALS['ZPHP_MAPPING'] += $map;
        if (defined('P_MODULE')) {
            $GLOBALS['ZPHP_MAPPING']['module'] = P_MODULE;
            $GLOBALS['ZPHP_MAPPING']['ctrl'] = P_MODULE . 'ctrl/';
            $GLOBALS['ZPHP_MAPPING']['lib'] = P_MODULE . 'lib/';
            $GLOBALS['ZPHP_MAPPING']['model'] = P_MODULE . 'model/';
        } else {
            $GLOBALS['ZPHP_MAPPING']['ctrl'] = P_APP . 'ctrl/';
            $GLOBALS['ZPHP_MAPPING']['lib'] = P_APP . 'lib/';
            $GLOBALS['ZPHP_MAPPING']['model'] = P_APP . 'model/';
        }
    }
    private static function setInput()
    {
        $I['INPUT'] = file_get_contents('php://input');
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $H = explode(';', $_SERVER['CONTENT_TYPE']);
            if ('POST' === $_SERVER['REQUEST_METHOD']) {
                'application/json' === $H[0] && $_POST += json_decode($I['INPUT'], true);
            } else {
                switch ($H[0]) {
                    case 'application/json':
                        $I[$_SERVER['REQUEST_METHOD']] = json_decode($I['INPUT'], true);
                        break;
                    case 'application/x-www-form-urlencoded':
                        parse_str($I['INPUT'], $I[$_SERVER['REQUEST_METHOD']]);
                        break;
                }
            }
        }
        define('DATA', $I);
    }
    public static function exceptionHandler($e)
    {
        if (DEBUGER) {
            debug::exceptionHandler($e);
        } else {
            $GLOBALS['ZPHP_CONFIG']['DEBUG']['level'] > 1 || z::_500();
            $line = $e->getLine();
            $file = $e->getFile();
            $msg = $e->getMessage() . " at [{$file} : {$line}]";
            $trace = $e->getTraceAsString();
            $trace = str_replace('\\\\', '\\', $trace);
            echo "<style>body{margin:0;padding:0;}</style><div style='background:#FFBBDD;padding:1rem;'><h2>ERROR!</h2><h3>{$msg}</h3>";
            echo '<strong><pre>' . $trace . '</pre></strong>';
            foreach ($e->getTrace() as $k => $v) {
                $v['args'] && $args["#{$k}"] = 1 === count($v['args']) ? $v['args'][0] : $v['args'];
            }
            if (isset($args)) {
                echo '<h3>参数：</h3>';
                P($args);
            }
            echo '</div>';
            die;
        }
    }
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (DEBUGER) {
            debug::errorHandler($errno, $errstr, $errfile, $errline);
        } elseif ($GLOBALS['ZPHP_CONFIG']['DEBUG']['level'] > 2) {
            $str = "<div style='background:#ccc;padding:1rem;width:100%;'><strong>Warning({$errno}): {$errstr} [{$errfile}: {$errline}]</strong></div>";
            echo $str;
        }
    }
}
