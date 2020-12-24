<?php
namespace ctrl;

use base\needlogin;

class upload extends needlogin
{
    private static $info, $imgs, $srcs, $editor, $path;
    public static function init()
    {
        parent::init();
        self::$path = UPLOAD_TMP;
    }
    public static function headimg()
    {
        self::upload();
        self::returnSuccess();
    }
    public static function img()
    {
        self::upload();
        self::returnSuccess();
    }
    private static function upload()
    {
        self::$editor = F('editor');
        $up = new \libs\upload();
        $up->set('path', self::$path);
        $up->set('allowType', ['.jpg', '.gif', '.png', '.jpeg']);
        $up->set('maxSize', 1024 * 1024);
        $result = $up->upload();
        self::$info = $up->getInfo();
        $err = $up->getError();
        $err && self::returnErr($err);
        foreach (self::$info as $v) {
            self::$imgs[] = basename($v['path']);
            self::$srcs[] = $v['src'];
        }
    }
    private static function returnErr($err)
    {
        if ($err) {
            is_array($err) && $err = implode(';', $err);
        } else {
            $err = '未知错误';
        }
        switch (self::$editor) {
            case 'wang':
                die(json_encode(['errno' => 1, 'msg' => $err]));
                break;
            case 'sim':
                die(json_encode(['success' => false, 'msg' => $err]));
                break;
            case 'md':
                die(json_encode(['success' => 0, 'message' => $err]));
                break;
            default:
                response(400, ['msg' => $err, 'data' => $_FILES]);
                break;
        }
    }
    private static function returnSuccess()
    {
        switch (self::$editor) {
            case 'wang':
                $data = ['errno' => 0, 'data' => self::$srcs];
                break;
            case 'md':
                $data = ['success' => 1, 'message' => '上传成功', 'url' => self::$srcs[0]];
                break;
            case 'sim':
                $data = ['success' => true, 'file_path' => self::$srcs[0]];
                break;
            case 'ck':
                $data = ['uploaded' => 1, 'url' => self::$srcs[0]];
                break;
            default:
                $data = [
                    'status' => 200,
                    'data' => 1 == count(self::$info) ? self::$info[0] : self::$info,
                ];
                break;
        }
        isset(DATA['params']) && $data['params'] = DATA['params'];
        die(json_encode($data, 320));
    }
}
