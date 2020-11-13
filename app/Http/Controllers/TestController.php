<?php

namespace App\Http\Controllers;

use App\Models\Infromaction;
use App\Models\Subscribe_user;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
class TestController extends Controller
{
//推送事件
public function wxEvent()
{
    $signature = request()->get("signature");//["signature"];
    $timestamp = request()->get("timestamp");//$_GET["timestamp"];
    $nonce = request()->get("nonce");//  $_GET["nonce"];

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
        // echo $xml_data;die;
        file_put_contents('wx_event.log',$xml_data);
        //2.把xml文本转化为数组或对象
        $data=simplexml_load_string($xml_data);
        //判断接受消息的类型
        //关注
        //dd($data);

        if($data->MsgType=="event"){
            // echo 1111;die;
            if($data->Event=="subscribe"){
                $openid=$data->FromUserName;
                $u=Subscribe_user::where('openid',$openid)->first();
                if($u){
                    $Content="欢迎您再次关注";
                    $resurn=$this->nodeInfo($data,$Content);
                    return   $resurn;
                    }else{
                        $token=$this->token();
                        $url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$openid.'&lang=zh_CN';
                        $file=file_get_contents($url);
                        $code=json_decode($file,true);//转化为json数据
                        $data=[
                            'openid'=>$code['openid'],
                            'nickname'=>$code['nickname'],
                            'sex'=>$code['sex'],
                            'city'=>$code['city'],
                            'country'=>$code['country'],
                            'province'=>$code['province'],
                            'headimgurl'=>$code['headimgurl'],
                            'subscribe_time'=>$code['subscribe_time']
                        ];
                        $res=Subscribe_user::insertGetId($data);
                        $Content="欢迎关注";
                        $resurn=$this->nodeInfo($data,$Content);
                        return $resurn;
                    }
                }
        }

        //获取图片信息
        if($data->MsgType=='image'){
            $a=$this->getImage();
        }

        //获取音频信息
        if($data->MsgType=='voice'){
            $a=$this->getVoice();
        }

        //获取视频信息
        if($data->MsgType=='video'){
            $a=$this->getVideo();
        }

        //获取文本信息
        if($data->MsgType=='text'){
            $a=$this->getText();
        }
        //回复天气
        if($data->Content=='天气'){
            $Content = $this->getNew();
            $result = $this->nodeInfo($data,$Content);
            return $result;
        }
        echo "";
    }else{
        echo  "";
    }
    //回复文本消息
    if( $tmpStr == $signature ){
        $xml_data=file_get_contents('php://input');
        $data=simplexml_load_string($xml_data);
        if($data->MsgType == "text"){
            $Content="哈喽哈";
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
    return $t;
}
//调用文本的接口
    public function nodeInfo($data,$Content)
    {
            $fromUserName=$data->ToUserName;
            $toUserName=$data->FromUserName;
            file_put_contents('log.logs',$toUserName);
            $time=time();
            $msgType="text";
            $temlate="<xml>
                            <ToUserName><![CDATA[".$toUserName."]]></ToUserName>
                            <FromUserName><![CDATA[".$fromUserName."]]></FromUserName>
                            <CreateTime>".time()."</CreateTime>
                            <MsgType><![CDATA[text]]></MsgType>
                            <Content><![CDATA[".$Content."]]></Content>
                      </xml>";
            echo $temlate;
    }


    //回复天气
    public function getNew(){
        $key='3b478800e7184e6e9f6a0f5321086107';
        $url = "https://devapi.qweather.com/v7/weather/now?location=101010100&key=$key&gzip=n";
        $red = $this->curl($url);
        $red= json_decode($red,true);
        //dd($red);
        $rea = $red['now'];
        $rea=implode(',',$rea);
        //dd($rea);
        return  $rea;
        //echo $red;
    }

    //调用接口的方法
    public function curl($url,$header="",$content=[])
    {
        $ch = curl_init(); //初始化CURL
        if(substr($url,0,5)=="https"){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true); //字符串类型打印
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        if(!empty($header)){
            curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
        }
        if($content){
            curl_setopt ($ch, CURLOPT_POST,true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS,$content);
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
    //菜单展示
    public function createMenu()
    {
        $access_token=$this->token();
        $url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        $menu = [
            "button"=> [
                    [
                    "type" =>"view",
                    "name" =>"搜索",
                    "url" => "https://www.baidu.com/"
                    ],
                        [
                        "name"=>"娱乐",
                        "sub_button"=>[
                            [
                            "type"=>"view",
                            "name"=>"视频",
                            "url"=>"https://www.baidu.com/"
                            ],
                            [
                            "type"=>"view",
                            "name"=>"音乐",
                            "url"=>"https://www.baidu.com/"
                            ]
                        ]
                        ],

                [
                "name"=>"学习",
                    "sub_button"=>[
                        [
                        "type"=>"view",
                        "name"=>"语文",
                        "url"=>"https://www.baidu.com/"
                        ],
                        [
                        "type"=>"view",
                        "name"=>"数学",
                        "url"=>"https://www.baidu.com/"
                        ]
                    ]
                ]
            ]
            ];
        $Client = new Client();
        $response = $Client ->request('POST',$url,[
            'verify'=>false,
            'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE)
            ]);
        $data = $response->getBody();
        echo $data;
    }

    //获取图片
    public function getImage()
    {
        $token=$this->token();
        $xml=file_get_contents("php://input");
        $data=simplexml_load_string($xml);
        // dd($data);
        $media_id=$data->MediaId;
        $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        // dd($url);
        $fileGet=file_get_contents($url);
        //dd($fileGet);
        $code=json_decode($fileGet,true);
        $image=uniqid();
        $path='img/'.$image.'.jpg';
        $res=file_put_contents($path,$fileGet);
        // if($res){
        //     //echo "yes";die;
        // }else{
        //    // echo "no";die;
        // }

        $data=[
            "FromUserName"=>$data->FromUserName,
            "CreateTime"=>$data->CreateTime,
            "MsgType"=>$data->MsgType,
            "PicUrl" => $data->PicUrl,
            "MediaId" => $data->MediaId
        ];
        $res1=Infromaction::insert($data);
        // dd($res1);
    }

    //获取音频
    public function getVoice()
    {
        $token=$this->token();
        $xml=file_get_contents("php://input");
        $data=simplexml_load_string($xml);
        // dd($data);
        $media_id=$data->MediaId;
        $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        // dd($url);
        $fileGet=file_get_contents($url);
        //dd($fileGet);
        $code=json_decode($fileGet,true);
        $voice=uniqid();
        $path='voice/'.$voice.'.amr';
        $res=file_put_contents($path,$fileGet);
        // if($res){
        //     //echo "yes";die;
        // }else{
        //    // echo "no";die;
        // }

        $data=[
            "FromUserName" => $data->FromUserName,
            "CreateTime" => $data->CreateTime,
            "MsgType" => $data->MsgType,
            "MediaId" => $data->MediaId,
            "Format" => $data->Format,
            "ThumbMediaId" =>$data->ThumbMediaId
        ];
        $res1=Infromaction::insert($data);
        dd($res1);
    }


    //获取视频
    public function getVideo()
    {
        $token=$this->token();
        $xml=file_get_contents("php://input");
        $data=simplexml_load_string($xml);
        // dd($data);
        $media_id=$data->MediaId;
        $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        // dd($url);
        $fileGet=file_get_contents($url);
        //dd($fileGet);
        $code=json_decode($fileGet,true);
        $video=uniqid();
        $path='video/'.$video.'.mp4';
        $res=file_put_contents($path,$fileGet);
        // if($res){
        //     //echo "yes";die;
        // }else{
        //    // echo "no";die;
        // }

        $data=[
            "FromUserName" =>  $data->FromUserName,
            "CreateTime" =>  $data->CreateTime,
            "MsgType" =>  $data->MsgType,
            "MediaId" =>  $data->MediaId,
            "ThumbMediaId" => $data->ThumbMediaId
        ];
        $res1=Infromaction::insert($data);
        // dd($res1);
    }

    //获取文本
    public function getText()
    {
        $token=$this->token();
        $xml=file_get_contents("php://input");
        $data=simplexml_load_string($xml);
        // dd($data);
        $data=[
            "FromUserName"=>$data->FromUserName,
            "CreateTime"=>$data->CreateTime,
            "MsgType"=>$data->MsgType,
            "Content"=>$data->Content
        ];
        $res1=Infromaction::insert($data);
        // dd($res1);
    }

    //签到
    public function FunctionName()
    {

    }

    //菜单获取天气
}
