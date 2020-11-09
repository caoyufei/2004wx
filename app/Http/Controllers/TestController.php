<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;


class TestController extends Controller
{
    //微信接入
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

        if($data->Content=="天气")
        {
            $Content=$this->weath();
            $fromUserName=$data->ToUserName;
            $toUserName=$data->FromUserName;
            $time=time();
            $msgType="text";
            $temlate="<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                    </xml>";

            echo sprintf($temlate,$toUserName,$fromUserName,$time,$msgType,$content);
        }


        //回复文本消息
        if($data->MsgType == "text"){
            $fromUserName=$data->ToUserName;
            $toUserName=$data->FromUserName;
            $time=time();
            $msgType="text";
            $content="你好";
            $temlate="<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                        </xml>";

            echo sprintf($temlate,$toUserName,$fromUserName,$time,$msgType,$content);
        }
        echo "";
    }else{
        echo "";
    }
}

    public function weath()
    {
        $key='3b478800e7184e6e9f6a0f5321086107';
        $url = "https://devapi.qweather.com/v7/weather/now?location=101010100&key=$key&gzip=n";
        $res=$this->curl($url);
        $data=json_decode($res,true);
        $res1=$data['now'];
        $res2=implode(',',$res1);
        // dd($res2);
        return $res2;
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

//调用接口方法
public function curl($url,$header="",$content=[]){
    $ch = curl_init(); //初始化CURL句柄
    if(substr($url,0,5)=="https"){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,true);
    }
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,true); //字符串类型打印
     curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
    if($header){
        curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
    }
    if($content){
        curl_setopt ($ch, CURLOPT_POST,true);
        curl_setopt ($ch, CURLOPT_POSTFIELDS,http_build_query($content));
    }
    //执行
    $output = curl_exec($ch);
    if($error=curl_error($ch)){
        die($error);
    }
    //关闭
    curl_close($ch);
    return $output;
}


}
