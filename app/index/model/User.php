<?php
namespace app\index\model;
use think\Model;

class User extends Model
{
    protected $table='t_user';
	protected $pk='id';

    public function register($data)
    {
        $result=$this->validate(true)->save($data);
        if ($result===false){
            return ['code'=>0,'msg'=>$this->getError()];
        }
        return ['code'=>1,'msg'=>$result];
	}
}