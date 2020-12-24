<?php
namespace libs;

class file
{
    const PREG_IMG_TAG = '/<img\s+src=\"([^\"]+)\"/iU';
    const PREG_IMG_MARKDOWN = '/!\[([^\]]*)\]\s*\(([^\)]+)\)/iU';
    private static $files, $url, $path, $tmpLen, $tmpDir, $makedir;
    private static function getSrc($src)
    {
        $src = trim($src);
        if (U_TMP === substr($src, 0, self::$tmpLen) && (self::$makedir || self::$makedir = MakeDir(self::$path))) {
            $pathinfo = pathinfo($src);
            self::$tmpDir || self::$tmpDir = P_IN . ltrim($pathinfo['dirname']);
            self::$files[] = $pathinfo['basename'];
            $tmpFile = P_IN . ltrim($src, '/');
            $mvFile = self::$path . "/{$pathinfo['basename']}";
            if (!rename($tmpFile, $mvFile)) {
                throw new \Exception("重命名失败[{$mvFile}]");
            }

            $src = self::$url . "/{$pathinfo['basename']}";
        } else {
            self::$files[] = basename($src);
        }
        return $src;
    }
    private static function replaceHtmlImg($match)
    {
        $src = self::getSrc($match[1]);
        return "<img src=\"{$src}\"";
    }
    private static function replaceMarkDownImg($match)
    {
        $src = self::getSrc($match[2]);
        return "![{$match[1]}]({$src})";
    }
    private static function checkFiles($path, $reserve)
    {
        if ($scan = scandir($path)) {
            $files = $reserve ? array_merge(self::files, $reserve) : self::$files;
            foreach ($scan as $v) {
                if ('.' === $v || '..' === $v) {
                    continue;
                }

                if (!in_array($v, self::$files)) {
                    unlink("{$path}/{$v}");
                }
            }
        }
    }
    public static function delTmpDir()
    {
        if (self::$tmpDir && $scan = scandir(self::$tmpDir)) {
            foreach ($scan as $v) {
                if ('.' !== $v && '..' !== $v) {
                    unlink(self::$tmpDir . "/{$v}");
                }
            }
            rmdir(self::$tmpDir);
        }
    }

    /**
     * @param html string html内容
     * @param url string 文件目录的相对路径
     * @param reserve array 需要额外保留的文件（仅文件名）
     * @param MarkDown bool 是否是MarkDown文本
     */
    public static function ReplaceHtml($html, $url, $reserve = [], $MarkDown = false)
    {
        self::$tmpDir = '';
        self::$files = [];
        self::$url = '/' . $url;
        self::$makedir = false;
        self::$path = P_IN . trim($url, '/');
        self::$tmpLen = mb_strlen(U_TMP);
        $html = preg_replace_callback(self::PREG_IMG_TAG, 'self::replaceHtmlImg', $html);
        $MarkDown && $html = preg_replace_callback(self::PREG_IMG_MARKDOWN, 'self::replaceMarkDownImg', $html);
        self::$files && self::checkFiles(self::$path, $reserve);
        return $html;
    }
}
