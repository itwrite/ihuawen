<?php
/*
 *      _______ _     _       _     _____ __  __ ______
 *     |__   __| |   (_)     | |   / ____|  \/  |  ____|
 *        | |  | |__  _ _ __ | | _| |    | \  / | |__
 *        | |  | '_ \| | '_ \| |/ / |    | |\/| |  __|
 *        | |  | | | | | | | |   <| |____| |  | | |
 *        |_|  |_| |_|_|_| |_|_|\_\\_____|_|  |_|_|
 */
/*
 *     _________  ___  ___  ___  ________   ___  __    ________  _____ ______   ________
 *    |\___   ___\\  \|\  \|\  \|\   ___  \|\  \|\  \ |\   ____\|\   _ \  _   \|\  _____\
 *    \|___ \  \_\ \  \\\  \ \  \ \  \\ \  \ \  \/  /|\ \  \___|\ \  \\\__\ \  \ \  \__/
 *         \ \  \ \ \   __  \ \  \ \  \\ \  \ \   ___  \ \  \    \ \  \\|__| \  \ \   __\
 *          \ \  \ \ \  \ \  \ \  \ \  \\ \  \ \  \\ \  \ \  \____\ \  \    \ \  \ \  \_|
 *           \ \__\ \ \__\ \__\ \__\ \__\\ \__\ \__\\ \__\ \_______\ \__\    \ \__\ \__\
 *            \|__|  \|__|\|__|\|__|\|__| \|__|\|__| \|__|\|_______|\|__|     \|__|\|__|
 */
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace Portal\Controller;
use Common\Controller\HomebaseController;
use Portal\Service\ApiService;

/**
 * 首页
 */
class IndexController extends HomebaseController {
	
    //首页
	private function _lists($limit=0,$keywords='',$group_by='',$order_by='',$where=array()){
		$join = '__POSTS__ as p on tr.object_id = p.id';
		$join2= '__USERS__ as u on p.user_id = u.id';

		$terms_m = M('Terms');
		$term_relationships_m= M("TermRelationships");

		$join_area_str = "";
		$area_tag_id = (int)(isset($_COOKIE['area_tag_id'])?$_COOKIE['area_tag_id']:$_REQUEST['area_tag_id']);
		$is_magazine = (int)$_SESSION['is_magazine'];
		if($is_magazine==0 && $area_tag_id>0){
			$_sql = M("PostsTagRelationships")->field('DISTINCT post_id')->where(array('tag_id'=>$area_tag_id,'post_id'=>array('neq',0)))->buildSql();
			$content['_sql'] = $_sql;
			$where['p.id'] = array('exp',"in($_sql)");
			//$join_area_str = "__POSTS_TAG_RELATIONSHIPS__ posts_tag_relationships on(posts_tag_relationships.post_id = p.id and posts_tag_relationships.tag_id={$area_tag_id})";
		}

		$ret = $terms_m->alias('t')
			->field("pa.name as author_name,pa.avatar as author_avatar, t.term_id,t.name,t.icon,t.sublabel,tr.object_id,p.id,p.author,p.post_title,p.post_excerpt,p.smeta,p.post_hits,p.post_date,(UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(p.post_date)) as period,u.user_nicename,u.avatar")
			->join($term_relationships_m->getTableName()." as tr on tr.term_id=t.term_id",'left')
			->join($join,'left')
			->join($join2,'left')
			->join(M("PostsAuthor")->getTableName(). ' pa on p.author_id = pa.id','left')
			->where(array('t.status'=>1,'p.post_status'=>1,'p.post_title'=>array('exp',"!='0'"),'p.post_content'=>array('exp',"!='0'"),'p.is_ad'=>array('exp','!=1')))
			->order('p.post_date desc');
		if(!empty($group_by)){
			$ret->group($group_by);
		}
		if(!empty($order_by)){
			$ret->order($order_by);
		}
		if(!empty($where)){
			$ret->where($where);
		}
		if(!empty($keywords)){
			$arr = explode(',',$keywords);
			$str = implode("','",$arr);
			$ret->where("t.name in ('$str')");

			$str = implode("%' or t.name like '%",$arr);
//			echo $str;exit;
			$ret->where("t.name like '%$str%'");
		}
		if(intval($limit)>0){
			$ret->limit($limit);
		}
		$upload_dir = C("UPLOADPATH");
		$result = $ret->select();
		foreach($result as &$row){
			$icon_file = SITE_PATH.$upload_dir.$row['icon'];
			$row['icon_svg_code'] = "";
			if(file_exists($icon_file)){
				$icon_content = @file_get_contents($icon_file);
				$icon_content = preg_replace(array('/\s+width="[^"]*"\s*/i','/\s+height="[^"]*"\s*/i','/\s+fill="[^"]*"\s*/i','/\s+id="[^"]*"\s*/i'),' ',$icon_content);
//				echo $icon_content;exit;
				$row['icon_svg_code'] = $icon_content;
			}
//			echo sp_get_asset_upload_path($icon_path);exit;
			$row['post_date2'] = convert_post_date2period($row['period'])."前";
		}
//		header('Content-Type:application/json; charset=utf-8');
//		echo $ret->getLastSql();exit;
		return $result;
	}

