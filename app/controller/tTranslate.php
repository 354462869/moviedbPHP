<?php

namespace App\controller;

use support\Request;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Tmt\V20180321\TmtClient;
use TencentCloud\Tmt\V20180321\Models\TextTranslateRequest;

class tTranslate
{
    protected $SecretId = null;
    protected $SecretKey = null;
    public function __construct()
    {
        $this->SecretId = config('app.tx_secret_id');
        $this->SecretKey = config('app.tx_secret_key');
    }
    public function index(Request $request,$text=null)
    {
        if ($text == null){
            return false;
        }
        try {
            $cred = new Credential(trim($this->SecretId), trim($this->SecretKey));
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("tmt.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new TmtClient($cred, "ap-chongqing", $clientProfile);

            $req = new TextTranslateRequest();

            $params = array(
                "SourceText" => $text,
                "Source" => "ja",
                "Target" => "zh",
                "ProjectId" => 0
            );
            $req->fromJsonString(json_encode($params));
            $resp = $client->TextTranslate($req);

            return $resp->toJsonString();
        }
        catch(TencentCloudSDKException $e) {
            return false;
        }
    }
    public function skapi_translate_text($source = null,$target = null,$text = null)
    {
        if ($text == null || $source == null || $target == null){
            return false;
        }
        $url = 'http://192.168.4.32:82/v1/translate/text';
        $params = [
            'appid'=>config('app.skapi_appid'),
            'source' =>$source,
            'target' =>$target,
            'text' =>$text,
        ];
        $params['sign'] = get_skapi_sign($params,config('app.skapi_app_key'));
        return curlPost($url,json_encode($params),['Content-Type:application/json']);
    }

}
