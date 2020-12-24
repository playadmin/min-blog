<?php
namespace root\state;

use libs\db;

class tags
{
    const TABLE = 'tag';
    const FILE = P_CACHE . 'data/tags';
    private static $tags;
    public static function get($map = false)
    {
        if (!self::$tags && !self::$tags = getLazyCache(self::FILE)) {
            self::getFromDb();
        }
        return $map ? self::$tags : array_values(self::$tags);
    }
    public static function getByShow($map = false)
    {
        $data = [];
        if (self::$tags || self::get(true)) {
            foreach (self::$tags as $v) {
                $map ? $data[$v['show']][$v['tid']] = $v : $data[$v['show']][] = $v;
            }
        }
        return $data;
    }
    public static function getTidsByShow()
    {
        $data = [];
        if (self::$tags || self::get(true)) {
            foreach (self::$tags as $v) {
                $data[$v['show']][] = $v['tid'];
            }
        }
        return $data;
    }
    public static function getFromDb()
    {
        $db = db::init(self::TABLE);
        $h = $db->order('sort')->fetch();
        self::$tags = [];
        while ($r = $h->fetch(\PDO::FETCH_ASSOC)) {
            self::$tags[$r['tid']] = $r;
        }
        setLazyCache(self::FILE, self::$tags);
    }
    public static function del()
    {
        return setLazyCache(self::FILE, false);
    }
}
