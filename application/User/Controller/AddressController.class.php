<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/9/8
 * Time: 17:32
 */

namespace User\Controller;


use Common\Controller\MemberbaseController;

class AddressController extends MemberbaseController{

    protected $address_m = null;
    function _initialize(){
        parent::_initialize();
        $this->address_m = M('UserAddress');
    }

    function edit_post(){
        $user_id = sp_get_current_userid();
        $data = I('post.address');

        $address = $this->address_m->where(array('user_id'=>$user_id))->find();

        $user_data = array(
            'sex' =>$_POST['sex']==1?1:0,
            'birthday' =>date('Y-m-d',strtotime(implode('-',array($_POST['year'],$_POST['month'],$_POST['day'])))),
            'day'=>implode('-',array($_POST['year'],$_POST['month'],$_POST['day']))
        );
//        print_r($user_data);exit;
        if($address){
            M('Users')->where(array('id'=>sp_get_current_userid()))->save($user_data);
            $this->address_m->where(array('id'=>$address['id']))->save($data);
        }else{
            $data['user_id'] = $user_id;
            $this->address_m->add($data);
        }
        $this->success("保存成功！",U("user/center/index"));
    }
}