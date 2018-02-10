<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace MobileApi\Controller;
use Common\Controller\ApibaseController;
use Portal\Service\ApiService;

class ListController extends ApibaseController {

	// 前台文章列表
	public function index() {

		//获取USER AGENT
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);

//分析数据
		$is_pc = (strpos($agent, 'windows nt')) ? true : false;
		$is_iphone = (strpos($agent, 'iphone')) ? true : false;
		$is_ipad = (strpos($agent, 'ipad')) ? true : false;
		$is_android = (strpos($agent, 'android')) ? true : false;

		$data = array();
		$osType=I('get.osType','');
	    $term_id=I('get.cid',0,'intval');
		$term=ApiService::term($term_id);

		if(empty($term)){
			$data['info'] = '栏目不存在！';
		    $this->ajaxReturn($data);
			exit;
		}

		$child_items = ApiService::all_child_terms($term_id);
		if(count($child_items)>0){

			$ids = array();
			foreach($child_items as $v){
				$ids[] = $v['term_id'];
			}
			$term_id = implode(',',$ids);
		}

//		$data['data']['term_id'] = $term_id;
		$ads = Api_sql_posts_paged("cid:$term_id;where:posts.is_ad=1;order:post_date DESC;group:posts.id",1);

		$page_size = $ads['count']>0?19:20;
		//如果接口是从ipad端过来的，把推荐的隔开，放到推荐列表
		$osSql = ($osType=='ipad'||$is_ipad)?" and posts.recommended=0":"";
		$lists = Api_sql_posts_paged("cid:$term_id;where:posts.is_ad=0 {$osSql};order:post_date DESC;group:posts.id",$page_size);

		$posts_ids = array();
		foreach ($lists as $row) {
			$posts_ids[] = $row['id'];
		}

		/**
		 * 推荐 的文章
		 */
		$commended_lists = Api_sql_posts_paged("cid:$term_id;where:posts.recommended=1;order:post_like desc,post_date DESC;group:posts.id",1);
		$lists['commended_list'] = array();
		if($commended_lists['count']>0){
			$lists['commended_list'] = $commended_lists['posts'];
		}else{
			//如果没有强推荐的文章，则在列表外的文章中按另外的排序抽取，
			$len = $lists['count'];
			$w_sql = $len>0?"posts.id not in(".implode(',',$posts_ids).")":"";
			$commended_lists = Api_sql_posts_paged("cid:$term_id;where:$w_sql;order:post_like desc,post_hits desc,post_date DESC;group:posts.id",1);
			$lists['commended_list'] = $commended_lists['posts'];
		}
//		$lists['ad'] = $ads;
		if(count($ads['posts'])>0){
			$first = array_shift($lists['posts']);
			array_unshift($lists['posts'],$ads['posts'][0]);
			array_unshift($lists['posts'],$first);
		}

		$data['data']['articles'] = $lists;

		$this->ajaxReturn($data);
	}
	
	// 搜索结果页面
	public function search(){
		$data = array();

		$keywords = I('request.keywords/s','');

		if (empty($keywords)) {
			$data['info']="关键词不能为空！请重新输入！";

		}else{
			/**
			 *
			 */
			$result=Api_sql_posts_paged_bykeyword($keywords,"group:posts.id",20,"");
			$data['data']['articles'] = $result;

		}
		$this->ajaxReturn($data);
	}


	public function magazines(){

		$data = array();
		$_SESSION['is_magazine'] = 1;
		$result=ApiService::posts("order: serial_number desc,post_date desc;group:posts.id",array('terms.taxonomy'=>'magazine'),2000,'');

		unset($_SESSION['is_magazine']);

		unset($result['page']);
		$res_res = array();
		foreach($result['posts'] as $k=>$post){

			$serial_number = $post['serial_number'];

			$file_path = $post['post_file_path'];

			$post = old2new_post($post);
			if($post){
				$post['serial_number'] = intval($serial_number);
				$post['pdf_url'] = sp_get_asset_upload_path($file_path);
				unset($post['post_excerpt']);
				unset($post['post_content']);
				$res_res[] = $post;
			}else{
				$result['count']--;
			}
		}
		$result['posts'] = $res_res;
		$data['data']['magazines'] = $result;

		$this->ajaxReturn($data);
	}
}
