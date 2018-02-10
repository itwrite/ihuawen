<?php
namespace MobileApi\Controller;

use Common\Controller\ApibaseUserController;
use Think\Log;

class UserController extends ApibaseUserController {

	public function _initialize() {
		parent::_initialize();
		$this->users_model=D("Common/Users");
		$this->comments_model=D("Common/Comments");
	}
    // 前台用户登录
	public function index(){

	}


	public function favorite(){

		$data = array();
		$user_favorites_model=M("UserFavorites");
		$where=array("f.uid"=>$this->user->id);

		$join1 = "__POSTS__ as p on p.id = f.object_id";
		$count=intval($user_favorites_model
			->alias('f')
			->join($join1)
			->where($where)
			->count());

		$page=$this->page($count,20);

		$favorites=$user_favorites_model
			->where($where)
			->field('p.*,f.id as fid,f.createtime as f_createtime,t.name')
			->alias('f')
			->join($join1)
			->join("__TERM_RELATIONSHIPS__ tr on(tr.object_id=p.id)")
			->join("__TERMS__ t on(t.term_id=tr.term_id)")
			->order("createtime desc")
			->group('p.id')
			->limit($page->firstRow,$page->listRows)
			->select();

		foreach($favorites as &$favor){
			$new_favor = array();
			$new_favor['fid'] = intval($favor['fid']);
			$new_favor['post_id'] = intval($favor['id']);
			$new_favor['post_title'] = $favor['post_title'];
			$new_favor['category'] = $favor['name'];
			$new_favor['comment_count'] = intval($favor['comment_count']);
			$new_favor['post_hits'] = intval($favor['post_hits']);
			$new_favor['post_like'] = intval($favor['post_like']);
			$new_favor['createtime'] = date('Y-m-d H:i:s',$favor['f_createtime']);
			$smeta = json_decode( $favor['smeta']);
			$new_favor['post_thumb'] = sp_get_asset_upload_path($smeta->thumb);
			$favor = $new_favor;
		}
		$data['data']['favorite'] = array(
			'total_page'=>$page->getTotalPages(),
			'count'=>$count,
			'list'=>$favorites
		);
		$this->ajaxReturn($data);
	}


	public function comments_like_users(){
		$comment_id = I('get.comment_id',0);
		$common_action_log_model=M("CommonActionLog");

		$fields = "ca.*,u.user_nicename,u.avatar,u.id as uuid";
		$total_size = (int)$common_action_log_model
			->field($fields)
			->alias('ca')
			->join("__USERS__ as u on(u.id=ca.user)")
			->where(array('ca.object'=>"comments".$comment_id))
			->count();

		$page_size = 20;
		$page_param = C("VAR_PAGE");
		$page = new \Page($total_size,$page_size);
		$page->__set("PageParam", $page_param);

		$list = $common_action_log_model
			->field($fields)
			->alias('ca')
			->join("__USERS__ as u on(u.id=ca.user)")
			->limit($page->firstRow . ',' . $page->listRows)
			->where(array('ca.object'=>"comments".$comment_id))->select();

		$users = array();
		foreach ($list as $row) {
			$arr = array();
			$arr['uid'] = (int)$row['uuid'];
			$arr['avatar'] = sp_get_user_avatar_url($row['avatar']);
			$arr['user_nicename'] = $row['user_nicename'];
			$users[] = $arr;
		}


		$result = array();
		$result['total_pages']=$page->getTotalPages(); // 总页数
		$result['count']=$total_size;
		$result['list'] = $users;
		$this->ajaxReturn(array('data'=>array('users'=>$result)));
	}
	function comments(){

		$common_action_log_model=M("CommonActionLog");
		$data = array();

		$uid=$this->user->id;
		$join1 = "__POSTS__ as p on p.id=c.post_id";
		$join2 = $common_action_log_model->getTableName()." as ca on(ca.user=$uid and ca.object=concat('comments',c.id))";
		$join_user = "__USERS__ as u on(u.id=c.uid)";
		$fields = "u.avatar,c.id,c.post_id,c.uid,c.content,c.full_name,c.email,c.parentid,c.comment_like,c.createtime,if(ca.id>0,1,0) as is_liked,p.post_title,p.smeta";

		$where=array("c.uid"=>$uid);
		$where['c.status']=1;
		$count=intval($this->comments_model->alias("c")->join($join1)->join($join2,'left')->join($join_user,'left')->where($where)->count());

		$page=$this->page($count,20);

		$comments=$this->comments_model
			->alias("c")
			->join($join1)
			->join($join2,'left')
			->join($join_user,'left')
			->field($fields)
			->where($where)
			->order("createtime desc")
			->limit($page->firstRow . ',' . $page->listRows)
			->select();
//        $data['sql'] = $this->comments_model->getLastSql();



		foreach($comments as &$comment){
			$comment['id'] = intval($comment['id']);
			$comment['uid'] = intval($comment['uid']);
			$comment['post_id'] = intval($comment['post_id']);
			$comment['parentid'] = intval($comment['parentid']);
			$comment['comment_like'] = intval($comment['comment_like']);
			$comment['is_liked'] = intval($comment['is_liked']);
			$smeta = json_decode( $comment['smeta']);
			unset($comment['smeta']);
			$comment['post_thumb'] = sp_get_asset_upload_path($smeta->thumb);
			$comment['avatar'] = sp_get_user_avatar_url($comment['avatar']);

			/**
			 *
			 */
			$limit = 3;
			$names_fields = 'ca.*,u.user_nicename,u.avatar,u.id as uuid';
			$names_count = (int)$common_action_log_model
				->field($names_fields)
				->alias('ca')
				->join("__USERS__ as u on(u.id=ca.user)")
				->where(array('ca.object'=>"comments".$comment['id']))
				->count();

			$names = $common_action_log_model
				->field($names_fields)
				->alias('ca')
				->join("__USERS__ as u on(u.id=ca.user)")
				->limit($limit)
				->where(array('ca.object'=>"comments".$comment['id']))
				->select();

			$users = array();
			foreach ($names as $u) {
				$arr = array();
				$arr['uid'] = (int)$u['uuid'];
				$arr['avatar'] = sp_get_user_avatar_url($u['avatar']);
				$arr['user_nicename'] = $u['user_nicename'];
				$users[] = $arr;
			}
			$comment['like_users'] = array();

			$comment['like_users']['count'] = $names_count;
			$comment['like_users']['list'] = $users;

		}

		$data['data']['comments'] = array();
		$data['data']['comments']['count'] = $count;
		$data['data']['comments']['total_pages'] = $page->getTotalPages();
		$data['data']['comments']['list'] = $comments;
//		$data['url'] = U('Portal/Article/index');
		$this->ajaxReturn($data);
	}


