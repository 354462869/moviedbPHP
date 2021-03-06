<?php
/**
 * Here is your custom functions.
 */
function curlGet($url)
{
    //初始化
    $curl  =  curl_init ( ) ;
    //设置抓取的url
    curl_setopt ( $curl , CURLOPT_URL ,  $url) ;
    //不检查ssl证书
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    //设置头文件的信息作为数据流输出
    curl_setopt ( $curl , CURLOPT_HEADER ,  0 ) ;
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt ( $curl , CURLOPT_RETURNTRANSFER ,  1 ) ;
    //执行命令
    $data  =  curl_exec ( $curl ) ;
    //关闭URL请求
    curl_close ( $curl ) ;
    //显示获得的数据
    return $data;
}
function curlPost($url,$requestString,$header = [],$timeout = 5)
{
    $con = curl_init((string)$url);
    curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_HTTPHEADER, $header);
    curl_setopt($con, CURLOPT_POSTFIELDS, $requestString);
    curl_setopt($con, CURLOPT_FOLLOWLOCATION, true);//重定向
    curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);//
    curl_setopt($con, CURLOPT_SSL_VERIFYHOST, false);//不检查ssl证书
    curl_setopt($con, CURLOPT_POST,true);
    curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($con, CURLOPT_TIMEOUT,(int)$timeout);
    $rst = curl_exec($con);
    curl_close($con);
    return $rst;
}
function scanFiles(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $files = scandir($path);
    $ret = [];
    foreach ($files as $file) {
        $newPath = $path . DIRECTORY_SEPARATOR . $file;
        if ($file === '.' || $file === '..' || $file == 'outfile') {
            continue;
        }
        if (stripos($file,'.mp4') || stripos($file,'.mkv') || stripos($file,'.rmvb')){
            $ret[] = $newPath;
        }
        if (is_dir($newPath)) {
            echo $newPath.PHP_EOL;
            $tmpFiles = scanFiles($newPath);
            array_push($ret, ...$tmpFiles);
        }
    }
    return $ret;
}
function get_file_list($path,& $file_list = [])
{
    $file_arr = scandir($path);
    foreach ($file_arr as $k => $v){
        if ($v == '.' || $v == '..' || $v == 'outfile'){
            continue;
        }
        if (is_dir($path.DIRECTORY_SEPARATOR.$v)){
            get_file_list($path.DIRECTORY_SEPARATOR.$v,$file_list);
        }else{
            if (stripos($v,'.mp4') || stripos($v,'.mkv') || stripos($v,'.rmvb')){
                $file_list[] = [
                    'name'=>$v,
                    'path'=>$path,
                    'path_name'=>$path.DIRECTORY_SEPARATOR.$v,
                ];
            }
        }
    }
    return $file_list;
}
function get_nfo_list($path,& $file_list = [])
{
    $file_arr = scandir($path);
    foreach ($file_arr as $k => $v){
        if ($v == '.' || $v == '..' || $v == 'outfile'){
            continue;
        }
        if (is_dir($path.DIRECTORY_SEPARATOR.$v)){
            get_nfo_list($path.DIRECTORY_SEPARATOR.$v,$file_list);
        }else{
            if (stripos($v,'.nfo')){
                $file_list[] = [
                    'name'=>$v,
                    'title'=>'',
                    'info'=>'',
                    'path'=>$path,
                    'path_name'=>$path.DIRECTORY_SEPARATOR.$v,
                ];
            }
        }
    }
    return $file_list;
}
function insert_id($id,$path)
{
    $xml = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<tvshow>
  <plot />
  <outline />
  <lockdata>false</lockdata>
  <dateadded>'.date('Y-m-d H:i:s').'</dateadded>
  <title></title>
  <tmdbid>'.$id.'</tmdbid>
  <uniqueid type="Tmdb">'.$id.'</uniqueid>
  <season>-1</season>
  <episode>-1</episode>
  <displayorder>aired</displayorder>
</tvshow>';
    if (file_put_contents($path.DIRECTORY_SEPARATOR.'tvshow.nfo',$xml)){
        return true;
    }else{
        return false;
    }
}
function download_image($url,$path,$name=null)
{
    if (!is_dir($path)){
        mkdir($path,0755,true);
    }
    $imgData = file_get_contents($url);
    if (file_put_contents($path.DIRECTORY_SEPARATOR.$name,$imgData)){
        return true;
    }else{
        return false;
    }
}
function get_nfo($name,$path)
{
    $xml = file_get_contents($path.DIRECTORY_SEPARATOR.$name);
    preg_match('/<plot><!\[CDATA\[(.*)\]\]><\/plot>/',$xml,$data);
    if (!$data){
        preg_match('/<plot>(.*)<\/plot>/',$xml,$data);
    }
    $plot = false;
    if (!$data){
        $rst['plot'] = '';
        $plot = true;
    }else{
        $rst['plot'] = $data[1];
        if (strpos($data[1],'[译:')){
            $plot = true;
        }
    }
    preg_match('/<title>(.*)<\/title>/',$xml,$data);
    $title = false;
    if (!$data){
        $rst['title'] = '';
        $title = true;
    }else{
        $rst['title'] = $data[1];
        if (strpos($data[1],'[译:')){
            $title = true;
        }
    }
    if ($title == true && $plot == true){
        $rst['status'] = true;
    }else{
        $rst['status'] = false;
    }
    return $rst;
}
function replace_nfo($path_name,$from_text,$to_text)
{
    $xml = file_get_contents($path_name);
    if (file_put_contents($path_name,str_replace($from_text,$to_text,$xml))){
        return true;
    }else{
        return false;
    }
}
function get_number($name)
{
    preg_match('/\w+[-]\w+|\w+/',$name,$data);
    if (isset($data[0])){
        return $data[0];
    }else{
        return '';
    }
}
function get_skapi_sign(array $arr,string $app_key):string
{
    $arr = array_filter($arr);
    unset($arr['sign']);
    ksort($arr);
    $str = http_build_query($arr);
    return md5($str.$app_key);
}
