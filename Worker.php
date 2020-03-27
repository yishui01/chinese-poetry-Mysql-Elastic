<?php

require __DIR__ . '/vendor/autoload.php';

use Webpatser\Uuid\Uuid;

class Worker
{
    public $conf;

    public function __construct($conf)
    {
        $this->conf = $conf;
    }

    //是否开启过滤
    protected $_ifFilter = true;

    protected $distDir = './sql/';

    public function run()
    {
        $dirPath = dirname(__FILE__);
        $sourceFilePath = $dirPath . '/chinese-poetry/';

        //判断古诗词仓库是否存在
        $isPathExist = file_exists($sourceFilePath);
        if ($isPathExist == false) {
            die('古诗词仓库不存在，请按说明下载');
        }

        $json = $sourceFilePath . "json/";
        $ci = $sourceFilePath . "ci/";
        $lunyu = $sourceFilePath . "lunyu/";
        $shijing = $sourceFilePath . "shijing/";
        $sishuwujing = $sourceFilePath . "sishuwujing/";  //四书五经乱码较多，放弃
        $nantang = $sourceFilePath . "wudai/nantang/";
        $huajianji = $sourceFilePath . "wudai/huajianji/";
        $youmengying = $sourceFilePath . "youmengying/";
        $arr = [
            ["source" => glob("{$json}poet.tang.*.json"), "dist" => "tang.sql", "typeField" => "shi-tang"],
            ["source" => glob("{$json}poet.song.*.json"), "dist" => "song.sql", "typeField" => "shi-song"],
            ["source" => glob("{$ci}ci.song.*.json"), "dist" => "ci.sql", "typeField" => "ci"],
            ["source" => glob("{$lunyu}lunyu.json"), "dist" => "lunyu.sql", "typeField" => "lunyu"],
            ["source" => glob("{$shijing}shijing.json"), "dist" => "shijing.sql", "typeField" => "shijing"],
            ["source" => glob("{$sishuwujing}*.json"), "dist" => "sishuwujing.sql", "typeField" => "sishuwujing"],
            ["source" => glob("{$nantang}poetrys.json"), "dist" => "nantang.sql", "typeField" => "nantang"],
            ["source" => glob("{$huajianji}*.json"), "dist" => "huajianji.sql", "typeField" => "huajianji"],
            ["source" => glob("{$youmengying}*.json"), "dist" => "youmengying.sql", "typeField" => "youmengying"],
        ];

        $conf = require './conf.php';
        $tableName = $conf['mysql']['tableName'];
        $this->_run($arr, $tableName);
    }

    public function _run($arr, $tableName)
    {
        if (!file_exists($this->distDir)) {
            mkdir($this->distDir, 0755);
        }
        foreach ($arr as $k => $v) {
            $sourceFile = $v["source"];
            $distFile = $this->distDir . $v["dist"];
            file_put_contents($distFile,
                "INSERT INTO `" . $tableName . "` (`sn`,`cate`,`title`,`author`,`rhythmic`,`chapter`,`section`,`notes`,`paragraphs`,`comment`,`content`,`create_time`) VALUES \r\n");
            $type = $v['typeField'];
            $content = '';
            $id = 0;
            $converter = new \Woodylan\Converter\Converter();

            foreach ($sourceFile as $path) {
                $fileContent = file_get_contents($path);
                $fileContentArray = json_decode($fileContent, true);

                foreach ($fileContentArray as $value) {
                    //过滤长短不一的诗
                    if ($type == 'shi-tang' || $type == 'shi-song') {
                        if ($this->_ifFilter && $value['paragraphs'] != "") {
                            $isAllow = $this->filter($value['paragraphs']);
                            if ($isAllow == false) {
                                continue;
                            }
                        }
                    }

                    //title或author超过50的过滤
                    if (strlen($value['title']) > 50 || strlen($value['author']) > 50) {
                        continue;
                    }
                    $paragraphs = (isset($value['paragraphs']) && is_array($value['paragraphs']))
                        ? implode($value['paragraphs'], '\n') : '';
                    $notes = (isset($value['notes']) && is_array($value['notes']))
                        ? implode($value['notes'], '\n') : '';
                    $comment = (isset($value['comment']) && is_array($value['comment']))
                        ? implode($value['comment'], '\n') : '';
                    $_c = is_array($value['content']) ? implode($value['content'], '\n') : $value['content'];

                    //过滤掉乱码的诗词
                    $filterColumn = [$paragraphs, $notes, $comment, $_c];
                    $invalid = false;
                    foreach ($filterColumn as $v) {
                        if ($v != "" && $this->stringInArray($v, ['□'])) {
                            $invalid = true;
                            break;
                        }
                    }
                    if ($invalid) {
                        continue;
                    }

                    //简繁转换
                    if ($this->conf['simple']) {
                        $paragraphs = $converter->turn($paragraphs);
                        $notes = $converter->turn($notes);
                        $comment = $converter->turn($comment);
                        $_c = $converter->turn($_c);
                    }

                    $id++;
                    //给上一行加入逗号
                    if ($id != 1) {
                        $content .= ",\r\n";
                    }
                    $uuid = $this->createUuid();
                    $time = date("Y-m-d H:i:s", time());
//"INSERT INTO `poems` (`sn`,`type`,`title`,`author`,`rhythmic`,`chapter`,`section`,`notes`,`paragraphs`,`comment`,`content`,`create_time`)
                    $content .= "( \"{$uuid}\", \"{$type}\", \"{$value['title']}\", \"{$value['author']}\", \"{$value['rhythmic']}\", \"{$value['chapter']}\",\"{$value['section']}\",\"{$notes}\",\"{$paragraphs}\",\"{$_c}\",\"{$comment}\",\"{$time}\")";
                }
            }

            $content .= ";";
            $handle = fopen($distFile, 'a+');
            fwrite($handle, $content);
            fclose($handle);
        }
    }

    //过滤脚本
    public function filter($paragraphs, $charLength = 50)
    {
        //判断每句是否长短一样
        foreach ($paragraphs as $key => $value) {
            $length = strlen($value);
            if ($key >= 1) {
                //判断跟上一个元素长度是否相等
                if (strlen($paragraphs[$key - 1]) != $length) {
                    return false;
                }
            }

            if ($length > $charLength * 3) {
                return false;
            }
        }

        return true;
    }

    public function createUuid($short = true)
    {
        $uuid = md5(microtime() . str_replace('-', '', Uuid::generate()->string));
        if ($short) {
            $uuid = substr($uuid, 8, 32);
        }

        return $uuid;
    }

    public function stringInArray($string, array $array)
    {
        foreach ($array as $value) {
            if (strpos($string, $value)) {
                return true;
            }
        }

        return false;
    }
}

$conf = require './conf.php';
//自动运行
$class = new worker($conf);
$class->run();