	public function index() {

		define('__TMPL__',$this->getTplRootUrl());

		$termsGroup1 = $termsGroup2 = $termsGroup3 =array();

		$articles_list9 = $this->_lists(9,"","p.id","p.post_date desc,tr.term_id asc",array('t.taxonomy'=>array('neq','magazine'),'p.is_ad'=>array('neq',1),'p.istop'=>array('neq',1),'p.recommended'=>array('neq',1),'t.name'=>array('exp',"not in('轻阅读','华人动态','华闻专栏')")));

		foreach($articles_list9 as $i=>$row){
			$subtitle_arr = explode('|',$row['sublabel']);
			$row['subtitle'] = $subtitle_arr[0]."&nbsp;&nbsp;".$subtitle_arr[1];

			if($i>=0 &&$i<3){
				$termsGroup1[] = $row;
			}else if($i>=3 && $i<6){
				$termsGroup2[] = $row;
			}elseif($i>=6 && $i<9){
				$termsGroup3[] = $row;
			}
		}
		$this->assign('termsGroup1',$termsGroup1);
		$this->assign('termsGroup2',$termsGroup2);
		$this->assign('termsGroup3',$termsGroup3);


		//热门
		$now_date = date('Y-m-d 23:59:59');
		$forward_date = date('Y-m-d 00:00:00',strtotime('-28 days'));
		$hottest_articles = $this->_lists(8,"","p.id","p.post_hits desc",
			array(
				'p.recommended'=>array('neq',1),
				'p.is_ad'=>0,
				't.taxonomy'=>'article',
				'p.post_date'=>array('between',array($forward_date,$now_date))
			)
		);

		$this->assign('hottest_articles',$hottest_articles);
//		print_r(count($hottest_articles));exit;

		/**
		 * ===============================================================================================
		 * ===============================================================================================
		 * ===============================================================================================
		 * ===============================================================================================
		 */

		$common_where = array('t.taxonomy'=>array('neq','magazine'),'p.is_ad'=>array('neq',1),'p.istop'=>array('neq',1),'p.recommended'=>array('neq',1));
		/**
		 * 商业理才
		 */
		$manageBusinessArticles = $this->_lists(4,"商业理财",'p.id','p.post_date desc',$common_where);
		$this->assign('manageBusinessArticles',$manageBusinessArticles);

		/**
		 * 华闻专栏
		 */
		$huawenColumnsArticles = $this->_lists(4,"华闻专栏",'p.id','p.post_date desc',$common_where);
		$this->assign('huawenColumnsArticles',$huawenColumnsArticles);

		$huarendongtaiArticles = $this->_lists(4,"华人动态",'p.id','p.post_date desc',$common_where);
		$this->assign('huarendongtaiArticles',$huarendongtaiArticles);

		/**
		 * 轻阅读
		 */
		$qingyueduArticles =  $this->_lists(12,"轻阅读",'p.id','p.post_date desc',$common_where);
		$this->assign('qingyueduArticles',$qingyueduArticles);



		$_SESSION['is_magazine'] = 1;
		$rotate_list =  $this->_lists(9,"杂志",'p.id','p.serial_number desc');
		unset($_SESSION['is_magazine']);
		$this->assign('rotate_list',$rotate_list);


		$tag_str = "field:post_title,post_excerpt,term_relationships.object_id,smeta,terms.term_id;where:posts.istop=1;order:posts.post_modified desc;limit:5;group:posts.id";
		$list = sp_sql_posts($tag_str);

		if(empty($list)){
			$_SESSION['is_magazine'] = 1;
			$list = sp_sql_posts($tag_str);
			unset($_SESSION['is_magazine']);
		}
		$last_news = array();
		foreach($list as &$row){
			$smeta=json_decode($row['smeta'],true);

			$new_slide = array();
			$new_slide['cid'] = (int)$row['term_id'];
			$new_slide['slide_id'] = (int)$row['object_id'];
			$new_slide['post_id'] = $new_slide['slide_id'];
			$new_slide['slide_name'] = $row['post_title'];
			$new_slide['slide_url'] = leuu('article/index',array('id'=>$new_slide['slide_id']));
			if(empty($smeta['thumb'])){
				$new_slide['slide_pic'] = __TMPL__.'Public/assets/images/default_tupian1.png';
			}else{
				$new_slide['slide_pic'] = sp_get_asset_upload_path($smeta['thumb']);
			}
			$new_slide['slide_des'] = $row['post_excerpt'];
			$new_slide['slide_content'] = $row['post_excerpt'];

			$last_news[] = $new_slide;
		}

		$this->assign('home_slides_keys',array_keys($last_news));
		$this->assign('home_slides',$last_news);

		$this->assign('NAV_INDEX','home_index');
//		print_r(C());exit;
    	$this->display(":index");
    }
	public function authors(){

		$post_m = M("Posts");
		$postAuthor_m = M('PostsAuthor');
		$list = $postAuthor_m->select();

		$result = array();
		foreach ($list as $row) {
			$res = $post_m->field('p.id as post_id,p.post_title,p.post_date,tr.object_id,tr.term_id')
				->alias("p")
				->join("__TERM_RELATIONSHIPS__ tr on(p.id=tr.object_id)")
				->where(array('p.post_status'=>1,'p.author_id'=>$row['id']))
				->order('p.post_date desc')
				->find();
			if($res){
				foreach ($res as $key=>$val) {
					$row[$key] = $val;
				}
				$k = strtotime($row['post_date']);
				$result[$k] = $row;
			}
		}
		$keys = array_keys($result);
		arsort($keys);
		$authors_list = array();
		foreach($keys as $k){
			$authors_list[] = $result[$k];
		}
		$this->assign('authors_list',$authors_list);

		$this->display(':authors');
	}
//	public function authors(){
//
//		$post_m = M("Posts");
//		$postAuthor_m = M('PostsAuthor');
//
//		$authors_list = $post_m->field('pa.*,p.post_title,p.id as post_id,tr.object_id,tr.term_id')
//			->alias("p")
//			->join($postAuthor_m->getTableName()." pa on(p.author_id = pa.id)")
//			->join("__TERM_RELATIONSHIPS__ tr on(p.id=tr.object_id)")
//			->where(array('p.post_status'=>1))
//			->order('p.post_modified desc,p.post_date desc')
//			->group('pa.id')
//			->select();
////		print_r($authors_list);exit;
//		$this->assign('authors_list',$authors_list);
//
//		$this->display(':authors');
//	}

