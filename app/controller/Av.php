<?php

namespace App\controller;

use support\Request;
use think\facade\Db;
use Transmission\Client;

class Av
{
    public function index(Request $request)
    {

    }
    public function av_list(Request $request)
    {
        $page = $request->post('page')?$request->post('page'):1;
        $page_size = $request->post('page_size')?$request->post('page_size'):1;
        $keyword = $request->post('keyword')?$request->post('keyword'):false;
        $list = Db::name('seed')
            ->field('seed.id,seed.name,seed.javbus_id,seed.download,javbus.info,javbus.number')
            ->where('domain','=','https://kp.m-team.cc')
//            ->where('download','>=',1)
//            ->where('seed.javbus_id','=',0)
            ->join('javbus','seed.javbus_id=javbus.id','LEFT');
        if ($keyword != false){
            $list = $list->where('seed.name','LIKE',"%{$keyword}%")->whereOr('javbus.info','LIKE',"%{$keyword}%");
        }
        $count = $list->count();
        $list = $list->page($page,$page_size)
            ->order('seed.name','DESC')
            ->select();
        foreach ($list as $k => $v){
            if ($v['number']){
                $v['poster'] = "http://192.168.4.32:8787/javbus/{$v['number']}/fanart.jpg";
            }else{
//                $number = get_number($v['name']);
                $v['poster'] = "";
            }
            $v['detail'] = '';
            if ($v['info']){
                $v['info'] = json_decode($v['info'],true);
                if ($v['info']['detail']){
                    $v['detail'] = $v['info']['detail'];
                }
            }
            if ($v['download'] != 0){
                $v['status'] = "下载过[{$v['download']}]";
            }
            $list[$k] = $v;
        }
        $data = [
            'total'=>$count,
            'list'=>$list
        ];
        return json(['code' => 200, 'message' => '查询成功','data'=>$data]);
    }
    public function download(Request $request)
    {
        echo "开始下载".date("Y-m-d H:i:s").PHP_EOL;
        $id = $request->post('id');
        $compel = $request->post('compel');
        $av = Db::name('seed')->where('id','=',$id)->find();
        echo "查询数据库是否已下载".date("Y-m-d H:i:s").PHP_EOL;
        if ($av['download'] != 0 && $compel == false){
            return json(['code' => 402, 'message' => '当前种子已经下载过,是否强制下载','data'=>[]]);
        }
        $number = get_number($av['name']);
        $repeat_av = Db::name('seed')
            ->where('download','>=',1)
            ->where('name','LIKE',"%{$number}%")
            ->find();
        echo "查询数据库是否有相似下载".date("Y-m-d H:i:s").PHP_EOL;
        if ($repeat_av && $compel == false){
            return json(['code' => 402, 'message' => '下载过相似种子'.$repeat_av['name'].',是否强制下载','data'=>[]]);
        }
        echo "开始申明trasmission".date("Y-m-d H:i:s").PHP_EOL;
        $client = new Client('192.168.4.14');
        echo "设置trasmission账号密码".date("Y-m-d H:i:s").PHP_EOL;
        $client->authenticate('admin', 'admin');

        $transmission = new \Transmission\Transmission();
        $transmission->setClient($client);
        $key = config('app.mt_key');
        $download_url = "{$av['domain']}/download.php?id={$av['hd_id']}&passkey={$key}&https=1";
        echo "开始添加任务".date("Y-m-d H:i:s").PHP_EOL;
        $rst = $transmission->add($download_url,false,'/volume4/4-下载专区/下载区');
        echo "添加任务".date("Y-m-d H:i:s").PHP_EOL;
//        $hash = $rst->getHash();
        $file_name = $rst->getName();
        echo "获取文件名".date("Y-m-d H:i:s").PHP_EOL;
        Db::name('seed')->where('id','=',$av['id'])->update(['file_name'=>$file_name,'download'=>($av['download']+1)]);
    }
}
