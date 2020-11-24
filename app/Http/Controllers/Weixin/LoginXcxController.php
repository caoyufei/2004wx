<?php

namespace App\Http\Controllers\Weixin;

use App\Http\Controllers\Controller;
use App\Models\Xcx_user;
use App\Models\Goods;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class LoginXcxController extends Controller
{
    public function login(Request $request)
    {
        //接受code
        $code=$request->get('code');
        $userinfo=json_decode(Request()->get('a'),true);
        //$userinfo=json_decode(file_get_contents("php://input"),true);
        // dd($userinfo);

         //使用code
        $url='https://api.weixin.qq.com/sns/jscode2session?appid='.env('WX_XCX_APPID').'&secret='.env('WX_XCX_SECRET').'&js_code='.$code.'&grant_type=authorization_code';
        $data=json_decode(file_get_contents($url),true);
        // dd($data);

        //自定义登录状态
        if(isset($data['errcode']))   //有错误
        {
            //TODO  错误处理
            $response=[
                'errno'=>50001,
                'msg'=>'登录失败'
            ];
        }else{
            $openid=$data['openid'];
            $a=Xcx_user::where(['openid'=>$openid])->first();

            if($a){
                //TODO  老用户
            }else{
                $user_info=[
                    'openid'=>$openid,
                    'nickname' =>$userinfo['nickName'],
                    'country' =>$userinfo['country'],
                    'province' =>$userinfo['province'],
                    'city' =>$userinfo['city'],
                    'gender' =>$userinfo['gender']
                ];
                Xcx_user::insertGetId($user_info);
            }



            //没错误
            //生成token
            $token=sha1($data['openid'].$data['openid'].$data['session_key'].mt_rand(0,999999));
            //保存token
            $redis_login_hash='h:xcx:login:'.$token;

            $login_info=[
                'uid'=>123,
                'user_name'=>"曹玉飞",
                'login_time'=>date("Y-m-s H:i:s"),
                'login_up'=>$request->getClientIp(),
                'token'=>$token
            ];
            //echo '<pre>';print_r($login_info);echo '</pre>';die;
            //保存登录信息
            Redis::hMset($redis_login_hash,$login_info);
            //设置过期时间
            Redis::expire($redis_login_hash,7200);


            $response=[
                'errno'=>0,
                'msg'=>'ok',
                'data'=>[
                    'token'=>$token
                ]
            ];
        }
        return $response;
    }

    //商品列表
    public function goods(Request $request)
    {
        $page_size = $request->get('ps');
        $goods = Goods::select('goods_id','goods_name','shop_price','goods_img')->paginate($page_size);
        $response = [
            'errno'=>0,
            'msg '=>'ok',
            'data'=>[
                'goods'=>$goods->items()
            ]
        ];
        return $response;
    }

    //商品详情
    public function detail(Request $request)
    {
        $goods_id=$request->get('goods_id');
        if(!empty($goods_id)){
            $goods_id=Goods::where('goods_id',$goods_id)->first()->toArray();
                    // dd($goods_id);
                    return $goods_id;
        }else{
        $token=$request->get('access_token');
        // echo $token;
        //验证token是否有效
        $token_key='h:xcx:login:'.$token;
        echo "key: >>>>>>".$token_key;   echo '<hr>';
        // echo $token;die;

        //检测key是否存在
        $status=Redis::exists($token_key);
        //var_dump($status);die;
        // $res=Redis::get($token_key);
        // var_dump($res);die;
        if($status==0){
            $response=[
                'errno'=>40003,
                'msg'=>"未授权"
            ];
            return $response;
        }
        }
    }

    //加入购物车
    public function addcart(Request $request){
        $goods_id=$request->get('goods_id');
        $cartInfo=[
            'add_time'=>time(),
            'user_id'=>5,
        ];
        $res=Cart::insert($cartInfo);
        if($res){
            $response=[
                'error'=>0,
                'msg'=>"加入购物车成功",
            ];
        }else{
            $response=[
                'error'=>500001,
                'msg'=>"加入购物车失败",
            ];
        }
        return $response;
    }
}