	public function authors_articles(){
		$author_id = I('get.author_id',0);

		$posts_author_m = M('PostsAuthor');

		$author = $posts_author_m->find($author_id);
		if(!$author){
			$this->error("找不到该作家的资料");
			exit;
		}

		$this->assign('author',$author);

		/**/
		$post_m = M("Posts");
		$postAuthor_m = M('PostsAuthor');

		$totalsize = $post_m
			->alias("p")
			->join($postAuthor_m->getTableName()." pa on(p.author_id = pa.id)")
			->join("__TERM_RELATIONSHIPS__ tr on(p.id=tr.object_id)")
			->where(array('pa.id'=>$author_id,'p.post_status'=>1))
			->count('distinct(p.id)');

		$page = new \Page($totalsize);

		$lists = $post_m->field('p.*,tr.object_id,tr.term_id')
			->alias("p")
			->join($postAuthor_m->getTableName()." pa on(p.author_id = pa.id)")
			->join("__TERM_RELATIONSHIPS__ tr on(p.id=tr.object_id)")
			->where(array('pa.id'=>$author_id,'p.post_status'=>1))
			->limit($page->firstRow,$page->listRows)
			->group('p.id')
			->order('p.post_modified desc,p.post_date desc')
			->select();
//		print_r($lists);exit;
//		$lists = ApiService::postsPaged('where:posts.author_id='.$author_id.";group:posts.id;");

		$result = array();
		$result['posts'] = $lists;
		$result['page']=$page->show('default');
		$result['total_pages']=$page->getTotalPages(); // 总页数
		$result['count']=intval($totalsize);
		/**/
//		$lists = ApiService::postsPaged('where:posts.author_id='.$author_id.";group:posts.id;");

		$this->assign('lists',$result);
		$this->display(":authors_articles");
	}
	public function special_column(){
		$this->display(":special_column");
	}


