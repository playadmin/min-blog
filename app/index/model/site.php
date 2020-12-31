<?php
namespace model;

use libs\file;
use root\base\model;

class site extends model
{
    const SYS_CFG_PATH = P_ROOT . 'config/system.config.php';
    const SYS_DEF_PATH = P_ROOT . 'config/system.default.php';
    const ABOUT_PAGE = P_ROOT . 'config/system.default.php';
    const CONTACT_PAGE = P_ROOT . 'config/system.default.php';
    const FIELDS = ['SITE', 'PAGE', 'CACHE'];
    public function getSite()
    {
        foreach (self::FIELDS as $k) {
            $cfg[$k] = SYS[$k];
        }
        return $cfg;
    }
    public function setSite()
    {
        $sets = require self::SYS_DEF_PATH;
        if (is_file(self::SYS_CFG_PATH) && $org = SYS) {
            isset($org['DB']) && $sets['DB'] = $org['DB'];
            $sets['HASH_KEY'] = $org['HASH_KEY'];
            $sets['NAV'] = $org['NAV'];
        }
        foreach ($_POST as $k => $v) {
            $sets[$k] = empty($sets[$k]) ? $v : $v + $sets[$k];
        }
        $str = "<?php\nreturn " . var_export($sets, true) . ';';
        if (file_put_contents(self::SYS_CFG_PATH, $str)) {
            return ['msg' => '保存成功', 'data' => $sets];
        } else {
            return $this->error('保存失败，请检查权限：' . self::SYS_CFG_PATH);
        }
    }
    public function setNav()
    {
        $tid = ID('tid');
        if (!$name = trim($_POST['name'])) {
            return $this->error('请填写唯一标识符');
        }
        if (!$title = trim($_POST['title'])) {
            return $this->error('请填写链接名');
        }
        $add = intval($_POST['add'] ?? 0);
        $sort = intval($_POST['sort'] ?? 0);
        if ($add && isset(SYS['NAV'][$name])) {
            return $this->error($name . '已经存在');
        }
        $show = intval($_POST['show'] ?? 0);
        $sets = SYS;
        $sets['NAV'][$name] = $data = ['title' => $title, 'show' => $show, 'sort' => $sort, 'tid' => $tid];
        $sorts = [];
        foreach ($sets['NAV'] as $v) {
            $sorts[] = $v['sort'] ?? 0;
        }
        array_multisort($sorts, SORT_NUMERIC, $sets['NAV']);

        $str = "<?php\nreturn " . var_export($sets, true) . ';';
        if (file_put_contents(self::SYS_CFG_PATH, $str)) {
            $data['name'] = $name;
            return ['msg' => '保存成功', 'data' => $data];
        } else {
            return $this->error('保存失败，请检查权限：' . self::SYS_CFG_PATH);
        }
    }
    public function delNav($name)
    {
        $msg = '';
        $error = [];
        if (!empty($sets['NAV'][$name]['tid'])) {
            $html = SYS['PATH']['page'] . $name . '.html';
            $dir = SYS['PATH']['page_img'] . $name;
            DelDir($dir, true);
            if (is_file($html) && !unlink($html)) {
                $error[] = "删除文件失败,需要手动删除文件[{$html}]";
            }
            if (file_exists($dir)) {
                $error[] = "删除附件失败,需要手动清理目录[{$dir}]";
            }
        }
        if (isset(SYS['NAV'][$name])) {
            $sets = SYS;
            unset($set['NAV'][$name]);
            $str = "<?php\nreturn " . var_export($sets, true) . ';';
            if (!file_put_contents(self::SYS_CFG_PATH, $str)) {
                $error[] = '保存失败，请检查权限：' . self::SYS_CFG_PATH;
            } else {
                $msg = '保存成功';
            }
        }
        $data = ['msg' => $msg];
        $error && $data['err'] = $error;
        return $data;
    }
    public function setPage($name)
    {
        $html = SYS['PATH']['page'] . $name . '.html';
        $dir = SYS['PATH']['page_img'] . $name;
        if (!$content = isset($_POST['content']) ? trim($_POST['content']) : '') {
            return $this->error('页面内容不能空');
        }
        $src = U_ROOT . substr(SYS['PATH']['page_img'], LEN_IN);
        $content = file::ReplaceHtml($content, $src);
        if (file_put_contents($html, $content)) {
            file::delTmpDir();
            return ['msg' => '保存成功'];
        } else {
            return $this->error('保存失败');
        }
    }
    public function getPage($name)
    {
        $html = SYS['PATH']['page'] . $name . '.html';
        return is_file($html) ? file_get_contents($html) : '';
    }
    public function getTotal()
    {
        $db = $this->db();
        $data['tags'] = $db->table('art_tag')->field('COUNT(*) as `sum`, `tid`')->group('tid')->select();
        $data['total'] = $db->table('article')->find('COUNT(*)');
        return $data;
    }
}
