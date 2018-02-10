<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/10/24
 * Time: 12:19
 */

namespace Portal\Controller;


use Common\Controller\AdminbaseController;

class AdminNotificationController  extends AdminbaseController{
    function index(){

    }
    function broadcast(){

        $this->display();
    }

    function broadcast_post(){
        $broadcast_type=I('post.broadcast_type','');
        $broadcast_content = I('post.broadcast_content','');

        if(empty($broadcast_content)){
            $this->error("请填写消息内容！");
            exit;
        }

        $broadcast_content = htmlspecialchars($broadcast_content);

        $broadcast_thumb = "";
        if(isset($_FILES['thumb_file'])){
            $all_allowed_exts=explode(',',"jpg,jpeg,png,gif,bmp4,svg");
            $upload_max_filesize=2097152;//默认2M

            $savepath='notify/'.date('Ymd').'/';
            //上传处理类
            $config=array(
                'rootPath' => './'.C("UPLOADPATH"),
                'savePath' => $savepath,
                'maxSize' => $upload_max_filesize,
                'saveName'   =>    array('uniqid',''),
                'exts'       =>    $all_allowed_exts,
                'autoSub'    =>    false,
            );
            $upload = new \Think\Upload($config);//
            $info=$upload->upload();

            if($info){
                //上传成功
                $oriName = $_FILES['file']['name'];
//					echo $oriName;exit;
                //写入附件数据库信息
                $first=array_shift($info);
                $url=C("TMPL_PARSE_STRING.__UPLOAD__").$savepath.$first['savename'];
                $preview_url=$url;
                $filepath = $savepath.$first['savename'];

                $broadcast_thumb = $filepath;
            }
        }

        $users_m = M('Users');
        $where = array('user_type'=>2);

        if(strtolower(trim($broadcast_type))=='particularuser'){
            $where['id']=I('post.user_id',0,'intval');
        }

        $users = $users_m->where($where)->order('id asc')->select();

//        print_r($broadcast_thumb);exit;
        foreach ($users as $user) {

            /**
             * 通知
             */
            $notification_m = M('UserNotification');
            $notification_m->add(array(
                'uid'=>$user['id'],
                'from_uid'=>sp_get_current_admin_id(),
                'title'=>'系统消息',
                'thumb'=>$broadcast_thumb,
                'content'=>$broadcast_content,
                'object_id'=>0,
                'object_table'=>'',
                'type'=>0
            ));
        }

        $this->success("已经发送");
    }
}