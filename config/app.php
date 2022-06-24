<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Request;

return [
    'debug' => true,
    'default_timezone' => 'Asia/Shanghai',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => '',
    'api_key' => env('app_api_key',''),//这里是www.themoviedb.org的api_key
    'tx_secret_id' => env('tx_secret_id',''),//这里是腾讯云的SecretId
    'tx_secret_key' => env('tx_secret_key',''),//这里是腾讯云的SecretId
    'transmission_host' => env('transmission_host','127.0.0.1'),//transmission地址
    'transmission_port' => env('transmission_port','9091'),//transmission端口
    'transmission_username' => env('transmission_username','admin'),//transmission账号
    'transmission_password' => env('transmission_password','admin'),//transmission密码
    'mt_key' => env('mt_key',''),//馒头key
    'skapi_appid' => env('skapi_appid',''),//skapi appid
    'skapi_app_key' => env('skapi_app_key',''),//skapi app_key
];
