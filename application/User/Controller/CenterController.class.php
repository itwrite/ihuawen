<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;

class CenterController extends MemberbaseController {
	
	function _initialize(){
		parent::_initialize();
	}
	
    // 会员中心首页
	public function index() {
		$user_id = sp_get_current_userid();

		$address = M('UserAddress')->where(array('user_id'=>$user_id))->find();
		$this->assign('address',$address);

		$countries = M('GeoCountries')->field("abv,abv3,if(name_chinese='',name,name_chinese) as country_name")->select();
		$this->assign('countries',$countries);
//		print_r($this->user);exit;
		$this->assign($this->user);
    	$this->display(':center');
    }
}
