<?php
use rely\debug;
function response($status = 200, $data = [], $header = null)
{
    ob_end_clean();
    header('Content-Type:application/json; charset=utf-8');
    if ($header) {
        if (is_array($header)) {
            foreach ($header as $v) {
                header($v);
            }
        } else {
            header($header);
        }
    }
    is_array($data) || $data = ['msg' => $data];
    $data['status'] = $status;
    ($debug = debug::GetJsonDebug()) && $data['ZPHP_DEBUG'] = $debug;
    die(json_encode($data, 320));
}
function id_path($id)
{
    return intval($id / 1000) . "k/{$id}";
}
function setLazyCache(string $file, $data = '')
{
    if (false === $data) {
        return is_file($file) ? unlink($file) : true;
    }

    is_callable($data) && $data = $data() ?: '';
    file_exists($dir = dirname($file)) || MakeDir($dir, 0755, true);
    $r = file_put_contents($file, serialize($data), LOCK_EX);
    if (false === $r) {
        throw new \Exception("写入失败，请检查权限:{$file}");
    }

    return $r;
}
function getLazyCache(string $file, $expire = 0)
{
    if (!is_file($file)) {
        return false;
    } elseif ($expire && filemtime($file) + $expire < TIME) {
        unlink($file);
        return false;
    } else {
        $data = file_get_contents($file);
        return $data ? unserialize($data) : $data;
    }
}
function ID(string $key, int $default = 0)
{
    $var = $_GET[$key] ?? $_POST[$key] ?? DATA[METHOD][$key] ?? $default;
    is_array($var) || strpos($var, ',') && $var = explode(',', $var);
    return is_array($var) ? array_map(function ($val) {
        return intval($val);
    }, $var) : intval($var);
}
function IID(int $index, $default = 0)
{
    if (empty(ROUTE['path'][$index])) {
        return $default;
    }

    $var = strpos(ROUTE['path'][$index], ',') ? explode(',', ROUTE['path'][$index]) : ROUTE['path'][$index];
    return is_array($var) ? array_map(function ($val) {
        return intval($val);
    }, $var) : intval($var);
}
function F($key, $filter = FILTER_SANITIZE_SPECIAL_CHARS, $options = null)
{
    $var = $_GET[$key] ?? $_POST[$key] ?? DATA[METHOD][$key] ?? null;
    if (is_array($var)) {
        return $var;
    }

    $var && $var = filter_var($var, $filter, $options);
    return null === $var ? null : trim($var);
}
function getip($int = false)
{
    $ip = empty($_SERVER['HTTP_CLIENT_IP']) ? false : $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) {
            array_unshift($ips, $ip);
            $ip = false;
        }
        $count = count($ips);
        for ($i = 0; $i !== $count; ++$i) {
            if (!preg_match('/^(10│172.16│192.168)./', $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    $ip = $ip ?: $_SERVER['REMOTE_ADDR'];
    return $int ? (int) sprintf('%u', ip2long($ip)) : $ip;
}
function SumDir($dir)
{
    if (file_exists($dir) && $h = opendir($dir)) {
        while (false !== ($item = readdir($h))) {
            if ('.' !== $item && '..' !== $item) {
                if (is_dir($dir . '/' . $item)) {
                    $i += SumDir($dir . '/' . $item);
                } else {
                    ++$i;
                }
            }
        }
        closedir($h);
    }
    ;
    return $i;
}
