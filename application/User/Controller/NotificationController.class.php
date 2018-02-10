<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/9/13
 * Time: 12:19
 */

namespace User\Controller;


use Common\Controller\MemberbaseController;

class NotificationController extends MemberbaseController {

    function index(){
        $notification_m = M('UserNotification');
        $total_size = (int) $notification_m
            ->field('n.*,u.user_nicename as from_user')
            ->alias('n')
            ->where(array('status'=>1))
            ->join($this->users_model->getTableName()." u on(n.from_uid=u.id)",'left')->where(array('n.uid'=>$this->user['id']))->count();

        $page_size = 20;
        $page_param = C("VAR_PAGE");
        $page = new \Page($total_size,$page_size);
        $page->__set("PageParam", $page_param);

        $notifications = $notification_m
            ->field('n.*,(UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(n.createtime)) as period,n.is_unread')
            ->alias('n')
            ->limit($page->firstRow, $page->listRows)
            ->order("n.createtime desc")
            ->where(array('status'=>1))
            ->join($this->users_model->getTableName()." u on(n.from_uid=u.id)",'left')->where(array('n.uid'=>$this->user['id']))->select();

        foreach ($notifications as &$noty) {
            $noty['id']=intval($noty['id']);
            $noty['date2'] = convert_post_date2period(intval($noty['period']))."前";
            $noty['is_unread'] = intval($noty['is_unread']);
            unset($noty['period']);
        }
        $this->assign('notifications',$notifications);
        $this->display(":notification");
    }

    function delete(){
        if(IS_POST){
            $notification_m = M('UserNotification');

            $id = I('post.id');
            if($notification_m->where(array('uid'=>$this->user['id'],'id'=>$id))->save(array('status'=>0))){
                $this->success("移除成功");
            }else{
                $this->error("非法操作");
            }
        }else{
            $this->error("非法操作");
        }
    }
}