	function profile(){
		$this->ajaxReturn(array('data'=>array('profile'=>$this->user)));
	}

	// 编辑用户资料提交
	public function profile_post() {
		if(IS_POST){
			//更新妮称
			$user_nicename = I('post.user_nicename','');

			if ($this->users_model->where(array('id'=>$this->user->id))->save(array('user_nicename'=>$user_nicename))!==false) {
				$this->user=get_user_with_token($this->token);
				$this->ajaxReturn(array('data'=>array('profile'=>$this->user)));

			} else {
				$this->error("保存失败！");
			}
		}
	}

	// 用户头像上传
	public function avatar_upload(){
		$config=array(
			'rootPath' => './'.C("UPLOADPATH"),
			'savePath' => './avatar/',
			'maxSize' => 512000,//500K
			'saveName'   =>    array('uniqid',''),
			'exts'       =>    array('jpg', 'png', 'jpeg'),
			'autoSub'    =>    false,
		);
		$upload = new \Think\Upload($config);//先在本地裁剪
		$info=$upload->upload();
		//开始上传
		if ($info) {

			$user_m = M('Users');
			//上传成功
			//写入附件数据库信息
			$first=array_shift($info);
			$file_path = $first['savepath'].$first['savename'];
			$imgurl=sp_get_image_url($file_path);

			$old_img=$this->user->avatar;
			$this->user->avatar=$file_path;
			$res=$user_m->where(array("id"=>$this->user->id))->save((array)$this->user);
			if($res){
				//删除旧头像
				sp_delete_avatar($old_img);
			}else{
				$this->user->avatar=$old_img;
				//删除新头像
				sp_delete_avatar($imgurl);
			}

			$this->ajaxReturn(sp_ajax_return(array("file"=>$imgurl),"上传成功！",1),"JSON");
		} else {
			//上传失败，返回错误
			$this->ajaxErrorReturn($upload->getError());
		}
	}


	public function notifications(){
		$notification_m = M('UserNotification');
		$total_size = (int) $notification_m
			->field('n.*,u.user_nicename as from_user')
			->alias('n')
			->join($this->users_model->getTableName()." u on(n.from_uid=u.id)",'left')->where(array('n.uid'=>$this->user->id))->count();

		$page_size = 20;
		$page_param = C("VAR_PAGE");
		$page = new \Page($total_size,$page_size);
		$page->__set("PageParam", $page_param);

		$notifications = $notification_m
			->field('n.*,(UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(n.createtime)) as period,n.is_unread')
			->alias('n')
			->order("n.createtime desc")
			->where(array('status'=>1))
			->limit($page->firstRow, $page->listRows)
			->join($this->users_model->getTableName()." u on(n.from_uid=u.id)",'left')->where(array('n.uid'=>$this->user->id))->select();

		foreach ($notifications as &$noty) {
			$noty['id']=intval($noty['id']);
			if($noty['type']==0){
				$noty['title'] = convert_post_date2period(intval($noty['period']))."前,". $noty['content']."【".$noty['title']."】";
			}else{
				$noty['title'] = convert_post_date2period(intval($noty['period']))."前,". $noty['title'];
			}
			$noty['is_unread'] = intval($noty['is_unread']);
			unset($noty['period']);
		}

		$result = array();
		$result['total_pages']=$page->getTotalPages(); // 总页数
		$result['count']=$total_size;
		$result['list'] = $notifications;
		$this->ajaxReturn(array('data'=>array('notifications'=>$result)));
	}

	// 留言提交
	public function post_feedback(){
		$guestbook_m = D("Common/Guestbook");
		if (IS_POST) {
			$post = array();
			$post['full_name'] = empty($this->user->user_nicename)?$this->user->user_login:$this->user->user_nicename;
			$post['msg'] = I('post.msg','');
			$post['email'] = $this->user->user_email;
			$post['title'] = "用户反馈";
			$post['createtime'] = date('Y-m-d H:i:s');
			if ($guestbook_m->create($post)!==false) {
				$result=$guestbook_m->add();
				if ($result!==false) {
					$this->success("留言成功！");
				} else {
					$this->error("留言失败！");
				}
			} else {
				$this->error($guestbook_m->getError());
			}
		}else{
			$this->error("留言失败！");
		}
	}
}

/**
 * @param int $period 秒
 * @return string
 */
function convert_post_date2period($period=0){
	if($period>24*3600){
		return (ceil($period/(24*3600))-1)."天";
	}elseif($period>3600){
		return (ceil($period/3600)-1)."小时";
	}elseif($period>60){
		return (ceil($period/60)-1)."分钟";
	}elseif($period>0){
		return $period."秒";
	}
}