<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;


class ScrapeCommand extends Command
{
    protected static $defaultName = 'scrape';
    protected static $defaultDescription = 'scrape';
    protected $api_key = null;

    public function curlGet($url)
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
    public function curlPost($url,$requestString,$timeout = 5)
    {
        $con = curl_init((string)$url);
        curl_setopt($con, CURLOPT_HEADER, false);
//        curl_setopt($con, CURLOPT_HTTPHEADER, $header);
        curl_setopt($con, CURLOPT_POSTFIELDS, $requestString);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);//
        curl_setopt($con, CURLOPT_SSL_VERIFYHOST, false);//不检查ssl证书
        curl_setopt($con, CURLOPT_POST,true);
        curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($con, CURLOPT_TIMEOUT,(int)$timeout);
        $rst = curl_exec($con);
        curl_close($con);
        return $rst;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('action', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->api_key = config('app.api_key');
        $name = $input->getArgument('action');
        if ($name == 'get_info'){
            $this->get_info();
        }
//        $output->writeln('Hello scrape');
        return self::SUCCESS;
    }
    protected function get_info()
    {
        echo '开始获取文件列表';
        $url = 'https://api.themoviedb.org/3/search/multi?api_key='.$this->api_key.'&language=zh-cn&include_adult=true';
        $path = 'Z:\日韩动漫';
        $file_arr = scandir($path);
        foreach ($file_arr as $k => $v){
            echo $path.DIRECTORY_SEPARATOR.$v.PHP_EOL;
            if ($v == '.' || $v == '..' || !is_dir($path.DIRECTORY_SEPARATOR.$v)){
                continue;
            }
            $file_list = scandir($path.DIRECTORY_SEPARATOR.$v);
            $file_count = 0;
            foreach ($file_list as $key => $value){
                if ($value == 'fanart.jpg' || $value == 'poster.jpg' || $value == 'tvshow.nfo'){
                    $file_count++;
                }
            }
            if ($file_count >= 3){
             echo'fanart.jpg,poster.jpg,tvshow.nfo都存在，跳过'.PHP_EOL;
             continue;
            }
            $name = preg_replace('/\[.+\]/','',$v);
            $keyword_arr = explode(' ',$name);
            $data = $this->search_keywords($keyword_arr);
            if (count($data) == 1){
                $this->get_tv_id($data[0],$path.DIRECTORY_SEPARATOR.$v);
            }else{
                print_r($data);
                fwrite(STDOUT, '当前关键词查询到多条信息，请手动选择正确关键词或者‘N’跳过:');
                $input = trim(fgets(STDIN));
                $continue = true;
                while ($continue){
                    if ($input == 'n' || $input == 'N'){
                        break;
                    }else{
                        if (is_numeric($input)){
                            $this->get_tv_id($data[$input],$path.DIRECTORY_SEPARATOR.$v);
                            break;
                        }
                    }
                    $input = fgets(STDIN);  // 从控制台读取输入
                }
            }
        }
    }
    protected function get_tv_id($data,$path = false)
    {
        $id = $data['id'];
        $res = $this->curlGet('https://api.themoviedb.org/3/tv/'.$id.'?api_key='.$this->api_key.'&language=zh-cn');
        $data = json_decode($res,true);
        if ($path == false){
            return $data;
        }else{
            echo '自动添加tmmid并下载图片到'.$path.PHP_EOL;
            $this->insert_id($data,$path);
            $this->download_image($data,$path);
        }
    }
    protected function search_keywords($keywords)
    {
        $url = 'https://api.themoviedb.org/3/search/tv?api_key='.$this->api_key.'&language=zh-cn&include_adult=true';
        if (is_array($keywords)){
            $count = count($keywords);
            $num = 0;
            $res_data = [];
            foreach ($keywords as $k => $v){
                $v = trim($v);
                $keywords[$k] = $v;
                if ($v == ''){
                    $num++;
                    continue;
                }
                $query = $v;
                echo '开始查询关键词['.$v.']'.PHP_EOL;
                $res = $this->curlGet($url.'&query='.$query);
                $data = json_decode($res,true);
                if ($data['total_results'] == 0){
                    echo '当前关键词没有结果'.PHP_EOL;
                    $num++;
                    continue;
                }elseif ($data['total_results'] == 1){
                    return [
                        ['id'=>$data['results'][0]['id'],'name'=>$data['results'][0]['name'],'original_name'=>$data['results'][0]['original_name'],'overview'=>$data['results'][0]['overview']]
                    ];
                    break;
                }else{
                    if ($data['total_results'] > 10){
                        $res_data[] = $k;
                        echo '当前结果大于10条'.PHP_EOL;
                        continue;
                    }else{
                        echo '当前结果['.$data['total_results'].']条'.PHP_EOL;
                        $return_data = [];
                        foreach ($data['results'] as $key => $value){
                            $return_data[] = ['id'=>$value['id'],'name'=>$value['name'],'original_name'=>$value['original_name'],'overview'=>$value['overview']];
                        }
                        return $return_data;
                        break;
                    }
                }
            }
            if (count($res_data) >= 2){
                $keyword = '';
                foreach ($res_data as $k => $v){
                    $keyword .= ' '.$keywords[$v];
                }
                echo '开始查询组和关键词['.$keyword.']'.PHP_EOL;
                return $this->search_keywords(trim($keyword));
            }
            if ($num == $count){
                fwrite(STDOUT, '当前所有关键词未查询到信息，请手动输入关键词或者‘N’跳过:');
                $input = trim(fgets(STDIN));
                $continue = true;
                while ($continue){
                    if ($input == 'n' || $input == 'N'){
                        return [];
                    }else{
                        return $this->search_keywords(trim($input));
                        break;
                    }
                    $input = trim(fgets(STDIN));  // 从控制台读取输入
                }
            }
        }else{
            $query = $keywords;
            echo '开始查询关键词['.$keywords.']'.PHP_EOL;
            $res = $this->curlGet($url.'&query='.urlencode($query));
            $data = json_decode($res,true);
            if ($data['total_results'] == 0){
                fwrite(STDOUT, '当前关键词未查询到信息，请手动输入关键词或者‘N’跳过:');
                $input = trim(fgets (STDIN));
                $continue = true;
                while ($continue){
                    if ($input == 'n' || $input == 'N'){
                        return [];
                    }else{
                        return $this->search_keywords(trim($input));
                        break;
                    }
                    $input = fgets(STDIN);  // 从控制台读取输入
                }
            }else{
                $return_data = [];
                foreach ($data['results'] as $k => $v){
                    $return_data[] = ['id'=>$v['id'],'name'=>$v['name'],'original_name'=>$v['original_name'],'overview'=>$v['overview']];
                }
                return $return_data;
            }
        }
    }
//    向剧集文件夹添加tmmid
    protected function insert_id($data,$path)
    {
        $xml = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<tvshow>
  <plot />
  <outline />
  <lockdata>false</lockdata>
  <dateadded>'.date('Y-m-d H:i:s').'</dateadded>
  <title></title>
  <tmdbid>'.$data['id'].'</tmdbid>
  <uniqueid type="Tmdb">'.$data['id'].'</uniqueid>
  <season>-1</season>
  <episode>-1</episode>
  <displayorder>aired</displayorder>
</tvshow>';
        if (file_put_contents($path.DIRECTORY_SEPARATOR.'tvshow.nfo',$xml)){
            echo '成功写入tvshow.nfo'.PHP_EOL;
        }
    }
    protected function get_nfo($data)
    {
        $xml = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>'.PHP_EOL;
        $xml .= '<tvshow>'.PHP_EOL;
        $xml .= '<plot><![CDATA['.$data['overview'].']]></plot>'.PHP_EOL;
        $xml .= '<outline><![CDATA['.$data['overview'].']]></outline>'.PHP_EOL;
        $xml .= '<lockdata>false</lockdata>'.PHP_EOL;
        $xml .= '<dateadded>'.date('Y-m-d H:i:s').'</dateadded>'.PHP_EOL;
        $xml .= '  <title>'.$data['name'].'</title>'.PHP_EOL;
        $xml .= '  <originaltitle>'.$data['original_name'].'</originaltitle>'.PHP_EOL;
        $xml .= '    <rating>0</rating>
  <year>2021</year>
  <tmdbid>'.$data['id'].'</tmdbid>
  <premiered>'.$data['first_air_date'].'</premiered>
  <releasedate>'.$data['last_air_date'].'</releasedate>
  <runtime>'.$data['episode_run_time'][0].'</runtime>
  <genre>'.$data['genres'][0]['name'].'</genre>
  <uniqueid type="Tmdb">'.$data['id'].'</uniqueid>
  <season>-1</season>
  <episode>-1</episode>
  <displayorder>aired</displayorder>
  <status>Continuing</status>
</tvshow>';
        return $xml;
    }
    protected function download_image($data,$path)
    {
        echo $path.PHP_EOL;
        $image_url = 'https://image.tmdb.org/t/p/original';
        if ($data['poster_path'] == ''){
            echo '没有封面图'.$path.PHP_EOL;
            return false;
        }else{
            echo '下载封面图'.$path.PHP_EOL;
        }
        $imgData = file_get_contents($image_url.$data['poster_path']);
        file_put_contents($path.DIRECTORY_SEPARATOR.'poster.jpg',$imgData);
        if ($data['backdrop_path'] == ''){
            echo '没有背景图'.$path.PHP_EOL;
            return false;
        }else{
            echo '下载背景图'.$path.PHP_EOL;
        }
        $imgData = file_get_contents($image_url.$data['backdrop_path']);
        file_put_contents($path.DIRECTORY_SEPARATOR.'fanart.jpg',$imgData);
        foreach ($data['seasons'] as $k => $v){
            echo '下载'.$v['season_number'].'季图'.$path.PHP_EOL;
            $imgData = file_get_contents($image_url.$data['backdrop_path']);
            file_put_contents($path.DIRECTORY_SEPARATOR.'season0'.$v['season_number'].'-poster.jpg',$imgData);
        }
    }
    protected function make_dir()
    {
        $time = time();
        $path = 'Z:\下载区\巧克力牛奶\21.12－22.3\V';
        echo '开始扫描'.$path.'文件,当前时间['.date('Y-m-d H:i:s',$time).']'.PHP_EOL;
        $out_path = $path .DIRECTORY_SEPARATOR.'outfile';
        $arr = $this->get_file_list($path);
        echo '扫描完成，总共['.count($arr).']个文件,耗时['.(($time1 = time())-$time).'秒]'.PHP_EOL;
        array_multisort($arr);
        echo '数组排序完成，耗时['.(($time2 = time())-$time1).'秒]'.PHP_EOL;
        $arr_old = $arr;
        $value_dir = '';
        $count = 0;
        foreach ($arr as $k => $v){
            $count++;
            $value = $arr_old[$k];
            $v['name'] = preg_replace('/\[.+\]/','',$v['name']);
            $len = strlen($v['name']);
            $arr[$k] = $v;
            if ($k != 0){
                similar_text(substr($arr[$k]['name'],0,(int)ceil($len * 0.7)),substr($arr[($k - 1)]['name'],0,(int)ceil($len * 0.7)),$num);
                echo PHP_EOL.PHP_EOL."[$count]".$value['name'].'[与前一个文件名相似度'.number_format($num,2).'%]'.PHP_EOL.PHP_EOL;
                if ($num > 80){//相似度超过90%默认是同一个剧集
//                    rename($v['path_name'],$out_path.DIRECTORY_SEPARATOR.$value_dir.DIRECTORY_SEPARATOR.$v['name']);
                    echo '----移动到['.$value_dir.']'.PHP_EOL;
                    continue;
                }
                if ($num < 30){//相似度超过90%默认不是同一个剧集
                    $end = strpos($value['name'],'.');
                    $value_dir = substr($value['name'],0,$end);
                    if (is_dir($out_path.DIRECTORY_SEPARATOR.$value_dir)){
                        echo '----['.$value_dir.']文件夹已存在'.PHP_EOL;
                    }else{
                        mkdir($out_path.DIRECTORY_SEPARATOR.$value_dir,0755,true);
                        echo '----创建文件夹['.$value_dir.']'.PHP_EOL;
                    }
//                    rename($v['path_name'],$out_path.DIRECTORY_SEPARATOR.$value_dir.DIRECTORY_SEPARATOR.$v['name']);
                    echo '----移动到['.$value_dir.']'.PHP_EOL;
                    continue;
                }
                fwrite(STDOUT, '--------是否移动到上一个文件所在文件夹 Y or N? ');
                $input = fgets(STDIN);
                $continue = true;
                while ($continue){
                    if ($input == 'y'.PHP_EOL){
//                        rename($v['path_name'],$out_path.DIRECTORY_SEPARATOR.$value_dir.DIRECTORY_SEPARATOR.$v['name']);
                        echo '----移动到['.$value_dir.']'.PHP_EOL;
                        break;
                    }else{
                        $end = strpos($value['name'],'.');
                        $value_dir = substr($value['name'],0,$end);
                        if (is_dir($out_path.DIRECTORY_SEPARATOR.$value_dir)){
                            echo '----['.$value_dir.']文件夹已存在'.PHP_EOL;
                        }else{
                            mkdir($out_path.DIRECTORY_SEPARATOR.$value_dir,0755,true);
                            echo '----创建文件夹['.$value_dir.']'.PHP_EOL;
                        }
//                        rename($v['path_name'],$out_path.DIRECTORY_SEPARATOR.$value_dir.DIRECTORY_SEPARATOR.$v['name']);
                        echo '----移动到['.$value_dir.']'.PHP_EOL;
                        break;
                    }
                    $input = fgets(STDIN);  // 从控制台读取输入
                }
            }else{
                $end = strpos($value['name'],'.');
                $value_dir = substr($value['name'],0,$end);
                echo $value['name'].PHP_EOL;
                if (is_dir($out_path.DIRECTORY_SEPARATOR.$value_dir)){
                    echo '----['.$value_dir.']文件夹已存在'.PHP_EOL;
                }else{
                    mkdir($out_path.DIRECTORY_SEPARATOR.$value_dir,0755,true);
                    echo '----创建文件夹['.$value_dir.']'.PHP_EOL;
                }
//                rename($v['path_name'],$out_path.DIRECTORY_SEPARATOR.$value_dir.DIRECTORY_SEPARATOR.$v['name']);
                echo '----移动到['.$value_dir.']'.PHP_EOL;
            }
        }
        echo PHP_EOL.PHP_EOL."文件全部移动完成，总共耗时[".(time()-$time)."秒]";
    }
    public function get_file_list($path,& $file_list = [])
    {
        $file_arr = scandir($path);
        foreach ($file_arr as $k => $v){
            if ($v == '.' || $v == '..'){
                continue;
            }
            if (is_dir($path.DIRECTORY_SEPARATOR.$v)){
                $this->get_file_list($path.DIRECTORY_SEPARATOR.$v,$file_list);
            }else{
                if (strpos($v,'.mp4') || strpos($v,'.mkv') || strpos($v,'.rmvb')){
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

}
