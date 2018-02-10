<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/6/19
 * Time: 10:20
 */

namespace MobileApi\Controller;


use Common\Controller\ApibaseController;

class CommentController extends ApibaseController{

    protected $comments_model;

    public function _initialize() {
        parent::_initialize();
        $this->comments_model=D("Common/Comments");
    }

    function index(){

        $common_action_log_model=M("CommonActionLog");

        $data = array();

        $post_id = I('request.post_id',0,'intval');
        $uid = 0;
        if($user = get_user_with_token(I('request.token',''))){
            $uid = $user['id'];
        }

        $join1 = "__POSTS__ as p on p.id=c.post_id";
        $join2 = "__USERS__ as u on(c.uid=u.id)";
        $field1 = ",IF ((SELECT count(*) FROM hw_common_action_log WHERE `user`=$uid AND object = concat('comments', c.id)) > 0, 1, 0) AS is_liked";
        $fields = "u.avatar,c.id,c.post_id,c.uid,c.content,c.full_name,c.email,c.parentid,c.comment_like,c.createtime $field1";

        $where=array("c.post_id"=>$post_id);
        $where['c.status']=1;
        $count=intval($this->comments_model->alias("c")->join($join1)->join($join2,'left')->where($where)->count());

        $page=$this->page($count,20);

        $comments=$this->comments_model
            ->alias("c")
            ->join($join1)
            ->join($join2,'left')
            ->field($fields)
            ->where($where)
            ->order("createtime desc")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
//        $data['sql'] = $this->comments_model->getLastSql();

        foreach($comments as &$comment){
            $comment['avatar'] = sp_get_user_avatar_url($comment['avatar']);
            $comment['id'] = intval($comment['id']);
            $comment['uid'] = intval($comment['uid']);
            $comment['post_id'] = intval($comment['post_id']);
            $comment['parentid'] = intval($comment['parentid']);
//            $comment['comment_like'] = intval($comment['comment_like']);
            $comment['is_liked'] = intval($comment['is_liked']);
            $comment['comment_like'] = (int)$common_action_log_model->field('ca.*,u.user_nicename')->alias('ca')->join("__USERS__ as u on(u.id=ca.user)",'left')->where(array('ca.object'=>"comments".$comment['id']))->count();

            /**
             *
             */
            $limit = 3;

            $names_fields = 'ca.*,u.user_nicename,u.avatar,u.id as uuid';

            $names_count = (int)$common_action_log_model->field($names_fields)
                ->alias('ca')->join("__USERS__ as u on(u.id=ca.user)")
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

        $this->ajaxReturn($data);
    }

    // 文章点赞
    public function do_like(){
        $this->check_login();
        $id = I('post.id',0,'intval');//comments表中id
        if(IS_POST&&$id>0){


            $comments_model=M("Comments");

            $can_like=Api_check_user_action($this->token,"comments$id",1);

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
                        'from_uid'=>$this->user->id,
                        'title'=>'"'.$this->user->user_nicename.'"赞了您的评论',
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

    public function post(){
        $this->check_login();

        if (IS_POST){

            $post_table='posts';
            $post_table=ucwords(str_replace("_", " ", $post_table));
            $post_table=str_replace(" ","",$post_table);
            $post_table_model=M($post_table);

            $pk=$post_table_model->getPk();

            $post_id =  I('post.post_id',0);
            $article = $post_table_model->where(array($pk=>$post_id))->find();

            if($article){
                $post = array();
                $post['post_table']='posts';

                $post['content'] = I('post.content','');
                $post['post_id'] = $post_id;
                $post['to_uid'] = I('post.to_uid',0,'intval');
                $post['parentid'] = I('post.parent_id',0,'intval');
                $post['url'] = U('portal/article/index',array('id'=>$post['post_id']));

                $post['uid'] = $uid = $this->user->id;

                $users_model=M('Users');
                $user=$users_model->field("user_login,user_email,user_nicename")->where("id=$uid")->find();
                $username=$user['user_login'];
                $user_nicename=$user['user_nicename'];
                $email=$user['user_email'];
                $post['full_name']=empty($user_nicename)?$username:$user_nicename;
                $post['email']=$email;

                if(C("COMMENT_NEED_CHECK")){
                    $post['status']=0;//评论审核功能开启
                }else{
                    $post['status']=1;
                }
                $data=$this->comments_model->create($post);
                if ($data!==false){
                    $this->check_last_action(intval(C("COMMENT_TIME_INTERVAL")));
                    $result=$this->comments_model->add($post);
                    if ($result!==false){

                        $post_table_model->create(array("comment_count"=>array("exp","comment_count+1"),"last_comment"=>time()));
                        $post_table_model->where(array($pk=>intval($post_id)))->save();

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

                        $this->ajaxReturn(sp_ajax_return(array("id"=>intval($result)),"评论成功！",1));
                    } else {
                        $this->error("评论失败！");
                    }
                } else {
                    $this->error($this->comments_model->getError());
                }
            }else{
                $this->error("网络异常，请重新提交！");
            }
        }else{
            $this->error("请求数据异常！");
        }
    }

    public function remove(){
        $this->check_login();

        $uid = intval($this->user->id);
        if (IS_POST){

            $comment_id = I('post.comment_id',0);

            $comments_m = M('Comments');
            $comment = $comments_m->where(array('id'=>$comment_id,'uid'=>$uid))->find();
            if($comment){

                /**
                 * 删除评论
                 */
                $res = $comments_m->where(array('id'=>$comment_id,'uid'=>$uid))->delete();
                if($res){
                    /**
                     * 注：
                     * 删除了评论后，就把相应的评论回复和点赞数据也删除
                     */
                    $comments_m->where(array('parentid'=>$comment_id))->delete();

                    $common_action_log_model=M("CommonActionLog");
                    $common_action_log_model->where(array('object'=>"comments$comment_id"))->delete();

                    $post_table_model =M("Posts");
                    $post_table_model->create(array("comment_count"=>array("exp","comment_count-1"),"last_comment"=>time()));
                    $post_table_model->where(array('id'=>intval($comment['post_id'])))->save();

                    $this->success("评论已删除！");

                }else{
                    $this->error("似乎访评论已不存在！请刷新！");
                }

            }else{
                $this->error("评论不存在！");
            }

        }else{
            $this->error("请求数据异常！");
        }
    }
}