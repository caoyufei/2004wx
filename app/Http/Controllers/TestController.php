<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;


class TestController extends Controller
{
    public function index()
    {
       //$test=DB::table("test")->get();
       //dd($test);

        $key="key";
        $set=Redis::set($key,"曹玉飞");
        $get=Redis::get($key);
        dd($get);




    }
}
