<?php
namespace model;

use root\base\model;
use root\state\tags as cache;

class tags extends model
{
    const TABLE = 'tag';
    private function data()
    {
        $data = [];
        $tag = F('tag');
        $sort = ID('sort', -1);
        $show = ID('show', -1);
        $mark = F('mark');
        if (isset($tag)) {
            if (!$tag) {
                return $this->error(['msg' => '请填写标签名称', 'field' => 'tag']);
            } elseif (32 < mb_strlen($tag)) {
                return $this->error(['msg' => '标签名称不能超过32个字符', 'field' => 'tag']);
            } else {
                $data['tag'] = $tag;
            }
        }
        if (isset($mark)) {
            if (255 < mb_strlen($mark)) {
                return $this->error(['msg' => '备注信息不能超过255个字符', 'field' => 'mark']);
            } else {
                $data['mark'] = $mark;
            }
        }
        -1 < $show && $data['show'] = $show;
        -1 < $sort && $data['sort'] = $sort;
        if (!$data) {
            return $this->error('非法操作');
        }
        return $data;
    }
    public function get()
    {
        $db = $this->db(self::TABLE);
        $data = $db->order('sort')->select();
        return $data;
    }
    public function add()
    {
        if (!$data = $this->data()) {
            return false;
        }
        if (!isset($data['tag'])) {
            return $this->error(['msg' => '请填写标签名称', 'field' => 'tag']);
        }
        $db = $this->db(self::TABLE);
        if ($data['tid'] = (int) $db->Insert($data)) {
            cache::del();
            return $data;
        } else {
            return $this->error($db->getError() ?: '未知错误');
        }
    }
    public function set($tid)
    {
        if (!$data = $this->data()) {
            return false;
        }
        $db = $this->db(self::TABLE);
        if ($db->where("`tid`={$tid}")->Update($data)) {
            cache::del();
            return $data;
        } else {
            return $this->error($db->getError() ?: '没有变更');
        }
    }
    public function del($tid)
    {
        $db = $this->db(self::TABLE);
        if ($n = $db->where(['tid' => $tid])->Delete()) {
            cache::del();
            return $n;
        } else {
            return $this->error($db->getError() ?: '删除失败');
        }
    }
}
