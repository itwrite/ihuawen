<?php
namespace Comment\Controller;

use Common\Controller\MemberbaseController;

class CommentController extends MemberbaseController{
	
	protected $comments_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->comments_model=D("Common/Comments");
	}
	
	// 个人中心我的评论列表
	public function index(){
		$uid=sp_get_current_userid();
		$where=array("uid"=>$uid);

		/**
		 * 评论内容，评论的文章，回复的对象（可能没有）
		 */
		$join_posts = "__POSTS__ posts on(posts.id = comments.post_id)";
		$count=$this->comments_model
			->alias("comments")
			->join($join_posts)
			->where($where)
			->count();
		
		$page=$this->page($count,20);
		
		$comments=$this->comments_model
			->alias("comments")
			->field("comments.*,posts.smeta,posts.post_title")
			->join($join_posts)
			->where($where)
			->order("comments.id desc")
			->limit($page->firstRow . ',' . $page->listRows)
			->select();

		foreach ($comments as &$row) {

			$parent_id = intval($row['parentid']);
			//如果这条评论是回复别人的，找出回复的对象
//			if($parent_id==0){
//				$path=explode("-", $row['path']);
//				$parent_id = intval($path[1]);
//			}
			if($parent_id>0){
				//别人的回复内容，人名
				$parent = $this->comments_model
					->alias('c')
					->field('c.content,c.full_name as user_nicename,c.createtime')
//					->join("__USERS__ u on(u.id = c.uid)")
					->where(array('c.id'=>$parent_id))
					->find();
				if($parent){
					$row['parent'] = $parent;
				}
			}
		}

//		print_r($comments);exit;
		$user_favorites_model=M("UserFavorites");
		$favorite_terms = $user_favorites_model->alias("f")->join("__TERMS__ t on t.term_id=f.object_id")->where(array('f.table'=>'terms','f.uid'=>$uid))->select();

		$favorite_terms_ids = array();
		foreach ($favorite_terms as $row) {
			$favorite_terms_ids[]=$row['term_id'];
		}

		$this->assign("favorite_terms",$favorite_terms);
		$this->assign("favorite_terms_ids",$favorite_terms_ids);

		$this->assign("page",$page->show("default"));
		$this->assign("comments",$comments);
		$this->display(":index");
	}
	
	// 前台用户提交评论
	public function post(){
		if (IS_POST){
			
			$post_table=sp_authcode(I('post.post_table'));
			
			$_POST['post_table']=$post_table;
			
			$url=parse_url(urldecode($_POST['url']));
			$query=empty($url['query'])?"":"?{$url['query']}";
			$url="{$url['scheme']}://{$url['host']}{$url['path']}$query";

			$_POST['url']=sp_get_relative_url($url);
			
			$session_user=session('user');
			if(!empty($session_user)){//用户已登陆,且是本站会员
				$uid=session('user.id');
				$_POST['uid']=$uid;
				$users_model=M('Users');
				$user=$users_model->field("user_login,user_email,user_nicename")->where("id=$uid")->find();
				$username=$user['user_login'];
				$user_nicename=$user['user_nicename'];
				$email=$user['user_email'];
				$_POST['full_name']=empty($user_nicename)?$username:$user_nicename;
				$_POST['email']=$email;
			}
			
			if(C("COMMENT_NEED_CHECK")){
				$_POST['status']=0;//评论审核功能开启
			}else{
				$_POST['status']=1;
			}
			$_POST['url'] = U('portal/article/index',array('id'=>$_POST['post_id']));
			$data=$this->comments_model->create();

			//评论计数
			$post_table=ucwords(str_replace("_", " ", $post_table));
			$post_table=str_replace(" ","",$post_table);
			$post_table_model=M($post_table);
			$article = $post_table_model->find(intval($_POST['post_id']));
			if ($data!==false && $article){

				$this->check_last_action(intval(C("COMMENT_TIME_INTERVAL")));
				$result=$this->comments_model->add();
				if ($result!==false){

					hook("after_comment",array_merge($data,$_POST));

					$pk=$post_table_model->getPk();
					
					$post_table_model->create(array("comment_count"=>array("exp","comment_count+1")));
					$post_table_model->where(array($pk=>intval($_POST['post_id'])))->save();
					
					$post_table_model->create(array("last_comment"=>time()));
					$post_table_model->where(array($pk=>intval($_POST['post_id'])))->save();

					/**
					 * 通知
					 */
					$notification_m = M('UserNotification');
					$notification_m->add(array(
						'uid'=>$article['user_id'],
						'from_uid'=>$this->user->id,
						'title'=>'有人评论了您的文章',
						'content'=>$article['post_title'],
						'object_id'=>$article['id'],
						'object_table'=>'posts',
						'type'=>1
					));

					$redirect=$_SERVER['HTTP_REFERER'];
					$arr = sp_ajax_return(array("id"=>$result),"评论成功！",1);
//					$arr['url'] = $redirect;
					$this->ajaxReturn($arr);
				} else {
					$this->error("评论失败！");
				}
			} else {
				$this->error($this->comments_model->getError());
			}
		}
		
	}


	// 评论点赞
	public function do_like(){
		$id = I('post.id',0,'intval');//comments表中id
		if(IS_POST&&$id>0){

			$comments_model=M("Comments");

			$can_like=cc_check_user_action(sp_get_current_userid(),"comments$id",1);

			if($can_like){
				$comment = $comments_model->where(array("id"=>$id))->find();
				if($comment){
					$comments_model->where(array("id"=>$id))->save(array("comment_like"=>array("exp","comment_like+1")));

					/**
					 * 通知
					 */
					$notification_m = M('UserNotification');
					$notification_m->add(array(
						'uid'=>$comment['uid'],
						'from_uid'=>$this->user['id'],
						'title'=>'"'.$this->user['user_nicename'].'"赞了您的评论',
						'content'=>$comment['content'],
						'object_id'=>$comment['id'],
						'object_table'=>'comments',
						'type'=>1
					));

					$this->success("赞好啦！");
				}else{
					$this->error("可能评论已被删除！请刷新！");
				}

			}else{
				$this->error("您已赞过啦！");
			}
		}else{
			$this->error("请求数据异常！id:".$id);
		}

	}

	public function do_dislike(){
		$id = I('post.id',0,'intval');//comments表中id
		if(IS_POST&&$id>0){

			$comments_model=M("Comments");

			$can_like=cc_check_user_action(sp_get_current_userid(),"comments$id",1);

			if($can_like){
				$comment = $comments_model->where(array("id"=>$id))->find();
				if($comment){
					$comments_model->where(array("id"=>$id))->save(array("comment_dislike"=>array("exp","comment_dislike+1")));

					/**
					 * 通知
					 */
					$notification_m = M('UserNotification');
					$notification_m->add(array(
						'uid'=>$comment['uid'],
						'from_uid'=>$this->user->id,
						'title'=>'"'.$this->user->user_nicename.'"踩了您的评论!',
						'content'=>$comment['content'],
						'object_id'=>$comment['id'],
						'object_table'=>'comments',
						'type'=>1
					));

					$this->success("踩啦！");
				}else{
					$this->error("可能评论已被删除！请刷新！");
				}

			}else{
				$this->error("您已踩过啦！");
			}
		}else{
			$this->error("请求数据异常！id:".$id);
		}

	}
}