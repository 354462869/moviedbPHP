<?php

namespace app\controller;

use support\Request;

class Index
{
    protected $api_key = null;

    public function __construct()
    {
        $this->api_key = config('app.api_key');
    }

    public function index(Request $request)
    {
        return view('index/index');
    }

    public function view(Request $request)
    {
        return view('index/index', ['name' => 'ggl']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }
    public function get_file_list(Request $request)
    {
        $path = $request->post('path');
        $data = get_file_list($path);
        return json($data);
    }
    public function search_file(Request $request)
    {
        $name = $request->post('name');
        $url = 'https://api.themoviedb.org/3/search/tv?api_key='.$this->api_key.'&language=zh-cn&include_adult=true';
        $res = curlGet($url.'&query='.$name);
        $data = json_decode($res,true);
        return json(['code' => 200, 'message' => 'ok','data'=>$data]);
    }
    public function find_tv(Request $request)
    {
        $id = $request->post('id');
        $res = curlGet('https://api.themoviedb.org/3/tv/'.$id.'?api_key='.$this->api_key.'&language=zh-cn');
        $data = json_decode($res,true);
        return json(['code' => 200, 'message' => 'ok','data'=>$data]);

    }
    public function mkdir(Request $request)
    {
        $name = $request->post('name');
        $path = $request->post('path');
        $name = str_replace('...','',$name);
        if (file_exists($path.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$name)){
            return json(['code' => 200, 'message' => '文件夹已存在']);
        }else{
            if ( mkdir($path.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$name,0755,true)){
                return json(['code' => 200, 'message' => '创建文件夹成功']);
            }
        }
    }
    public function insert_tv_id(Request $request)
    {
        $id = $request->post('id');
        $name = $request->post('name');
        $path = $request->post('path');
        $name = str_replace('...','',$name);
        if (file_exists($path.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'tvshow.nfo')){
            return json(['code' => 200, 'message' => '文件已存在']);
        }else{
            if ( insert_id($id,$path.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$name)){
                return json(['code' => 200, 'message' => '创建文件成功']);
            }
        }
    }
    public function download_image(Request $request)
    {
        $id = $request->post('id');
        $url = $request->post('url');
        $name = $request->post('name');
        $path = $request->post('path');
        $save_name = $request->post('save_name');
        $imgData = file_get_contents($url);
        $name = str_replace('...','',$name);
        if (file_exists($path.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.$save_name)){
            return json(['code' => 200, 'message' => '文件已存在','image'=>'http://127.0.0.1:8787/outfile/'.$id.'/'.$save_name]);
        }else{
            if (!file_exists('public'.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$id)){
                mkdir('public'.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$id,0755,true);
            }
            if (file_put_contents($path.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.$save_name,$imgData)
            && file_put_contents('public'.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.$save_name,$imgData)
            ){
                return json(['code' => 200, 'message' => '创建文件成功','image'=>'http://127.0.0.1:8787/outfile/'.$id.'/'.$save_name]);
            }
        }
    }
    public function move_file(Request $request)
    {
        $name = $request->post('name');
        $path = $request->post('path');
        $save_path = $request->post('save_path');
        $rel_name = $request->post('rel_name');
        $name = str_replace('...','',$name);
        if (rename($path,$save_path.DIRECTORY_SEPARATOR.'outfile'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.$rel_name)){
            return json(['code' => 200, 'message' => '移动文件成功']);
        }
    }
    public function get_nfo_list(Request $request)
    {
        $path = $request->post('path');
        $data = get_nfo_list($path);
        return json($data);
    }
    public function get_nfo(Request $request)
    {
        $name = $request->post('name');
        $path = $request->post('path');
        $data =  get_nfo($name,$path);
        if ($data['status'] == true){
            return json(['code' => 200, 'message' => '查询成功','nfo'=>$data]);
        }else{
            $Translate = new tTranslate();
            if ($data['title']){
                if (!strpos($data['title'],'[译:')){
                    $rst = $Translate->skapi_translate_text('auto','zh',$data['title']);
                    $rst_data = json_decode($rst,true);
                    $to_text = $data['title']."[译:{$rst_data['data']['to_text']}]";
                    if (replace_nfo($path.DIRECTORY_SEPARATOR.$name,$data['title'],$to_text)){
                        $data['title'] = $to_text;
                    }
                }
            }
            if ($data['plot']){
                if (!strpos($data['plot'],'[译:')){
                    $rst = $Translate->skapi_translate_text('auto','zh',$data['plot']);
                    $rst_data = json_decode($rst,true);
                    $to_text = $data['plot']."[译:{$rst_data['data']['to_text']}]";
                    if (replace_nfo($path.DIRECTORY_SEPARATOR.$name,$data['plot'],$to_text)){
                        $data['plot'] = $to_text;
                    }
                }
            }
            return json(['code' => 200, 'message' => '查询成功','nfo'=>$data]);
        }
    }
    public function test(Request $request)
    {
        $Translate = new tTranslate();
        return $Translate->skapi_translate_text('auto','zh','hello world');
    }
}
