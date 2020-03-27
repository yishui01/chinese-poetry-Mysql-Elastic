<?php
//数据库配置
return [
    'mysql' => [
        'tableName' => 'poems', //存放诗词的mysql表名,不建议修改，这里改了那当前目录table.sql里面的table名也需要改
        'dbhost'    => '192.168.136.109',
        'dbport'    => '3306',
        'dbuser'    => 'root',
        'dbpw'      => '123456',
        'dbname'    => 'test_blog',
    ],

    'es' => [
        'host'               => ['192.168.136.109:9200'],
        'index'              => 'poems',
        'number_of_shards'   => '2',
        'number_of_replicas' => '0',
    ],

    'simple' => true,  //导出sql时是否转换为简体中文
];
