<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
namespace Portal\Controller;

use Common\Controller\AdminbaseController;

class AdminPostController extends AdminbaseController {
    
	protected $posts_model;
	protected $term_relationships_model;
	protected $terms_model;
	
	function _initialize() {
		parent::_initialize();
		$this->posts_model = D("Portal/Posts");
		$this->terms_model = D("Portal/Terms");
		$this->term_relationships_model = D("Portal/TermRelationships");
	}
	
	// 后台文章管理列表
	public function index(){
		$this->_lists(array("post_status"=>array('neq',3)));
		$this->_getTree();
		$this->display();
	}
	
	// 文章添加
	public function add(){
		$terms = $this->terms_model->order(array("listorder"=>"asc"))->select();
		$term_id = I("get.term",0,'intval');
		$this->_getTermTree();
		$term=$this->terms_model->where(array('term_id'=>$term_id))->find();

		$author_m = M('posts_author');
		$author_list = $author_m->select();
		$this->assign("author_list",$author_list);

		$tags = M('PostsTag')->where(array('status'=>1))->select();
		$this->assign("tags",$tags);


		$this->assign("term",$term);
		$this->assign("terms",$terms);
		$this->display();
	}

	// 文章添加
	public function add_magazine(){
		$terms = $this->terms_model->order(array("listorder"=>"asc"))->select();
		$term_id = I("get.term",0,'intval');
		$this->_getTermTree(array(),array('taxonomy'=>'magazine'));
		$term=$this->terms_model->where(array('term_id'=>$term_id))->find();

		$author_m = M('posts_author');
		$author_list = $author_m->select();
		$this->assign("author_list",$author_list);

		$tags = M('PostsTag')->where(array('status'=>1))->select();
		$this->assign("tags",$tags);

		$this->assign("term",$term);
		$this->assign("terms",$terms);
		$this->display();
	}
	
