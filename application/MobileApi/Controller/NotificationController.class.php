<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/7/26
 * Time: 11:48
 */

namespace MobileApi\Controller;



use Common\Controller\ApibaseUserController;

class NotificationController extends ApibaseUserController{

    public function index(){

    }

    public function detail(){
        $id = I('get.notification_id',0);
        $notification_m = M('UserNotification');

        $notification = $notification_m->find($id);
        if($notification){

            switch($notification['object_table']){

                case 'comments':

                    $comments_m = D("Common/Comments");
                    $common_action_log_model=M("CommonActionLog");

                    $uid=$this->user->id;
                    $join1 = "__POSTS__ as p on p.id=c.post_id";
                    $join2 = $common_action_log_model->getTableName()." as ca on(ca.user=$uid and ca.object=concat('comments',c.id))";
                    $join_user = "__USERS__ as u on(u.id=c.uid)";
                    $fields = "u.avatar,c.id,c.post_id,c.uid,c.content,c.full_name,c.email,c.parentid,c.comment_like,c.createtime,if(ca.id>0,1,0) as is_liked,p.post_title,p.smeta";

                    $comment = $comments_m
                        ->alias("c")
                        ->join($join1)
                        ->join($join2,'left')
                        ->join($join_user,'left')
                        ->field($fields)
                        ->where(array('c.id'=>intval($notification['object_id'])))
                        ->find();
                    if($comment){
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

                        $this->ajaxReturn(array('data'=>array('comment'=>$comment)));
                    }else{
                        $this->error("消息不存在！");
                    }
                    break;
//                case 'posts':
//                    break;
                default:
                    $this->error("消息不存在！");
            }
        }else{
            $this->error("消息不存在！");
        }
    }
}