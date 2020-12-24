<?php
namespace ctrl;

use base\ctrl;
use libs\view;
use root\state\art;
use root\state\tags;

class index extends ctrl
{
    const CACHE_TIME = 300; // 默认缓存时间
    const MAX_PAGE = 10; // 默认缓存前10页
    public static function init()
    {
        parent::init();
        define('TAGS', tags::getByShow(true));
        self::nav();
    }
    public static function tag($tid = 0)
    {
        if (!$tid && !$tid = intval(ROUTE['params']['tid'])) {
            parent::_404();
        }
        if (isset(TAGS[1][$tid])) {
            defined('USER') || parent::_404();
            $tag = TAGS[1][$tid]['tag'];
        }
        if (!defined('USER') && isset(TAGS[1][$tid])) {
            parent::_404();
        }
        $p = intval(ROUTE['params']['p']) ?: 1;
        $expire = SYS['CACHE']['list'][0] ?? self::CACHE_TIME;
        $maxPage = SYS['CACHE']['list'][1] ?? self::MAX_PAGE;

        if (defined('USER') || !$expire || $p > $maxPage || !$html = view::GetCache($expire, 'tag', ['p', 'tid'])) {
            $m = new \model\data;
            self::newly($m, $tid);
            $res = $m->getsByTag($tid);
            view::assign('tag', $tag ?? TAGS[0][$tid]['tag']);
            view::assign('list', $res['data']);
            view::assign('page', $res['page']);
            view::display();
        } else {
            echo $html;
        }
    }
    public static function art()
    {
        $id = IID(0);
        if (empty(SYS['CACHE']['art'])) {
            $m = new \model\data;
            if (!$art = $m->get($id)) {
                parent::_404();
            }
            self::newly($m);
            view::assign('art', $art);
            view::display();
        } elseif (!$content = art::getCache($id)) {
            parent::_404();
        } else {
            self::newly();
            view::assign('content', $content);
            view::display('index/static/art');
        }
    }
    public static function index()
    {
        $p = intval(ROUTE['params']['p']) ?: 1;
        $expire = SYS['CACHE']['index'][0] ?? self::CACHE_TIME;
        $maxPage = SYS['CACHE']['index'][1] ?? self::MAX_PAGE;

        if (defined('USER') || !$expire || $p > $maxPage || !$html = view::GetCache($expire, 'index', ['p'])) {
            $m = new \model\data;
            $res = $m->gets();
            view::assign('list', $res['data']);
            view::assign('page', $res['page']);
            view::display('index');
        } else {
            echo $html;
        }
    }
    public static function page()
    {
        if (!$name = ROUTE['params']['name']) {
            parent::_404();
        }
        view::assign('active', $name);
        if (!empty(SYS['NAV'][$name]['tid'])) {
            self::tag(SYS['NAV'][$name]['tid']);
        } elseif (!is_file($file = SYS['PATH']['page'] . $name . '.html')) {
            parent::_404();
        } else {
            $html = file_get_contents($file);
            self::newly();
            view::assign('html', $html);
            view::display();
        }
    }
    public static function search()
    {
        if (empty(SYS['SITE']['search'])) {
            parent::_404();
        }
        if (!$keywords = trim($_GET['w'])) {
            return self::index();
        }
        $m = new \model\data;
        self::newly($m);
        $res = $m->search($keywords);
        view::assign('list', $res['data']);
        view::assign('page', $res['page']);
        view::display();
    }
    private static function newly($m = null, $tid = 0)
    {
        $num = SYS['CACHE']['newly'][1] ?? 10;
        $expire = SYS['CACHE']['newly'][0] ?? self::CACHE_TIME;
        if (!$expire) {
            $m || $m = new \model\data;
            $newly = $m->getNewly($tid, $num);
        } else {
            $file = P_CACHE . "newly/{$tid}";
            $newly = getLazyCache($file, $expire);
            if (false === $newly) {
                $m || $m = new \model\data;
                $newly = $m->getNewly($tid, $num);
                setLazyCache($file, $newly);
            }
        }
        view::assign('newly', $newly);
    }
    private static function nav()
    {
        $nav = [];
        if (!empty(SYS['NAV'])) {
            foreach (SYS['NAV'] as $k => $v) {
                if (!$v['show']) {
                    continue;
                }
                $nav[] = [$v['title'], U_HOME . "p/{$k}", $k];
            }
        }
        view::assign('nav', $nav);
    }
}
