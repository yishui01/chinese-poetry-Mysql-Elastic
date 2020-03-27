<?php

class Mysql
{
    //数据库信息
    private $dbhost;
    private $dbuser;
    private $dbpw;
    private $dbport;
    private $dbname;
    private $dbcharset;
    private $tableName;

    public $link;

    public function __construct($data)
    {
        $this->dbhost = isset($data['dbhost']) ? $data['dbhost'] : '';
        $this->dbuser = isset($data['dbuser']) ? $data['dbuser'] : '';
        $this->dbpw = isset($data['dbpw']) ? $data['dbpw'] : '';
        $this->dbport = isset($data['dbport']) ? $data['dbport'] : '3306';
        $this->dbname = isset($data['dbname']) ? $data['dbname'] : '';
        $this->tableName = isset($data['tableName']) ? $data['tableName'] : 'poems';
        $this->dbcharset = isset($data['dbcharset']) ? $data['dbcharset'] : 'utf8mb4';
        $this->connect();
    }

    //链接设置数据库
    protected function connect()
    {
        $link = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpw, null, $this->dbport);
        if (!$link) {
            throw new \Exception("数据库连接失败");
        } else {
            $this->link = $link;
        }
        //mysql 版本
        //获得mysql版本
        $version = mysqli_get_server_info($this->link);
        //设置字符集
        if ($version > '4.1' && $this->dbcharset) {
            mysqli_query($link, "SET NAMES {$this->dbcharset}");
        }
        //选择数据库
        mysqli_select_db($this->link, $this->dbname);

    }

    /**
     * @param array $dbfile 要导入的sql数据文件
     */
    public function import_data($dbfiles)
    {
        $date = "\r\n\r\n【" . date('Y-m-d H:i:s', time()) . "】开始导入：\r\n";
        $logFile = "./importToMysql.log";
        file_put_contents($logFile, $date, FILE_APPEND);
        foreach ($dbfiles as $dbfile) {
            $sql = file_get_contents($dbfile);
            $status = mysqli_multi_query($this->link, $sql);
            $msg = $dbfile . "导入数据库成功\r\n";
            if (!$status) {
                $msg = 'Err******:' . $dbfile . "导入数据库失败\r\n";
            }
            file_put_contents($logFile, $msg, FILE_APPEND);
        }
        $date = '【' . date('Y-m-d H:i:s', time()) . "】导入结束：\r\n";
        file_put_contents($logFile, $date, FILE_APPEND);
    }

}