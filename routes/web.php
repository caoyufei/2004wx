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


Route::get('/index','TestController@index');
Route::get('/index1','TestController@index1');

Route::get('/test','TestController@wx');
Route::get('/token','TestController@token');

