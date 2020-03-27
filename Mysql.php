<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/13
 * Time: 10:25
 */
require './lib/mysql.php';
/**
 * 初始化数据库信息
 */
$conf = require './conf.php';

$obj = new Mysql($conf['mysql']);
$obj->import_data(glob('./table.sql')); //创建表

$sqlFiles = glob("./sql/*.sql");
echo "正在导入以下sql文件:\r\n";
var_dump($sqlFiles);
$obj->import_data($sqlFiles); //导入
