<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class ListController extends HomebaseController {


	// 前台文章列表
	public function index() {
	    $navid=I('get.navid',0,'intval');

		$ids = sp_get_term_ids_from_nav_names(intval($navid));

		$term_id=I('get.id',0,'intval');
		$term=sp_get_term($term_id);

//		print_r($term_id);exit;
		if(empty($term)){
		    header('HTTP/1.1 404 Not Found');
		    header('Status:404 Not Found');
		    if(sp_template_file_exists(MODULE_NAME."/404")){
		        $this->display(":404");
		    }
		    return;
		}
//		print_r($term);exit;
		$child_items = sp_get_all_child_terms($term_id);
		if(count($child_items)>0){
//			$ids = array();
			foreach($child_items as $v){
				$ids[] = $v['term_id'];
			}
			$term_id = implode(',',$ids);
		}
//		echo $term_id;exit;
		$tplname=$term["list_tpl"];
    	$tplname=sp_get_apphome_tpl($tplname, "list_masonry");

		$this->assign_nav_terms();
//		echo $tplname;exit;
    	$this->assign($term);
    	$this->assign('cat_id', $term_id);
    	$this->display(":$tplname");
	}
	
	// 文章分类列表接口,返回文章分类列表,用于后台导航编辑添加
	public function nav_index(){
		$navcatname="文章分类";
        $term_obj= M("Terms");

        $where=array();
        $where['status'] = array('eq',1);
        $terms=$term_obj->field('term_id,name,parent')->where($where)->order('term_id')->select();
		$datas=$terms;
		$navrule = array(
		    "id"=>'term_id',
            "action" => "Portal/List/index",
            "param" => array(
                "id" => "term_id"
            ),
            "label" => "name",
		    "parentid"=>'parent'
        );
		return sp_get_nav4admin($navcatname,$datas,$navrule) ;
	}

	function magazine_list(){

		$this->display(':magazine_list');
	}

}
