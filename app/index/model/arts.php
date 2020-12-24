<?php
namespace model;

use libs\file;
use root\base\model;
use root\state\art;

class arts extends model
{
    const TABLE = 'article';
    public function gets()
    {
        $p = ID('p', 1);
        $page = ['num' => 10, 'max' => 100, 'p' => $p, 'return' => true];
        $db = $this->db(self::TABLE);
        $data['data'] = $db->order('id DESC')->page($page)->select();
        $data['page'] = $db->getPage();
        return $data;
    }
    public function get($id)
    {
        $db = $this->db(self::TABLE);
        $data = $db->where("`id`={$id}")->find();
        return $data;
    }
    public function add()
    {
        if (!isset($_POST['content']) || !$content = trim($_POST['content'])) {
            return $this->error('内容不能空');
        }
        if ($tid = ID('tid')) {
            is_array($tid) || $tid = [$tid];
            if (count($tid) > 20) {
                return $this->error('标签数量不能超过20个');
            }
            sort($tid, SORT_NUMERIC);
            $data['tids'] = implode(',', $tid);
        } else {
            $tid = [0];
            $data['tids'] = '';
        }
        $data['addtime'] = TIME;
        $data['title'] = F('title');
        $db = $this->db('article');
        $db->begin();
        $id = (int) $db->insert($data);
        $content = file::ReplaceHtml($content, $this->src($id));
        $result = $db->where("`id`={$id}")->update(['content' => $content]);
        $setTag = !$tid || $this->setArtTag($id, $tid);
        $db->commit();
        file::delTmpDir();
        if ($result && $setTag) {
            $data['id'] = $id;
            $res = ['msg' => '保存成功', 'data' => $data];
            $data['content'] = $content;
            empty(SYS['CACHE']['art']) || art::setCache($id, $data);
            return $res;
        } else {
            return $this->error($db->getError());
        }
    }
    public function set($id)
    {
        $tid = ID('tid', -1);
        $where = "`id`={$id}";
        if (is_array($tid)) {
            if (count($tid) > 20) {
                return $this->error('标签数量不能超过20个');
            }
            sort($tid, SORT_NUMERIC);
            $tids = implode(',', $tid);
            $db = $this->db('article');
            if ($tids === $db->where($where)->find('tids')) {
                $tid = -1;
            } else {
                $data['tids'] = implode(',', $tid);
            }
        }
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $title = F('title');
        $title && $data['title'] = $title;
        $content && $data['content'] = file::ReplaceHtml($content, $this->src($id));
        if (!isset($data)) {
            return $this->error('非法操作');
        }
        $data['lasttime'] = TIME;
        isset($db) || $db = $this->db('article');
        $db->begin();
        $art = $db->where($where)->update($data);
        $tag = -1 !== $tid && $this->setArtTag($id, $tid, true);
        $art && $content && file::delTmpDir();
        $db->commit();
        if ($art) {
            empty(SYS['CACHE']['art']) || art::setCache($id);
            return ['msg' => '保存成功', 'data' => $data];
        } else {
            return $this->error($db->getError() ?: '没有变更');
        }
    }

    public function setTids($id)
    {
        $tid = ID('tid', -1);
        if (is_array($tid)) {
            if (count($tid) > 20) {
                return $this->error('标签数量不能超过20个');
            }
            sort($tid, SORT_NUMERIC);
        } else {
            return $this->error('非法操作');
        }
        $save = ['tids' => implode(',', $tid)];
        $where['id'] = $id;
        $db = $this->db('article');
        $db->begin();
        if (!$db->where($where)->update($save)) {
            return $this->error('没有变更');
        }
        if ($n = $this->setArtTag($id, $tid, true)) {
            $db->commit();
            empty(SYS['CACHE']['art']) || art::setCache($id);
            return ['msg' => '保存成功', 'data' => $save];
        } else {
            return $this->error('保存失败');
        }
    }
    public function del($id)
    {
        $where = ['id' => $id];
        $db = $this->db(self::TABLE);
        if ($n = $db->where($where)->Delete()) {
            $db->table('art_tag')->where($where)->delete();
            $this->delFiles($id);
            empty(SYS['CACHE']['art']) || art::setCache($id, false);
            return $n;
        } else {
            return $this->error($db->getError() ?: '删除失败');
        }
    }
    private function src($id)
    {
        return U_ROOT . substr(SYS['PATH']['art_img'] . id_path($id), LEN_IN);
    }
    private function setArtTag($id, array $tids, $d = false)
    {
        $n = 0;
        $pdo = $this->db()->PDO;
        if ($d) {
            $sql = "DELETE FROM `art_tag` WHERE `id` ";
            $sql .= is_array($id) ? 'IN(' . implode(',', $id) . ')' : "= {$id}";
            $n += $pdo->exec($sql);
        }
        if ($tids || $tids = [0]) {
            $sql = "INSERT IGNORE INTO `art_tag` (`id`,`tid`) VALUES";
            if (is_array($id)) {
                foreach ($id as $i) {
                    foreach ($tids as $tid) {
                        $values[] = "({$i},{$tid})";
                    }
                }
            } else {
                foreach ($tids as $tid) {
                    $values[] = "({$id},{$tid})";
                }
            }
            $sql .= implode(',', $values);
            $n += $pdo->exec($sql);
        }
        return $n;
    }
    private function delFiles($id)
    {
        if (is_array($id)) {
            foreach ($id as $i) {
                $dir = SYS['PATH']['art_img'] . id_path($i);
                DelDir($dir);
            }
        } else {
            $dir = SYS['PATH']['art_img'] . id_path($id);
            DelDir($dir);
        }
    }
}
