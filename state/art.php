<?php
namespace root\state;

use libs\db;
use libs\view;
use root\state\tags;

view::init();

class art
{
    const TABLE = 'article';
    const TPL = 'index/static/art-content';
    const PATH = P_TMP . '/article/' . THEME . '/';
    const FIELDS = '`id`,`tids`,`title`,`content`,`addtime`,`lasttime`';

    public static function setCache($id, $data = null)
    {
        $file = self::PATH . id_path($id);
        if (false === $data) {
            return is_file($file) && unlink($file);
        }
        if (!$data && !$data = self::getArtFromDb($id)) {
            return false;
        }
        $html = self::fetch($data);
        $result = MakeDir(dirname($file)) && file_put_contents($file, $html, LOCK_EX);
        if (false === $result) {
            throw new \Exception("file can not write:{$file}");
        }
        return $html;
    }
    public static function getCache($id)
    {
        $file = self::PATH . id_path($id);
        return is_file($file) ? ReadFileSH($file) : self::resetCache($file, $id);
    }
    private static function fetch($data)
    {
        defined('TAGS') || define('TAGS', tags::getByShow(true));
        view::assign('art', $data);
        $html = view::fetch(self::TPL);
        $html && $html = substr($html, 16);
        return $html;
    }
    private static function getArtFromDb($id)
    {
        return db::init(self::TABLE)->field(self::FIELDS)->where("`id`={$id}")->find();
    }
    private static function resetCache($file, $id)
    {
        MakeDir(dirname($file));
        if ('WINDOWS' === ZPHP_OS) {
            $lock_path = P_CACHE . 'lock_file/';
            $lock_file = $lock_path . md5($file);
            file_exists($lock_path) || MakeDir($lock_path, 0755, true);
            if (!$h = fopen($lock_file, 'w')) {
                throw new \Exception('file can not write: ' . $lock_file);
            }
            if (flock($h, LOCK_EX)) {
                if ($data = self::getArtFromDb($id)) {
                    $html = self::fetch($data);
                    file_put_contents($file, $html, LOCK_EX);
                }
                flock($h, LOCK_UN);
                fclose($h);
                return $html ?? false;
            }
            flock($h, LOCK_UN);
            fclose($h);
        } else {
            if (!$h = fopen($file, 'w')) {
                throw new \Exception('file can not write: ' . $file);
            }
            if (flock($h, LOCK_EX | LOCK_NB)) {
                if ($data = self::getArtFromDb($id)) {
                    $html = self::fetch($data);
                    fwrite($h, $html);
                }
                flock($h, LOCK_UN);
                fclose($h);
                return $html ?? false;
            }
        }
        return ReadFileSH($file);
    }
}
