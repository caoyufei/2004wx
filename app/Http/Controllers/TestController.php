<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;


class TestController extends Controller
{
//推送事件
public function wxEvent()
{
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];

    $token = 'wx';
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );
    $echostr=request()->get('echostr','');
    if(!empty($echostr)){
        echo $echostr;die;
    }
    if( $tmpStr == $signature ){
       //验证通过
        //1.接受数据
        $xml_data=file_get_contents("php://input");

        //记录日志
        file_put_contents('wx_event.log',$xml_data);

        //2.把xml文本转化为数组或对象
        $data=simplexml_load_string($xml_data);

        //判断接受消息的类型
        //关注
        if($data->MsgType=="event"){
            if($data->Event=="subscribe"){
            $fromUserName=$data->ToUserName;
            $toUserName=$data->FromUserName;
            $time=time();
            $msgType="text";
            $content="欢迎关注";
            $temlate="<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                        </xml>";

            echo sprintf($temlate,$toUserName,$fromUserName,$time,$msgType,$content);
            }else{
                if($data->Event=="unsubscribe"){
                    echo "";
                }
            }
        }
        //回复文本消息
    }
    if( $tmpStr == $signature ){
        $xml_data=file_get_contents('php://input');
        $data=simplexml_load_string($xml_data);
        if($data->MsgType == "text"){
            $Content="wdnmd";
            $resurn=$this->nodeInfo($data,$Content);
            return $resurn;
        }
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
    public function nodeInfo($data,$Content)
    {
            $fromUserName=$data->ToUserName;
            $toUserName=$data->FromUserName;
            file_get_contents('log.logs',$toUserName);
            $time=time();
            $msgType="text";
            $temlate="<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                        </xml>";

            echo sprintf($temlate,$toUserName,$fromUserName,$time,$msgType,$Content);
    }
}
