<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;

class FavoriteController extends MemberbaseController{
	
    // 前台个人中心我的收藏列表
	public function index(){
		$uid=sp_get_current_userid();
		$user_favorites_model=M("UserFavorites");
		$where=array("uid"=>$uid,'table'=>'posts');

		$cid = I('get.tid',0,'intval');

		$term = M("Terms")->find($cid);

		$join_terms_relationships="";
		$header_title = "我收藏的文章";
		if($term){
			$header_title = $term['name'];
			$join_terms_relationships = "__TERM_RELATIONSHIPS__ tr on(tr.object_id=posts.id and tr.term_id={$cid})";
		}
		$this->assign('header_title',$header_title);

		$join_str = "__POSTS__ posts on(posts.id=f.object_id)";
		
		$count=$user_favorites_model->alias("f")->join($join_str)->where($where)->count();
		
		$page=$this->page($count,10);
		
		$favorites=$user_favorites_model->where($where)
			->field("posts.id,posts.post_title,posts.smeta,(UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(posts.post_date)) as period")
			->order("createtime desc")
			->alias("f")
			->join($join_str)
			->join($join_terms_relationships)
			->limit($page->firstRow,$page->listRows)
			->select();
		
		$this->assign("page",$page->show("default"));


		$favorite_terms = $user_favorites_model->alias("f")->join("__TERMS__ t on t.term_id=f.object_id")->where(array('f.table'=>'terms','f.uid'=>$uid))->select();

		$favorite_terms_ids = array();
		foreach ($favorite_terms as $row) {
			$favorite_terms_ids[]=$row['term_id'];
		}

		$this->assign("favorite_terms",$favorite_terms);
		$this->assign("favorite_terms_ids",$favorite_terms_ids);

		$this->assign("favorites",$favorites);
		$this->display(":favorite");
	}

	function ajax_get_lists(){
		$uid=sp_get_current_userid();
		$user_favorites_model=M("UserFavorites");
		$where=array("uid"=>$uid,'table'=>'posts');

		$join_str = "__POSTS__ posts on(posts.id=f.object_id)";

		$count=$user_favorites_model->alias("f")->join($join_str)->where($where)->count();

		$page=$this->page($count,10);

		$favorites=$user_favorites_model->where($where)
			->field("terms.name as category,posts.id,posts.post_title,posts.smeta,(UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(posts.post_date)) as period")
			->order("createtime desc")
			->alias("f")
			->join($join_str)
			->join("__TERM_RELATIONSHIPS__ tr on tr.object_id=posts.id")
			->join("__TERMS__ terms on tr.term_id=terms.term_id")
			->group("posts.id")
			->limit($page->firstRow,$page->listRows)
			->select();

		foreach ($favorites as &$f) {
			$smeta = json_decode($f['smeta'],true);
			$f['post_thumb'] = sp_get_asset_upload_path($smeta['thumb']);
			$f['post_date2'] = convert_post_date2period($f['period'])."前";
		}

		$data = array();
		$data['count'] = $count;
		$data['total_pages'] = $page->getTotalPages();
		$data['posts'] = $favorites;
		$this->ajaxReturn(array('status'=>1,'data'=>array('articles'=>$data)));
	}
	// 用户收藏
	/**/
	public function do_favorite(){
		$key=sp_authcode(I('post.key'));
		if($key){
			$authkey=C("AUTHCODE");
			$key=explode(" ", $key);
			$authcode=$key[0];
			$table=$key[1];
			$object_id=$key[2];
			$post=I("post.");
			unset($post['key']);
			$post['table']=$table;
			$post['object_id']=(int)$object_id;

			if($authcode==C("AUTHCODE") && $post['object_id']>0){

				$uid=sp_get_current_userid();
				$post['uid']=$uid;
				$user_favorites_model=M("UserFavorites");
				$find_favorite=$user_favorites_model->where(array('table'=>$table,'object_id'=>$object_id,'uid'=>$uid))->find();
				if($find_favorite){

					$this->error("亲，您已收藏过啦！");
				}else {
					$post['createtime']=time();
					$result=$user_favorites_model->add($post);
					if($result){
						$this->success("收藏成功！");
					}else {
						$this->error("收藏失败！");
					}
				}
			}else{
				$this->error("非法操作，无合法密钥！");
			}
		}else{
			$this->error("非法操作，无密钥！");
		}
		
	}
	 /**/
	public function do_favorite_terms(){
		$table="terms";
		$object_id=I('post.object_id');

		$post['table']=$table;
		$post['object_id']=(int)$object_id;
		$term = M("Terms")->find($object_id);
		if( $post['object_id']>0 && $term){

			$uid=sp_get_current_userid();
			$post['uid']=$uid;
			$user_favorites_model=M("UserFavorites");
			$find_favorite=$user_favorites_model->where(array('table'=>$table,'object_id'=>$object_id,'uid'=>$uid))->find();
			if($find_favorite){
				$this->error("亲，您已收藏过啦！");
			}else {
				$post['createtime']=time();
				$result=$user_favorites_model->add($post);
				if($result){
					$fav = $user_favorites_model->find($result);
					$this->success("收藏成功！",'',array('data'=>array('term'=>$term,'favorite'=>$fav)));
				}else {
					$this->error("收藏失败！");
				}
			}
		}else{
			$this->error("非法操作！");
		}

	}
	
	// 用户取消收藏
	public function delete_favorite(){
		$id=I("get.id",0,"intval");
		$uid=sp_get_current_userid();
		$post['uid']=$uid;
		$user_favorites_model=M("UserFavorites");
		$result=$user_favorites_model->where(array('id'=>$id,'uid'=>$uid))->delete();
		if($result){
			$this->success("取消收藏成功！");
		}else {
			$this->error("取消收藏失败！");
		}
	}
}