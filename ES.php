<?php

require __DIR__ . '/vendor/autoload.php';
require './lib/mysql.php';

class ES
{
    public $esConf = [
        'host'               => ['192.168.136.109:9200'], //ES HOST
        'index'              => 'poems',
        'number_of_shards'   => '2',
        'number_of_replicas' => '0',
    ];
    public $mysqlConf = [
        'tableName' => 'poems', //存放诗词的mysql表名
        'dbhost'    => '127.0.0.1',
        'dbport'    => '3306',
        'dbuser'    => 'root',
        'dbpw'      => 'root',
        'dbname'    => 'test',
    ];

    public $es;
    public $link;

    public function __construct($conf)
    {
        $this->mysqlConf = $conf['mysql'];
        $this->esConf = $conf['es'];

        ini_set('memory_limit', '1024M');
        $this->es = \Elasticsearch\ClientBuilder::create(['logging' => false])  //关闭log节约内存
        ->setHosts($this->esConf['host'])->build();
        $this->link = (new Mysql($this->mysqlConf))->link;
    }

    public function createIndex()
    {
        $params = [
            'index' => $this->esConf['index'],
            'body'  => [
                'settings' => [
                    'number_of_shards'   => $this->esConf['number_of_shards'],
                    'number_of_replicas' => $this->esConf['number_of_replicas']
                ],
                'mappings' => [
                    'properties' => $this->getProperties()
                ]
            ]
        ];
        if (!$this->es->indices()->exists(['index' => $this->esConf['index']])) {
            $this->es->indices()->create($params);
        }
        return true;
    }

    public function mysqlToEs()
    {
        $uresult = mysqli_query($this->link,
            "SELECT * FROM " . $this->mysqlConf['tableName'],
            MYSQLI_USE_RESULT);

        $data = [
            'index' => $this->esConf['index'],
            'body'  => []
        ];

        foreach ($this->getRow($uresult) as $k => $row) {
            $data['body'][] = [
                'index' => [
                    '_index' => $this->esConf['index'],
                    '_id'    => $row['id']
                ]
            ];
            $data['body'][] = $row;
            if ($k % 10000 == 0) {
                $responses = $this->es->bulk($data);
                // unset the bulk response when you are done to save memory
                unset($responses);
                $data['body'] = [];
                echo $k . "消耗内存：" . (memory_get_usage() / 1024 / 1024) . "M ---\r\n";
            }

            //逐条添加进es
//            $this->es->index([
//                'index' => $this->index,
//                'id'    => $row['id'],
//                'body'  => $row
//            ]);
        }

        if (!empty($data['body'])) {
            $this->es->bulk($data);
        }
        $data['body'] = [];

        echo "消耗内存：" . (memory_get_usage() / 1024 / 1024) . "M \r\n";
        echo "处理数据行数：" . $k . "\r\n";
        echo "success";
    }

    public function getRow($uresult)
    {
        while ($row = $uresult->fetch_assoc()) {
            yield $row;
        }
    }

    public function getProperties()
    {
        $textSetIK = true; //是否对text类型使用中文分词插件（需要事先在es中启用）
        $p = [
            'id'          => ['type' => 'integer'],
            'cate'        => ['type' => 'keyword'],
            'sn'          => ['type' => 'keyword'],
            'author'      => ['type' => 'keyword'],
            'title'       => ['type' => 'text'],
            'rhythmic'    => ['type' => 'text'],
            'chapter'     => ['type' => 'text'],
            'section'     => ['type' => 'text'],
            'comment'     => ['type' => 'text'],
            'notes'       => ['type' => 'text'],
            'paragraphs'  => ['type' => 'text'],
            'content'     => ['type' => 'text'],
            'create_time' => ['type' => 'date', "format" => "yyyy-MM-dd HH:mm:ss"],
        ];
        if ($textSetIK) {
            foreach ($p as $k => $v) {
                if ($v['type'] == 'text') {
                    $p[$k]['analyzer'] = 'ik_smart';
                }
            }
        }
        return $p;
    }
}

$conf = require './conf.php';
$obj = new ES($conf);
$obj->createIndex();
$obj->mysqlToEs();