	private function getTplRootUrl(){
		$tmpl_path=C("SP_TMPL_PATH");
		if($this->theme) { // 指定模板主题
			$theme = $this->theme;
		}else{
			// 获取当前主题名称
			$theme      =    C('SP_DEFAULT_THEME');
			if(C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
				$t = C('VAR_TEMPLATE');
				if (isset($_GET[$t])){
					$theme = $_GET[$t];
				}elseif(cookie('think_template')){
					$theme = cookie('think_template');
				}
				if(!file_exists($tmpl_path."/".$theme)){
					$theme  =   C('SP_DEFAULT_THEME');
				}
				cookie('think_template',$theme,864000);
			}
		}

		$theme_suffix="";

		if(C('MOBILE_TPL_ENABLED') && sp_is_mobile()){//开启手机模板支持

			if (C('LANG_SWITCH_ON',null,false)){
				if(file_exists($tmpl_path."/".$theme."_mobile_".LANG_SET)){//优先级最高
					$theme_suffix  =  "_mobile_".LANG_SET;
				}elseif (file_exists($tmpl_path."/".$theme."_mobile")){
					$theme_suffix  =  "_mobile";
				}elseif (file_exists($tmpl_path."/".$theme."_".LANG_SET)){
					$theme_suffix  =  "_".LANG_SET;
				}
			}else{
				if(file_exists($tmpl_path."/".$theme."_mobile")){
					$theme_suffix  =  "_mobile";
				}
			}
		}else{
			$lang_suffix="_".LANG_SET;
			if (C('LANG_SWITCH_ON',null,false) && file_exists($tmpl_path."/".$theme.$lang_suffix)){
				$theme_suffix = $lang_suffix;
			}
		}
		$theme=$theme.$theme_suffix;
		$current_tmpl_path=$tmpl_path.$theme."/";
		// 获取当前主题的模版路径
		$cdn_settings=sp_get_option('cdn_settings');
		if(!empty($cdn_settings['cdn_static_root'])){
			$cdn_static_root=rtrim($cdn_settings['cdn_static_root'],'/');
			return $cdn_static_root."/".$current_tmpl_path;
		}

		return $current_tmpl_path;
	}

	function wechat(){

		$this->display("Public@:wechat");
	}
}