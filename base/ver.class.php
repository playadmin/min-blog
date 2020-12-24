<?php
namespace root\base;

class ver
{
    const MSG_KEY = 'smg-ver-code:'; // 短信验证码的缓存 key
    const KEY = '@%^**#加密验证码用的字符串随便填写@%^**#';
    const IMG_EXPIRE = 300; //图片验证码过期时间
    const MSG_EXPIRE = 300; //短信验证码过期时间
    const MSG_LIMIT = 90; //短信验证码获取间隔
    const MSG_TITLE = '短信签名';
    private static function key($key, $tm = '')
    {
        return md5(self::KEY . session_id() . "{$key}@{$tm}");
    }

    /**
     * 图片验证码
     * @return array [data=>图片的base64, ct=>当前时间, ctk=>验证用的hash]
     */
    public static function Img()
    {
        $ver = new \libs\verimg(120, 42, 4, 16);
        $data['data'] = $ver->base64();
        $code = strtolower($ver->getCode());
        $data['ct'] = TIME;
        $data['ctk'] = self::key($code, TIME);
        return $data;
    }

    /**
     * 验证图片验证码
     * @return 成功：0, 失败：提示信息
     */
    public static function CkImg($code = '')
    {
        $code || $code = F('vercode');
        $ctk = F('ctk');
        $ct = ID('ct');
        if (!$code) {
            return '请填写验证码';
        }

        if (!$ctk || !$ct) {
            return '图片验证码不正确';
        }

        if (TIME - $ct > self::IMG_EXPIRE) {
            return '图片验证码已失效，请重新获取';
        }

        if (self::key(strtolower($code), $ct) != $ctk) {
            return '图片验证码不正确';
        }

        return 0;
    }
}
