<?php
namespace app\index\controller;
use think\Db;
use app\index\model\User;
use think\Session;
class Login extends \think\Controller
{
    /**
     * 1.1 获取验证码
     * session 5分钟存储code，type=3同时存储用户id
     */
    public function get_code()
    {
        $return_data=[
            ['ret'  => -1, 'msg'  => '请勿重复获取验证码', 'data' => '发送失败'],
            ['ret'  => 0, 'msg'  => '该手机号已被注册', 'data' => '']
        ];
        $phone=input('phone');
        $type=input('type');
        if ($type==1){
            if (Db::query("select id from t_user where phone=$phone limit 1")){
                return $return_data[1];
            }
        }
        Session::init(['prefix' => 'msm', 'type' => '', 'expire' =>60*5, 'auto_start' => true]);
        if (Session::has('phone') || Session::has('ip')){
            if (Session::get('count')>5){
                return $return_data[0];
            }
            Session::set('count',Session::get('count')+1);
        }else{
            Session::set('phone',$phone);
            Session::set('ip',request()->ip());
            Session::set('count',1);
            Session::set('code',mt_rand(100000,999999));
        }
        if ($type==3){
            Session::set('userId',input('userId'));
        }
        $str='http://api.scsj.net.cn/sms?token=c1e5a22922af88c7483aa86bde1ccae9&phoneNo='.$phone.'&sn='.Session::get('code');
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_URL,$str);
        $result = curl_exec($ch);
        if($result === FALSE ){
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result);
    }

    /**1.2 用户注册**/
    public function register()
    {
        $data['phone']=input('post.phone');
        $data['salt']=get_salt(8);
        $data['user_passwd']=md5(input('post.userPasswd').$data['salt']);
        $data['user_login_ip']=input('post.userLoginIp');
        $data['client_id']=input('post.clientId');
        $code=input('post.code');
        $data['inviter_code']=input('post.inviterCode');
        /**短信验证码对比**/
//        if ($code!=Session::get('code')){
//            return ['ret'=>0,'msg'=>'验证码错误','data'=>""];
//        }
        $model=(new User())->register($data);
        if ($model->code){

        }

        /**手机号、账号唯一性验证**/

//        $result=chk('t_user','phone',$data['phone']);
//        if(true===$result){
//            return ['ret'=>0,'msg'=>'用户名已存在','data'=>""];
//        }


//        $result=Db::table('t_user')->alias('u')
//            ->join('shop_store s','u.id=s.user_id','LEFT')
//            ->where("u.id={$result}")
//            ->field(['u.amount','u.id','u.phone','u.avatar','u.user_name'=>'userName','u.user_qqopenid'=>'userQqopenid','u.token','u.user_wxopenid'=>'u.userWxopenid',"IS NOT(u.user_paypwd)"=>'hasPaypwd','u.type','u.code','s.store_state'=>'storeState'])
//            ->find();
//        return ['ret'=>1,'msg'=>'','data'=>['user'=>$result,'message'=>'1']];

    }

    /**1.3 手机号登录**/
    public function login()
    {
        $data['phone']=input('post.phone');
        $data['password']=input('post.userPasswd');
        $data['opcode']='login';
        $url='http://ucenter.scsj.net.cn/';
        $curl = curl_init();
        curl_setopt ( $curl, CURLOPT_URL, $url );
        curl_setopt ( $curl, CURLOPT_POST, 1 );
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        $result = curl_exec($curl);
        curl_close($curl);
        var_dump($result);
    }
}
