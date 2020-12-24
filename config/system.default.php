<?php
return [
    'SITE' => [
        'name' => 'MyBlog',
        'title' => 'MyBlog',
        'keywords' => '',
        'description' => '',
        'search' => false,
        'style' => 'teal',
    ],
    'CACHE_MOD' => 'file',
    'CACHE' => [
        'art' => true, // 文章内容静态化(有修改时自动更新)
        'newly' => [60, 10], // [时间，数据量]
        'list' => [60, 10], // tag列表页 [时间，缓存前几页]
        'index' => [60, 10], // 首页列表 [时间，缓存前几页]
    ],
    'NAV' => [ // 导航配置
        'about' => ['title' => 'About', 'show' => true, 'sort' => 0, 'tid' => 0],
        'contact' => ['title' => 'Contact', 'show' => true, 'sort' => 1, 'tid' => 0],
    ],
    'PAGE' => [
        'max' => 100,
        'num' => 20,
    ],
    'PATH' => [
        'page' => P_ROOT . 'page/',
        'art_img' => P_PUBLIC . 'files/art/',
        'page_img' => P_PUBLIC . 'files/page/',
    ],
    'HASH_KEY' => [
        'user' => 'User @ 加密字符串 +- ==',
    ],
    'TOKEN_NAME' => [
        'index' => 'TOKEN',
    ],
    'MAX_TOKEN' => [ //用户最大登录token的数量(超出则删除最早的token) 键名是 APP_NAME
        'index' => 2, //index登录token的最大数量
    ],
    'LOGIN_EXPIRE' => [
        'index' => 86400, //index默认登录保持的时间(一天)
    ],
    'REMEMBER_EXPIRE' => [
        'index' => 604800, //index记住登录的保持时间(一周)
    ],
];
