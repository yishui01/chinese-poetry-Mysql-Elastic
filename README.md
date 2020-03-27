# chinese-poetry-Mysql-Elastic

## 简介
  诗词集 [chinese-poetry](https://github.com/chinese-poetry/chinese-poetry) 的数据导入工具
### 功能
- 一键转换 [chinese-poetry](https://github.com/chinese-poetry/chinese-poetry) 仓库里的json数据转换成 sql 文件
- 一键导入mysql、
- 一键导入Elasticsearch (需要先导入mysql)


## 环境

- php 5.6 +
- git


## 使用
目前本仓库内已经转换好了sql文件，可以直接使用，sql内诗词已全部转换为**简体中文**

### 将sql文件导入mysql

1、配置conf.php 数据库地址

2、导入到mysql
```shell script
php Mysql.php
```
<hr />

### 查询mysql数据导入elasticsearch (必须先导入mysql才可导出到ES)

1、配置conf.php 数据库地址

2、导入到ES
```shell script
php ES.php
```
<hr />


### 转换为.sql文件

1、在当前目录下载要转换的诗词库
```shell script
git clone https://github.com/chinese-poetry/chinese-poetry.git chinese-poetry
```
2、安装composer依赖
```shell script
composer install
```
3、执行，在当前目录会重新生成sql目录
```shell script
php Worker.php
```

