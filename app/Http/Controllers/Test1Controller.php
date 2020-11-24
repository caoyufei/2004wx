<?php

namespace App\Http\Controllers;

use Facade\FlareClient\Http\Client as HttpClient;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
class Test1Controller extends Controller
{
    public function test1()
    {
        print_r($_GET);
    }

    public function test2()
    {
        $xml_data=file_get_contents("php://input");

        //将xml转化为对象或数组
        $xml=simplexml_load_string($xml_data);
        //print_r($xml);
        // echo $xml->ToUserName;
    }

    public function guzzle1()
    {
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_SECRET')."";
        // echo $url;
        $client=new Client();
        $response=$client->request('GET',$url,['verify'=>false]);
        $json_str=$response->getBody();
        echo $json_str;
    }




    public function guzzle2()
    {
        $access_token="";
        $type='image';
        $url='https https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
        echo $url;die;
        $client=new Client();
        $response=$client->request('POST',$url,[
            'verify'=>false,
            'multipart'=>[
                [
                    'name'=>'media',
                    'contents'=>fopen('1.jpg','r'),
                ]//上传的文件路径
            ]
        ]);
        //发起请求并接受请求
        $data=$response->getBody();
        echo $data;
    }
    public function test()
    {
        // echo '<pre>';print_r($_GET);echo '</pre>';
        // echo '<pre>';print_r($_POST);echo '</pre>';
        $goods_info=[
            'goods_id'=>12,
            'goods_name'=>'毛衣',
            'price'=>155
        ];
        //`return $goods_info;
        echo json_encode($goods_info);
    }

}
