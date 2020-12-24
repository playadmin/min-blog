<?php
namespace ctrl;

use base\needlogin;
use model\arts;

class art extends needlogin
{
    public static function get_art()
    {
        if (!$id = ID('id')) {
            response(400, '参数错误:id');
        }
        $m = new arts;
        if ($data = $m->get($id)) {
            response(200, ['data' => $data]);
        } else {
            response(400, $m->getError() ?: '没有找到文章');
        }
    }
    public static function get_arts()
    {
        $m = new arts;
        $data = $m->gets();
        response(200, $data);
    }
    public static function set_art()
    {
        if (!$id = ID('id')) {
            response(400, '参数错误:id');
        }
        $m = new arts;
        if ($m->set($id)) {
            response(200);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
    public static function set_tids()
    {
        if (!$id = ID('id')) {
            response(400, '参数错误:id');
        }
        $m = new arts;
        if ($data = $m->setTids($id)) {
            response(200, $data);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
    public static function add_art()
    {
        $m = new arts;
        if ($data = $m->add()) {
            response(200, ['data' => $data]);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
    public static function del_arts()
    {
        if (!$id = ID('id')) {
            response(400, '参数错误:id');
        }
        $m = new arts;
        if ($n = $m->del($id)) {
            response(200, ['num' => $n]);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
}
