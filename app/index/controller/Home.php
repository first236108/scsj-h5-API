<?php

namespace app\index\controller;
use think\Db;
class Home
{
    /**2.1 获取地图中心商铺**/
    public function get_home_in_map()
    {
        $lat=input('post.lat');
        $lng=input('post.lng');
        if($search=input('post.search')){
            $map['name']=['like',"%$search%"];
        }
        $map['longitude']=['between',$lng-0.05,$lng+0.05];
        $map['latitude']=['between',$lat-0.05,$lat+0.05];
        $result=Db::query("SELECT * FROM( SELECT u.baozhengjin_status,s.id,s.store_avatar,s.name,s.grade,s.store_goodsnum,s.store_banner,s.area_info,s.address,s.latitude,s.longitude,ROUND(fnGetDistance($lat,$lng,s.latitude,s.longitude),4) AS distance FROM shop_store s INNER JOIN t_user u ON s.user_id = u.id WHERE s.latitude>0 AND s.longitude>0 ORDER by distance asc) t WHERE t.distance<5000 LIMIT 50");
        if (!$result){
            return [ 'data' => '','ret'  => 0, 'msg'  => '操作失败'];
        }
        return ['ret'  => 1, 'msg'  => '操作成功','data' => ['distance'=>5,'list'=>$result]];
    }

    /**2.2 获取可用城市列表**/
    public function find_useable_city()
    {
        $list=Db::table('sys_city')->order('initial asc')->field('code,name,initial')->select();
        if (!$list){
            $data['ret']=0;
            $data['msg']='获取数据失败';
            $data['data']='';
            return $data;
        }
        return ['ret'=>1,'msg'=>'操作成功','data'=>$list];
    }

    /**2.3 获取app首页**/
    public function get_home()
    {
        $default_num=2;
        $lat=input('post.lat');
        $lng=input('post.lng');
        if(!$city=input('post.city')){
            $city='郑州市';
        }
        $where="buy_type=1 and is_delete=1 and goods_verify=1 and goods_state=1";
        $advList=$this->get_adv();//轮播
        $goods1=$this->get_top($where,10,$city);//每日推荐
        $store2=$this->get_home_store(2,$city);
        $where.=" and goods_type='ytgj'";
        $goods2=$this->get_top($where,10,$city);//进口商品
        $store=$this->get_on_sale($city,$default_num);//特价商品（2个商店 及所属产品）
        foreach ($store as $index => $item) {
            $store[$index]['distance']=get_distance(34.783975,113.727863,$item['lat'],$item['lng']);
            unset($store[$index]['lat'],$store[$index]['lng']);
            $store[$index]['goods']=$this->get_store_goods($item['id']);
            $store[$index]['store_goodsnum']=count($store[$index]['goods']);
        }

        if (count($store)==$default_num){
            $result['ret']=1;
            $result['msg']='获取成功';
            $result['data']['store1']=$store;
            $result['data']['advList']=$advList;
            $result['data']['store2']=$store2;
            $result['data']['goods1']=$goods1;
            $result['data']['goods2']=$goods2;
        }else{
            $result['ret']=0;
            $result['msg']='获取数据失败';
        }
        return $result;
    }

    /**2.4 获取首页店铺排序**/
    public function get_home_store($orderType=2,$city='郑州市')
    {
        $lat=34.783975;
        $lng=113.727863;
        if (request()->isPost()){
            if (!input('post.city') || !input('post.orderType') || !input('post.page')){
                return ['ret'=>0,'msg'=>'数据获取失败','data'=>''];
            }
            $orderType=input('post.orderType');

            if ($orderType==5){
                $lat=input('post.lat')? input('post.lat'):34.783975;
                $lng=input('post.lng')? input('post.lng'):113.727863;
            }
        }
        switch ($orderType){
            case 2:
                $order='s.store_salegoodsnum desc';
                break;
            case 3:
                $order='s.modify_date desc';
                break;
            case 5:
                $order='distance desc';
                break;
        }
        $list=Db::table('shop_store')
            ->alias('s')
            ->join(['shop_goods'=>'g'],'g.store_id=s.id')
            ->join(['t_user'=>'u'], 's.user_id = u.id','LEFT')
            ->where("INSTR(s.city,'{$city}') and s.is_delete=1 and s.store_state=1")
            ->field(['u.baozhengjin_status','s.address','s.store_goodsnum','s.grade','s.name','s.id','s.area_info','s.store_avatar',"fnGetDistance({$lat},{$lng},s.latitude,s.longitude)" => 'distance'])
            ->order($order)->group('s.id')
            ->paginate(10);
        //$sql=Db::name('store')->getLastSql();
        $list=$list->toArray();
        return ['totalPage'=>$list['last_page'],'page'=>$list['current_page'],'list'=>$list['data']];
    }

    /**
     * 以下为复用方法模块
     */

    /**获取首页轮播广告**/
    protected function get_adv()
    {
        $advList=Db::table('shop_adv')
            ->where('type=1')
            ->field('id,adv_content as advImage,adv_src as advSrc,adv_type as advType')
            ->order('slide_sort desc')
            ->select();
        return ['advList'=>$advList];
    }

    /**获取特价商品（2个商店）**/
    protected function get_on_sale($city,$default_num)
    {
        $list=Db::table('shop_store')
            ->alias('s')
            ->join(['t_user'=>'u'], 's.user_id = u.id')
            ->where("s.city='{$city}' and s.is_delete=1 and s.top_it=1")
            ->order("store_sales desc")
            ->field("u.baozhengjin_status,s.address,s.grade,s.name,s.id,s.area_info,s.store_avatar,s.latitude as lat,s.longitude as lng")
            ->limit($default_num)->select();
        return $list;
    }

    /**根据店铺id获取所有产品**/
    protected function get_store_goods($store_id=0,$city='郑州市')
    {
        if (!$store_id){
            return false;
        }
        return Db::table('shop_goods')
            ->where("store_id={$store_id} and buy_type=1 and is_delete=1 and goods_verify=1 and goods_state=1")
            ->order('goods_salenum desc')
            ->field('id,goods_image,goods_name,goods_price,goods_promotion_price as price')
            ->select();
    }

    /**首页 每日推荐**/
    protected function get_top($where,$num = 10,$city='郑州市')
    {
        $ids=Db::table('shop_store')->where("city='{$city}'")->field('id')->buildSql();
        return Db::table('shop_goods')
            ->where("$where and (store_id in $ids)")
            ->order('goods_commend asc,goods_salenum desc')
            ->field('id,goods_image,goods_name,goods_price,goods_promotion_price as price')
            ->limit($num)
            ->select();
    }
}
