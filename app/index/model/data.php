<?php
namespace model;

use root\base\model;

class data extends model
{
    const PAGE_MAX = 10;
    const PAGE_NUM = 20;
    public function get(int $id)
    {
        $db = $this->db('article');
        $db->field('`id`,`tids`,`title`,`content`,`addtime`,`lasttime`');
        $id ? $db->where("`id`={$id}") : $db->order('id DESC');
        $data = $db->find();
        return $data;
    }
    public function getNewly(int $tid = 0, int $num = 5)
    {
        $db = $this->db();
        if ($tid || (!defined('USER') && !empty(TAGS[1]))) {
            $where = $tid ? "`tid`={$tid}" : '`tid` NOT IN(' . implode(',', array_keys(TAGS[1])) . ')';
            $db->table('art_tag')
                ->where($where)->order('id DESC')
                ->limit($num);
            if (!$ids = $db->select('DISTINCT id')) {
                return [];
            }
            $ids = implode(',', $ids);
            $data = $db->table('article')
                ->field('`id`,`title`,`addtime`')
                ->where("`id` IN({$ids})")
                ->select();
        } else {
            $db->table('article')
                ->field('`id`,`title`,`addtime`')
                ->order('id DESC')
                ->limit($num);
            $data = $db->select();
        }
        return $data;
    }
    public function gets(int $p = 0)
    {
        $p || $p = intval(ROUTE['params']['p']) ?: 1;
        $page = [
            'num' => SYS['PAGE']['num'] ?? self::PAGE_NUM,
            'max' => SYS['PAGE']['max'] ?? self::PAGE_MAX,
            'p' => $p,
            'return' => true,
        ];
        $db = $this->db();
        if (!defined('USER') && !empty(TAGS[1])) {
            $where = '`tid` NOT IN(' . implode(',', array_keys(TAGS[1])) . ')';
            $db->table('art_tag')
                ->where($where)->order('id DESC')
                ->Page($page);
            if (!$ids = $db->select('DISTINCT id')) {
                return [];
            }
            $ids = implode(',', $ids);
            $data = $db->table('article')
                ->field('`id`,`title`,`addtime`')
                ->where("`id` IN({$ids})")
                ->select();
        } else {
            $data = $db->table('article')
                ->field('`id`,`title`,`addtime`')
                ->order('id DESC')
                ->Page($page)
                ->select();
        }
        $page = $data ? $this->page($db->getPage(), function (int $p) {
            return U_HOME . (1 === p ? '' : "index/{$p}");
        }) : [];
        return ['data' => $data, 'page' => $page];
    }
    public function getsByTag(int $tid, int $p = 0)
    {
        $p || $p = intval(ROUTE['params']['p']) ?: 1;
        $page = [
            'num' => SYS['PAGE']['num'] ?? self::PAGE_NUM,
            'max' => SYS['PAGE']['max'] ?? self::PAGE_MAX,
            'p' => $p,
            'return' => true,
        ];
        $db = $this->db();
        $where = "`tid`={$tid}";
        $db->table('art_tag')->where($where)->order('id DESC')->Page($page);
        if (!$ids = $db->select('DISTINCT id')) {
            return ['data' => [], 'page' => []];
        }
        $act = ROUTE['act'] === 'page' ? 'p' : 't';
        $data['page'] = $this->page($db->getPage(), function (int $p) use ($tid, $act) {
            return U_HOME . "{$act}/{$tid}/{$p}";
        });
        $ids = implode(',', $ids);
        $data['data'] = $db->table('article')
            ->field('`id`,`tids`,`title`,`addtime`,`lasttime`')
            ->where("`id` IN({$ids})")
            ->select();
        return $data;
    }
    public function search(string $s)
    {
        $p = IID(0);
        $page = [
            'num' => SYS['PAGE']['num'] ?? self::PAGE_NUM,
            'max' => SYS['PAGE']['max'] ?? self::PAGE_MAX,
            'p' => $p,
            'return' => true,
        ];
        $db = $this->db();
        $where = '`title` LIKE :s';
        $s = "%{$s}%";
        $db->table('article')
            ->field('`id`,`tids`,`title`,`addtime`,`lasttime`')
            ->where($where, ['s' => $s])
            ->Page($page);
        if ($data['data'] = $db->select()) {
            $str = urldecode($keywords);
            $data['page'] = $this->page($db->getPage(), function ($p) use ($str) {
                return U_HOME . "s?s={$str}&p={$p}";
            });
        } else {
            $data['page'] = [];
        }
        return $data;
    }
    public function page(array $page, callable $call, string $nourl = '')
    {
        unset($page['limit']);
        $nourl || $nourl = 'javascript:;';
        $page['first'] = $page['p'] === 1 ? $nourl : $call(1);
        $page['prev'] = $page['p'] > 1 ? $call($page['p'] - 1) : $nourl;
        $page['last'] = $page['p'] === $page['pages'] ? $nourl : $call($page['pages']);
        $page['next'] = $page['p'] < $page['pages'] ? $call($page['p'] + 1) : $nourl;

        if (1 < $page['pages']) {
            $rolls = 10;
            $pos = intval($rolls / 2);
            if ($pos < $page['p'] && $page['pages'] > $rolls) {
                $i = $page['p'] - $pos;
                $end = $i + $rolls - 1;
                $end > $page['pages'] && ($end = $page['pages']) && ($i = $end - $rolls + 1);
            } else {
                $i = 1;
                $end = $rolls > $page['pages'] ? $page['pages'] : $rolls;
            }
            for ($i; $i <= $end; $i++) {
                $page['list'][$i] = $page['p'] == $i ? 'javascript:;' : $call($i);
            }
        } else {
            $page['list'] = [];
        }
        return $page;
    }
}
