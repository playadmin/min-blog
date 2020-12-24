<?php
namespace ctrl;

use libs\view;
use model\install;

class index
{
    public static function init()
    {
        $cfg = P_ROOT . 'config/system.config.php';
        if (is_file($cfg) && $installed = !!file_get_contents($cfg)) {
            require P_IN . '404.html';
            die;
        }
        define('SYS', require P_ROOT . 'config/system.default.php');
    }

    public static function index()
    {
        view::Display();
    }
    public static function check()
    {
        $m = new install;
        list($success, $error, $noempty) = $m->checkDir();
        view::assign('success', $success);
        view::assign('error', $error);
        view::assign('noempty', $noempty);
        view::Display();
    }
    public static function install()
    {
        if ('POST' === METHOD) {
            $m = new install;
            if (!$cfg = $m->initCfg()) {
                $err = $m->getError();
                response(400, ['msg' => '<p>' . implode('</p><p>', $err) . '</p>']);
            }
            if (!$m->checkDb($cfg)) {
                response(400, ['msg' => '连接数据库失败，请检查数据库配置！<br>' . $m->getError()]);
            }
            if (!$r = $m->initDataBase($cfg)) {
                response(400, ['msg' => $m->getError()]);
            }
            if (!$m->saveCfg($cfg)) {
                response(400, ['msg' => $m->getError()]);
            }
            $m->cleanDir();
            response(200, ['msg' => '安装完成', 'data' => [
                'url' => rtrim($cfg['SITE']['url'], '/'),
                'index' => $cfg['SITE']['path'],
                'admin' => $cfg['SITE']['path'] . 'ad/#/login',
            ]]);
        } else {
            view::Display();
        }
    }
}
