<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
