<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;


class TestController extends Controller
{

    public  function wx()
    {
        $echostr=request()->get('echostr','');
        if($this->checkSignature && !empty($echostr)){
            echo $echostr;
        }
    }

    private function checkSignature()
    {
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];

    $token = 'wx';
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );

    if( $tmpStr == $signature ){
        return true;
    }else{
        return false;
    }
}



//获取access_token
public function token()
{
    $key="access_token";
    if(empty(Redis::get($key)))
    {
        $token="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_SECRET')."";
        $api=file_get_contents($token);
        $data=json_decode($api);
        $token=$data->access_token;
        Redis::setex($key,7200,$token);
    }
    $t=Redis::get($key);
    dd($t);





}

}
