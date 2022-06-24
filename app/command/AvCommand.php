<?php

namespace app\command;

use App\controller\tTranslate;
use QL\QueryList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use think\Exception;
use think\facade\Db;


class AvCommand extends Command
{
    protected static $defaultName = 'av';
    protected static $defaultDescription = 'av';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        if ($name == 'get_av_info'){
            $this->get_av_info();
        }
        if ($name == 'get_av_list'){
            $this->get_av_list();
        }
        if ($name == 'find_info'){
            $this->find_info();
        }
        if ($name == 'seed_to_javbus'){
            $this->seed_to_javbus();
        }
        if ($name == 'javbus_to_translate'){
            $this->javbus_to_translate();
        }
//        $output->writeln('Hello av');
//        $rst = Db::name('seed')->where('id','=',14814)->find();
//        print_r($rst);
//        $output->writeln($rst['id']);
        return self::SUCCESS;
    }
    protected function get_av_list()
    {
        $url = 'https://www.javbus.com/genre/sub';
        $error = [];
        for ($i = 1; $i <= 1498; $i++){
            echo '['.date('Y-m-d H:i:s')."]=========================当前正在采集第[{$i}]页=======================".PHP_EOL;
            sleep(2);
            if ($i == 1){
                $av_url = $url;
            }else{
                $av_url = $url.'/'.$i;
            }
            $base_url = 'https://www.javbus.com';
            $rules = [
                'number' => ['.photo-info>span>date:eq(0)','text'],
                'url' => ['a','href'],
                'poster_url' => ['.photo-frame>img','src'],
            ];
            $range = '#waterfall>.item';
            try {
                $data = QueryList::get($av_url)->rules($rules)->range($range)->query()->queryData();
            }catch (\Exception $e){
                $error[$i]['error'] = [
                    'error' =>$e->getMessage()
                ];
                continue;
            }
            foreach ($data as $k => $v){
                try {
                    if (!Db::name('javbus')->where('number','=',$v['number'])->find()){
                        sleep(1);
                        if (strpos($v['poster_url'],'://')){
                            $poster_url = $v['poster_url'];
                        }else{
                            $poster_url = $base_url.$v['poster_url'];
                        }
                        echo "[{$v['number']}]";
                        $path = public_path().DIRECTORY_SEPARATOR.'javbus'.DIRECTORY_SEPARATOR.$v['number'];
                        download_image($poster_url,$path,'poster.jpg');
                        echo '=下载封面=';
                        $av_info = $this->get_av_info($v['url'],$v['number']);
                        if (strpos($av_info['fanart_url'],'://')){
                            $fanart_url = $av_info['fanart_url'];
                        }else{
                            $fanart_url = $base_url.$av_info['fanart_url'];
                        }
                        download_image($fanart_url,$path,'fanart.jpg');
                        echo '=下载背景图='.PHP_EOL;
                        echo "当前数据库没有该信息，添加[{$av_info['name']}]至数据库".PHP_EOL;
                        $insert_data = [
                            'name'=>$av_info['name'],
                            'number'=>$v['number'],
                            'poster_path'=>$path.DIRECTORY_SEPARATOR.'poster.jpg',
                            'fanart_path'=>$path.DIRECTORY_SEPARATOR.'fanart.jpg',
                            'info'=>json_encode($av_info['info'],JSON_UNESCAPED_UNICODE),
                        ];
                        Db::name('javbus')->insert($insert_data);
                    } else{
                        echo "当前数据库已有该信息，不执行操作".PHP_EOL;
                    }
                }catch (\Exception $e){
                    $error[$i][] = [
                        'number'=>$v['number'],
                        'error' =>$e->getMessage()
                    ];
                }
            }
        }
        print_r($error);
    }
    protected function get_av_info($url,$number)
    {
        $rules = [
            'name' => ['.container>h3','text'],
            'fanart_url' => ['.bigImage>img','src'],
            'info'=>['.info>p','texts','-button'],
        ];
        $data = QueryList::get($url)->rules($rules)->query()->queryData();
        $info = [];
        $i = 0;
        foreach($data['info'] as $k => $v){
            $v = str_replace(' ','',$v);
            $v = explode("\n",$v);
            $v = array_filter($v);
            if (count($v) == 1){
                if (strlen($v[0]) - strpos($v[0],':') <= 1){
                    $info[$i]['name'] = $v[0];
                }else{
                    $info[$i] = $v[0];
                }
            }else{
                foreach($v as $key => $value){
                    $value = trim($value);
                    if ($value == ''){
                        unset($v[$key]);
                    }else{
                        $v[$key] = $value;
                    }
                }
                $info[$i-1]['arr'] = $v;
            }
            $i++;
            $data['info'][$k] = $v;
        }
        $info['detail'] = $this->find_info($number);
        return [
            'name'=>$data['name'],
            'fanart_url'=>$data['fanart_url'],
            'info'=>$info,
        ];
    }
    public function find_info($number)
    {
        $url = 'https://www.jav321.com/search';
        $params = [
            'sn'=>$number
        ];
        $html = curlPost($url,http_build_query($params));
        $rules = [
            'info'=>['.panel-body>.row:eq(2)','text']
        ];
        $data = QueryList::rules($rules)->html($html)->query()->getData()->all();
        return $data['info'];
    }
    protected function get_number($name)
    {
        preg_match('/\w+[-]\w+|\w+/',$name,$data);
        return $data[0];
    }
    protected function seed_to_javbus()
    {
        $seed_list = Db::name('seed')->where('javbus_id','=',0)->where('domain','=','https://kp.m-team.cc')->select();
        foreach ($seed_list as $k => $v){
            if ($v['javbus_id'] == 0){
                try {
                    $name = $this->get_number($v['name']);
                    $javbus = Db::name('javbus')->where('number','=',$name)->find();
                    if ($javbus){
                        Db::name('seed')->where('id','=',$v['id'])->update(['javbus_id'=>$javbus['id']]);
                        echo "[{$name}]关联javbus_id[{$javbus['id']}]".PHP_EOL;
                    }else{
                        echo "[{$name}]没有查询到关联id".PHP_EOL;
                    }
                }catch (\Exception $e){

                }
            }
        }
    }
    protected function javbus_to_translate()
    {
        $javbus_list = Db::name('javbus')->where('id','>=',13905)->select();
        foreach ($javbus_list as $k => $v){
           if (!strpos($v['info'],'[译:')){
               try {
                   echo "[{$v['id']}]==>";
                   $data = json_decode($v['info'],true);
                   if ($data['detail'] == ''){
                       echo PHP_EOL;
                       continue;
                   }
                   $Translate = new tTranslate();
                   $rst = $Translate->skapi_translate_text('auto','zh',$data['detail']);
                   $rst_data = json_decode($rst,true);
                   if ($rst_data['code'] != 200){
                       echo $rst_data['message'].PHP_EOL;
                       continue;
                   }
                   $to_text = $data['detail']."[译:{$rst_data['data']['to_text']}]";
                   $data['detail'] = $to_text;
                   $update_rst = Db::name('javbus')->where('id','=',$v['id'])->update(['info'=>json_encode($data,JSON_UNESCAPED_UNICODE)]);
                   if ($update_rst){
                       echo "[已替换]";
                   }else{
                       print_r($update_rst);
                       echo "[替换失败]";
                   }
                   echo PHP_EOL;
               }catch (\Exception $e){
                   echo "[{$e->getMessage()}]";
               }
           }
        }
    }
}
