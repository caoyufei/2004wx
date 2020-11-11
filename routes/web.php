<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // phpinfo();
    return view('welcome');
});


//微信接入
Route::get('/test','TestController@wx');
//获取access_token
Route::get('/token','TestController@token');
//推送事件
Route::match(['get','post'],'/wx','TestController@wxEvent');

Route::prefix('/wx')->group(function(){
    Route::get('create_menu',"TestController@createMenu");
});

Route::get('/guzzle2',"TestController@guzzle2");

//练习
Route::prefix('test')->group(function(){
    Route::get('/guzzle1',"Test1Controller@guzzle1");
    Route::get('/guzzle2',"Test1Controller@guzzle2");
});
