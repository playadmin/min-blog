<?php
namespace ctrl;

use base\needlogin;
use model\site;
use model\tags;
use root\state\tags as cache;

class sets extends needlogin
{
    public static function get_tags()
    {
        $data = cache::get();
        response(200, ['data' => $data]);
    }
    public static function set_tag()
    {
        if (!$tid = ID('tid')) {
            response(400, '参数错误:tid');
        }
        $m = new tags;
        if ($data = $m->set($tid)) {
            response(200, ['data' => $data]);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
    public static function add_tag()
    {
        $m = new tags;
        if ($data = $m->add()) {
            response(200, ['data' => $data]);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
    public static function del_tags()
    {
        if (!$tid = ID('tid')) {
            response(400, '参数错误:tid');
        }
        $m = new tags;
        if ($n = $m->del($tid)) {
            response(200, ['num' => $n]);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }

    public static function get_site()
    {
        $m = new site;
        $data = $m->getSite();
        response(200, ['data' => $data]);
    }
    public static function set_site()
    {
        $m = new site;
        if ($data = $m->setSite($tid)) {
            response(200, $data);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
    public static function get_navs()
    {
        if (empty(SYS['NAV'])) {
            response(200, []);
        }

        foreach (SYS['NAV'] as $k => $v) {
            $v['name'] = $k;
            $data[] = $v;
        }
        response(200, ['data' => $data]);
    }
    public static function set_nav()
    {
        $m = new site;
        if ($data = $m->setNav()) {
            response(200, $data);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
    public static function del_nav()
    {
        if (!$name = F('name')) {
            response(400, '参数错误:name');
        }
        $m = new site;
        if ($data = $m->delNav()) {
            response(200, $data);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
    public static function set_page()
    {
        if (!$name = F('name')) {
            response(400, '参数错误:name');
        }
        if (!isset(SYS['NAV'][$name])) {
            response(403, ['msg' => '页面不存在！', 'url' => '/sets/pages']);
        }
        $m = new site;
        if ($data = $m->setPage($name)) {
            response(200, $data);
        } else {
            $err = $m->getError();
            response(400, $err);
        }
    }
    public static function get_page()
    {
        if (!$name = F('name')) {
            response(400, '参数错误:name');
        }
        if (!isset(SYS['NAV'][$name]) || !empty(SYS['NAV'][$name]['tid'])) {
            response(403, ['msg' => '页面不存在！', 'url' => '/sets/pages']);
        }
        $m = new site;
        $data['data'] = $m->getPage($name);
        $data['title'] = SYS['NAV'][$name]['title'];
        response(200, ['data' => $data]);
    }
    public static function get_total()
    {
        $m = new site;
        $data = $m->getTotal();
        response(200, ['data' => $data]);
    }
}