	// 文章添加提交
	public function add_post(){
		if (IS_POST) {
			if(empty($_POST['term'])){
				$this->error("请至少选择一个分类！");
			}
			if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
				foreach ($_POST['photos_url'] as $key=>$url){
					$photourl=sp_asset_relative_url($url);
					$_POST['smeta']['photo'][]=array("url"=>$photourl,"alt"=>$_POST['photos_alt'][$key]);
				}
			}
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			 
			$_POST['post']['post_modified']=date("Y-m-d H:i:s",time());
			$_POST['post']['user_id']=get_current_admin_id();
			$article=I("post.post");
			$article['smeta']=json_encode($_POST['smeta']);

			$article['post_content']=htmlspecialchars_decode($article['post_content']);
			$result=$this->posts_model->add($article);
			if ($result) {
				foreach ($_POST['term'] as $mterm_id){
					$this->term_relationships_model->add(array("term_id"=>intval($mterm_id),"object_id"=>$result));
				}

				$post_tag_relationships_m = M('PostsTagRelationships');
				foreach ($_POST['tag_ids'] as $tid) {
					$post_tag_relationships_m->add(array("tag_id"=>intval($tid),"post_id"=>$result));
				}

				$session_admin_id=session('ADMIN_ID');
				M('PostsImages')->where(array('post_id'=>0,'user_id'=>$session_admin_id))->save(array('post_id'=>$result));

				$this->success("添加成功！");
			} else {
				$this->error("添加失败！");
			}
		}
	}

	// 杂志添加提交
	public function add_magazine_post(){
		set_time_limit(0);
		if (IS_POST) {
			if(empty($_POST['term'])){
				//$this->ajaxReturn($_POST);
				$this->error("请至少选择一个分类！");
			}
			if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
				foreach ($_POST['photos_url'] as $key=>$url){
					$photourl=sp_asset_relative_url($url);
					$_POST['smeta']['photo'][]=array("url"=>$photourl,"alt"=>$_POST['photos_alt'][$key]);
				}
			}
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta']['serial_number'] = $_POST['serial_number'] = intval($_POST['serial_number']);

			$_POST['post']['post_modified']=date("Y-m-d H:i:s",time());
			$_POST['post']['user_id']=get_current_admin_id();
			$article=I("post.post");

			$article['post_source_date'] = date('Y-m-d H:i:s',strtotime(str_replace('/','-',$article['post_source_date'])));
			$article['post_content']=htmlspecialchars_decode($article['post_content']);
			$article['post_file_path'] = '';
			$article['serial_number'] = $_POST['smeta']['serial_number'];

			$config=array(
				'rootPath' => './'.C("UPLOADPATH"),
				'savePath' => 'PDFs/',
				'maxSize' => 512000000,//500K
				'saveName'   =>    array('uniqid',''),
				'exts'       =>    array('pdf'),
				'autoSub'    =>    false,
			);
			$upload = new \Think\Upload($config);//先在本地裁剪

			$info = $upload->uploadOne($_FILES['file']);
			if($info){

				$file_path = $info['savepath'].$info['savename'];
				$article['post_file_path']=$file_path;
				$_POST['smeta']['filename'] = $_FILES['file']['name'];
				$article['smeta']=json_encode($_POST['smeta']);

				$result=$this->posts_model->add($article);
				if ($result) {
					foreach ($_POST['term'] as $mterm_id){
						$this->term_relationships_model->add(array("term_id"=>intval($mterm_id),"object_id"=>$result));
					}

					$post_tag_relationships_m = M('PostsTagRelationships');
					foreach ($_POST['tag_ids'] as $tid) {
						$post_tag_relationships_m->add(array("tag_id"=>intval($tid),"object_id"=>$result));
					}

					$this->success("添加成功！");
				} else {
					$this->error("添加失败！");
				}
			}else{
				$this->error("添加失败！");
			}
		}
	}
	
	// 文章编辑
	public function edit(){
		$id=  I("get.id",0,'intval');
		$TermRelationships_m = M('TermRelationships');
		$term_relationship = $TermRelationships_m->where(array("object_id"=>$id,"status"=>1))->getField("term_id",true);

		$terms=$this->terms_model->select();
		$post=$this->posts_model
			->alias('p')
			->join($TermRelationships_m->getTableName()." tr on(tr.object_id=p.id)",'left')
			->join($this->terms_model->getTableName()." t on(tr.term_id=t.term_id)",'left')
			->where("id=$id")->find();


		$tags = M('PostsTag')->where(array('status'=>1))->select();
		$this->assign("tags",$tags);

		$post_tag_relationships_m = M('PostsTagRelationships');
		$relationships = $post_tag_relationships_m->where(array('post_id'=>$id))->select();
		$relationships_arr=array();
		foreach ($relationships as $ship) {
			$relationships_arr[] = $ship['tag_id'];
		}
		$this->assign("relationships_arr",$relationships_arr);


		$author_m = M('posts_author');
		$author_list = $author_m->select();
		$this->assign("author_list",$author_list);

		$this->assign("terms",$terms);
		$this->assign("term",$term_relationship);

		if($post['taxonomy'] == 'magazine'){
			$file = SITE_PATH.C("UPLOADPATH").$post['post_file_path'];
			if(is_file($file)){

				$post['file'] = $file;
				$post['file_size']  = sp_size_unit(filesize($file));
			}
//			print_r(json_decode($post['smeta'],true));exit;
			$this->_getTermTree($term_relationship,array('taxonomy'=>'magazine'));
			$this->assign("post",$post);
			$this->assign("smeta",json_decode($post['smeta'],true));
			$this->display("AdminPost/edit_magazine");
			exit;
		}else{
			$this->_getTermTree($term_relationship);
		}

		$this->assign("post",$post);
		$this->assign("smeta",json_decode($post['smeta'],true));
		$this->display();
	}

	// 文章编辑提交
	public function edit_post(){
		if (IS_POST) {
			if(empty($_POST['term'])){
				$this->error("请至少选择一个分类！");
			}
			$post_id=intval($_POST['post']['id']);
			
			$this->term_relationships_model->where(array("object_id"=>$post_id,"term_id"=>array("not in",implode(",", $_POST['term']))))->delete();
			foreach ($_POST['term'] as $mterm_id){
				$find_term_relationship=$this->term_relationships_model->where(array("object_id"=>$post_id,"term_id"=>$mterm_id))->count();
				if(empty($find_term_relationship)){
					$this->term_relationships_model->add(array("term_id"=>intval($mterm_id),"object_id"=>$post_id));
				}else{
					$this->term_relationships_model->where(array("object_id"=>$post_id,"term_id"=>$mterm_id))->save(array("status"=>1));
				}
			}
			
			if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
				foreach ($_POST['photos_url'] as $key=>$url){
					$photourl=sp_asset_relative_url($url);
					$_POST['smeta']['photo'][]=array("url"=>$photourl,"alt"=>$_POST['photos_alt'][$key]);
				}
			}
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			unset($_POST['post']['user_id']);
			$_POST['post']['post_modified']=date("Y-m-d H:i:s",time());
			$article=I("post.post");
			$article['smeta']=json_encode($_POST['smeta']);
			$article['post_content']=htmlspecialchars_decode($article['post_content']);
			$result=$this->posts_model->where(array('id'=>$post_id))->save($article);
			if ($result!==false) {
				$post_tag_relationships_m = M('PostsTagRelationships');
				$post_tag_relationships_m->where(array('object_id'=>$post_id))->delete();
				foreach ($_POST['tag_ids'] as $tid) {
					$post_tag_relationships_m->add(array("tag_id"=>intval($tid),"object_id"=>$result));
				}
				$this->success("保存成功！");
			} else {
				$this->error("保存失败！");
			}
		}
	}

	// 文章编辑提交
	public function edit_post_magazine(){
		if (IS_POST) {
			if(empty($_POST['term'])){
				$this->error("请至少选择一个分类！");
			}
			$post_id=intval($_POST['post']['id']);

			$this->term_relationships_model->where(array("object_id"=>$post_id,"term_id"=>array("not in",implode(",", $_POST['term']))))->delete();
			foreach ($_POST['term'] as $mterm_id){
				$find_term_relationship=$this->term_relationships_model->where(array("object_id"=>$post_id,"term_id"=>$mterm_id))->count();
				if(empty($find_term_relationship)){
					$this->term_relationships_model->add(array("term_id"=>intval($mterm_id),"object_id"=>$post_id));
				}else{
					$this->term_relationships_model->where(array("object_id"=>$post_id,"term_id"=>$mterm_id))->save(array("status"=>1));
				}
			}

			if(!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])){
				foreach ($_POST['photos_url'] as $key=>$url){
					$photourl=sp_asset_relative_url($url);
					$_POST['smeta']['photo'][]=array("url"=>$photourl,"alt"=>$_POST['photos_alt'][$key]);
				}
			}
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta']['serial_number'] = $_POST['serial_number'] = intval($_POST['serial_number']);

			unset($_POST['post']['user_id']);
			$_POST['post']['post_modified']=date("Y-m-d H:i:s",time());
			$article=I("post.post");

			$article['post_source_date'] = date('Y-m-d H:i:s',strtotime(str_replace('/','-',$article['post_source_date'])));
			$article['serial_number'] = $_POST['smeta']['serial_number'];
			$article['post_content']=htmlspecialchars_decode($article['post_content']);
			if(!empty($_FILES['file']['tmp_name'])){
				$config=array(
					'rootPath' => './'.C("UPLOADPATH"),
					'savePath' => '/PDFs/',
					'maxSize' => 512000000,//500K
					'saveName'   =>    array('uniqid',''),
					'exts'       =>    array('pdf'),
					'autoSub'    =>    false,
				);
				$upload = new \Think\Upload($config);//先在本地裁剪

				$info = $upload->uploadOne($_FILES['file']);
				if($info){
					$file_path =$info['savepath'].$info['savename'];
					$article['post_file_path']=$file_path;
					$_POST['smeta']['filename'] = $_FILES['file']['name'];
				}
			}


			$article['smeta']=json_encode($_POST['smeta']);

			$result=$this->posts_model->where(array('id'=>$post_id))->save($article);
			if ($result!==false) {
				$post_tag_relationships_m = M('PostsTagRelationships');
				$post_tag_relationships_m->where(array('object_id'=>$post_id))->delete();
				foreach ($_POST['tag_ids'] as $tid) {
					$post_tag_relationships_m->add(array("tag_id"=>intval($tid),"object_id"=>$result));
				}
				$this->success("保存成功！");
			} else {
				$this->error("保存失败！");
			}
		}
	}

	public function remove_magazine(){
		$post_id = I('post.id',0,'intval');

		if(IS_POST){
			$post = $this->posts_model->where(array('id'=>$post_id,'taxonomy'=>'magazine'))->find();
			if($post){
				$file = SITE_PATH.$post['post_content'];
				if(is_file($file)){
					unlink($file);
				}
				$this->success("删除成功！");
			}else{
				$this->error("删除失败！");
			}
		}else{
			$this->error("异常！");
		}
	}

	// 文章排序
	public function listorders() {
		$status = parent::_listorders($this->term_relationships_model);
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}
	
	/**
	 * 文章列表处理方法,根据不同条件显示不同的列表
	 * @param array $where 查询条件
	 */
	private function _lists($where=array()){
		$term_id=I('request.term',0,'intval');
		
		$where['post_type']=array(array('eq',1),array('exp','IS NULL'),'OR');
		
		if(!empty($term_id)){
		    $where['b.term_id']=$term_id;
			$term=$this->terms_model->where(array('term_id'=>$term_id))->find();
			$this->assign("term",$term);
		}
		
		$start_time=I('request.start_time');
		if(!empty($start_time)){
		    $where['post_date']=array(
		        array('EGT',$start_time)
		    );
		}
		
		$end_time=I('request.end_time');
		if(!empty($end_time)){
		    if(empty($where['post_date'])){
		        $where['post_date']=array();
		    }
		    array_push($where['post_date'], array('ELT',$end_time));
		}
		
		$keyword=I('request.keyword');
		if(!empty($keyword)){
		    $where['post_title']=array('like',"%$keyword%");
		}
			
		$this->posts_model
		->alias("a")
			->join("__USERS__ c ON a.user_id = c.id")
		->where($where);
		
		if(!empty($term_id)){
		    $this->posts_model->join("__TERM_RELATIONSHIPS__ b ON a.id = b.object_id");
		}
		
		$count=$this->posts_model->count();
			
		$page = $this->page($count, 20);
			
		$this->posts_model
		->alias("a")
		->join("__USERS__ c ON a.user_id = c.id")
		->where($where)
		->limit($page->firstRow , $page->listRows)
		->order("a.post_date DESC")
		->group("a.id");
		if(empty($term_id)){
		    $this->posts_model->field('a.*,c.user_login,c.user_nicename');
		}else{
		    $this->posts_model->field('a.*,c.user_login,c.user_nicename,b.listorder,b.tid');
		    $this->posts_model->join("__TERM_RELATIONSHIPS__ b ON a.id = b.object_id");
		}
		$posts=$this->posts_model->select();
		
		$this->assign("page", $page->show('Admin'));
		$this->assign("formget",array_merge($_GET,$_POST));
		$this->assign("posts",$posts);
	}
	
	// 获取文章分类树结构 select 形式
	private function _getTree($where = array('taxonomy'=>array('in',array('article','magazine')))){
		$term_id=empty($_REQUEST['term'])?0:intval($_REQUEST['term']);
		$where = array_extend(array('status'=>1),$where);
		$result = $this->terms_model->where($where)->order(array("listorder"=>"asc"))->select();
		
		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		foreach ($result as $r) {
			$r['str_manage'] = '<a href="' . U("AdminTerm/add", array("parent" => $r['term_id'])) . '">添加子类</a> | <a href="' . U("AdminTerm/edit", array("id" => $r['term_id'])) . '">修改</a> | <a class="js-ajax-delete" href="' . U("AdminTerm/delete", array("id" => $r['term_id'])) . '">删除</a> ';
			$r['visit'] = "<a href='#'>访问</a>";
			$r['taxonomys'] = $this->taxonomys[$r['taxonomy']];
			$r['id']=$r['term_id'];
			$r['parentid']=$r['parent'];
			$r['selected']=$term_id==$r['term_id']?"selected":"";
			$array[] = $r;
		}
		
		$tree->init($array);
		$str="<option value='\$id' \$selected>\$spacer\$name</option>";
		$taxonomys = $tree->get_tree(0, $str);
		$this->assign("taxonomys", $taxonomys);
	}
	
	// 获取文章分类树结构 
	private function _getTermTree($term=array(),$where = array('taxonomy'=>'article')){
		$where = array_extend(array('status'=>1),$where);
		$result = $this->terms_model->where($where)->order(array("listorder"=>"asc"))->select();
		
		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		foreach ($result as $r) {
			$r['str_manage'] = '<a href="' . U("AdminTerm/add", array("parent" => $r['term_id'])) . '">添加子类</a> | <a href="' . U("AdminTerm/edit", array("id" => $r['term_id'])) . '">修改</a> | <a class="js-ajax-delete" href="' . U("AdminTerm/delete", array("id" => $r['term_id'])) . '">删除</a> ';
			$r['visit'] = "<a href='#'>访问</a>";
			$r['taxonomys'] = $this->taxonomys[$r['taxonomy']];
			$r['id']=$r['term_id'];
			$r['parentid']=$r['parent'];
			$r['selected']=in_array($r['term_id'], $term)?"selected":"";
			$r['checked'] =in_array($r['term_id'], $term)?"checked":"";
			$array[] = $r;
		}
		
		$tree->init($array);
		$str="<option value='\$id' \$selected>\$spacer\$name</option>";
		$taxonomys = $tree->get_tree(0, $str);
		$this->assign("taxonomys", $taxonomys);
	}
	
	// 文章删除
	public function delete(){
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->posts_model->where(array('id'=>$id))->save(array('post_status'=>3)) !==false) {
				$post_tag_relationships_m = M('PostsTagRelationships');
				$post_tag_relationships_m->where(array('object_id'=>$id))->delete();
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
		
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			
			if ($this->posts_model->where(array('id'=>array('in',$ids)))->save(array('post_status'=>3))!==false) {
				$post_tag_relationships_m = M('PostsTagRelationships');
				$post_tag_relationships_m->where(array('object_id'=>array('in',$ids)))->delete();
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	}
	
	// 文章审核
	public function check(){
		if(isset($_POST['ids']) && $_GET["check"]){
		    $ids = I('post.ids/a');
			
			if ( $this->posts_model->where(array('id'=>array('in',$ids)))->save(array('post_status'=>1)) !== false ) {
				$this->success("审核成功！");
			} else {
				$this->error("审核失败！");
			}
		}
		if(isset($_POST['ids']) && $_GET["uncheck"]){
		    $ids = I('post.ids/a');
		    
			if ( $this->posts_model->where(array('id'=>array('in',$ids)))->save(array('post_status'=>0)) !== false) {
				$this->success("取消审核成功！");
			} else {
				$this->error("取消审核失败！");
			}
		}
	}
	
	// 文章置顶
	public function top(){
		if(isset($_POST['ids']) && $_GET["top"]){
			$ids = I('post.ids/a');
			
			if ( $this->posts_model->where(array('id'=>array('in',$ids)))->save(array('istop'=>1))!==false) {
				$this->success("置顶成功！");
			} else {
				$this->error("置顶失败！");
			}
		}
		if(isset($_POST['ids']) && $_GET["untop"]){
		    $ids = I('post.ids/a');
		    
			if ( $this->posts_model->where(array('id'=>array('in',$ids)))->save(array('istop'=>0))!==false) {
				$this->success("取消置顶成功！");
			} else {
				$this->error("取消置顶失败！");
			}
		}
	}
	
	// 文章推荐
	public function recommend(){
		if(isset($_POST['ids']) && $_GET["recommend"]){
			$ids = I('post.ids/a');
			
			if ( $this->posts_model->where(array('id'=>array('in',$ids)))->save(array('recommended'=>1))!==false) {
				$this->success("推荐成功！");
			} else {
				$this->error("推荐失败！");
			}
		}
		if(isset($_POST['ids']) && $_GET["unrecommend"]){
		    $ids = I('post.ids/a');
		    
			if ( $this->posts_model->where(array('id'=>array('in',$ids)))->save(array('recommended'=>0))!==false) {
				$this->success("取消推荐成功！");
			} else {
				$this->error("取消推荐失败！");
			}
		}
	}
	
	// 文章批量移动
	public function move(){
		if(IS_POST){
			if(isset($_GET['ids']) && $_GET['old_term_id'] && isset($_POST['term_id'])){
			    $old_term_id=I('get.old_term_id',0,'intval');
			    $term_id=I('post.term_id',0,'intval');
			    if($old_term_id!=$term_id){
			        $ids=explode(',', I('get.ids/s'));
			        $ids=array_map('intval', $ids);
			         
			        foreach ($ids as $id){
			            $this->term_relationships_model->where(array('object_id'=>$id,'term_id'=>$old_term_id))->delete();
			            $find_relation_count=$this->term_relationships_model->where(array('object_id'=>$id,'term_id'=>$term_id))->count();
			            if($find_relation_count==0){
			                $this->term_relationships_model->add(array('object_id'=>$id,'term_id'=>$term_id));
			            }
			        }
			        
			    }
			    
			    $this->success("移动成功！");
			}
		}else{
			$tree = new \Tree();
			$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
			$terms = $this->terms_model->order(array("path"=>"ASC"))->select();
			$new_terms=array();
			foreach ($terms as $r) {
				$r['id']=$r['term_id'];
				$r['parentid']=$r['parent'];
				$new_terms[] = $r;
			}
			$tree->init($new_terms);
			$tree_tpl="<option value='\$id'>\$spacer\$name</option>";
			$tree=$tree->get_tree(0,$tree_tpl);
			 
			$this->assign("terms_tree",$tree);
			$this->display();
		}
	}
	
	// 文章批量复制
	public function copy(){
	    if(IS_POST){
	        if(isset($_GET['ids']) && isset($_POST['term_id'])){
	            $ids=explode(',', I('get.ids/s'));
	            $ids=array_map('intval', $ids);
	            $uid=sp_get_current_admin_id();
	            $term_id=I('post.term_id',0,'intval');
	            $term_count=$terms_model=M('Terms')->where(array('term_id'=>$term_id))->count();
	            if($term_count==0){
	                $this->error('分类不存在！');
	            }
	            
	            $data=array();
	            
	            foreach ($ids as $id){
	                $find_post=$this->posts_model->field('post_keywords,post_source,post_content,post_title,post_excerpt,smeta')->where(array('id'=>$id))->find();
	                if($find_post){
	                    $find_post['user_id']=$uid;
	                    $find_post['post_date']=date('Y-m-d H:i:s');
	                    $find_post['post_modified']=date('Y-m-d H:i:s');
	                    $post_id=$this->posts_model->add($find_post);
	                    if($post_id>0){
	                        array_push($data, array('object_id'=>$post_id,'term_id'=>$term_id));
	                    }
	                }
	            }
	            
	            if ( $this->term_relationships_model->addAll($data) !== false) {
	                $this->success("复制成功！");
	            } else {
	                $this->error("复制失败！");
	            }
	        }
	    }else{
	        $tree = new \Tree();
	        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
	        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
	        $terms = $this->terms_model->order(array("path"=>"ASC"))->select();
	        $new_terms=array();
	        foreach ($terms as $r) {
	            $r['id']=$r['term_id'];
	            $r['parentid']=$r['parent'];
	            $new_terms[] = $r;
	        }
	        $tree->init($new_terms);
	        $tree_tpl="<option value='\$id'>\$spacer\$name</option>";
	        $tree=$tree->get_tree(0,$tree_tpl);
	
	        $this->assign("terms_tree",$tree);
	        $this->display();
	    }
	}
	
	// 文章回收站列表
	public function recyclebin(){
		$this->_lists(array('post_status'=>array('eq',3)));
		$this->_getTree();
		$this->display();
	}
	
	// 清除已经删除的文章
	public function clean(){
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			$ids = array_map('intval', $ids);
			$status=$this->posts_model->where(array("id"=>array('in',$ids),'post_status'=>3))->delete();
			$this->term_relationships_model->where(array('object_id'=>array('in',$ids)))->delete();
			
			if ($status!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}else{
			if(isset($_GET['id'])){
				$id = I("get.id",0,'intval');
				$status=$this->posts_model->where(array("id"=>$id,'post_status'=>3))->delete();
				$this->term_relationships_model->where(array('object_id'=>$id))->delete();
				
				if ($status!==false) {
					$this->success("删除成功！");
				} else {
					$this->error("删除失败！");
				}
			}
		}
	}
	
	// 文章还原
	public function restore(){
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->posts_model->where(array("id"=>$id,'post_status'=>3))->save(array("post_status"=>"1"))) {
				$this->success("还原成功！");
			} else {
				$this->error("还原失败！");
			}
		}
	}
	
}