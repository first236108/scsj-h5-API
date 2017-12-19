<?php
namespace app\index\validate;
use think\Myvalidate;
class User extends Myvalidate
{
    protected $rule=[
        'phone'=>'require|unique',
        'user_passwd'=>'require|min:6'
    ];
    protected $message=[
        'phone.unique'=>'手机号已注册',
        'phone.require'=>'手机号不允许为空',
        'user_passwd.require'=>'密码必需填写',
        'user_passwd.min'=>'密码长度至少6位'
    ];
}