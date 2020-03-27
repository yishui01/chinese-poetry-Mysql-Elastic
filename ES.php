<?php

require __DIR__ . '/vendor/autoload.php';
require './lib/mysql.php';

class ES
{
    public $host = ['192.168.136.109:9200']; //ES HOST
    public $index = 'poems';
    public $number_of_shards = '2';
    public $number_of_replicas = '0';

    public $es;

    public $mysqlConf = [
        'tableName' => 'poems', //存放诗词的mysql表名
        'dbhost' => '127.0.0.1',
        'dbport' => '3306',
        'dbuser' => 'root',
        'dbpw'   => 'root',
        'dbname' => 'test',
    ];
    public $link;

    public function __construct()
    {
        ini_set('memory_limit', '1024M');
        $this->es = \Elasticsearch\ClientBuilder::create(
            ['logging' => false] //节约内存
        )->setHosts($this->host)->build();
        $this->link = (new Mysql($this->mysqlConf))->link;
    }

    public function createIndex()
    {
        $params = [
            'index' => $this->index,
            'body'  => [
                'settings' => [
                    'number_of_shards'   => $this->number_of_shards,
                    'number_of_replicas' => $this->number_of_replicas
                ],
                'mappings' => [
                    [
                        'properties' => $this->getProperties()
                    ]
                ]
            ]
        ];
        if (!$this->es->indices()->exists(['index' => $this->index])) {
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
            'index' => $this->index,
            'body'  => []
        ];

        foreach ($this->getRow($uresult) as $k => $row) {
            $data['body'][] = [
                'index' => [
                    '_index' => $this->index,
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
        $textSetIK = false; //是否对text类型使用中文分词插件（需要事先在es中启用）
        $p = [
            'id'          => ['type' => 'integer'],
            'type'        => ['type' => 'keyword'],
            'sn'          => ['type' => 'keyword'],
            'title'       => ['type' => 'text'],
            'author'      => ['type' => 'text'],
            'rhythmic'    => ['type' => 'text'],
            'chapter'     => ['type' => 'text'],
            'section'     => ['type' => 'text'],
            'comment'     => ['type' => 'text'],
            'notes'       => ['type' => 'text'],
            'paragraphs'  => ['type' => 'text'],
            'content'     => ['type' => 'text'],
            'create_time' => ['type' => 'date'],
        ];
        if ($textSetIK) {
            foreach ($p as $k => $v) {
                if ($v['type'] == 'text') {
                    $v['analyzer'] = 'ik_smart';
                }
            }
        }
        return $p;
    }
}

$obj = new ES();
$obj->createIndex();
$obj->mysqlToEs();




