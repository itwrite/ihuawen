<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/7/5
 * Time: 14:59
 */

namespace MobileApi\Controller;


use Common\Controller\ApibaseController;

class OauthController extends ApibaseController{

    function callback_login_handle(){
        $oauth_user_model = M('OauthUser');
        $type=strtolower(I('request.type',''));
        $openid = I('request.openid','');
        $name = I('request.name','');
        $head_img = I('request.head_img','');
        $access_token = I('request.access_token','');
        $expires_in = I('request.expires_in',0,'intval');

        $ip = get_client_ip(0,true);

        $find_oauth_user = $oauth_user_model->where(array("from"=>$type,"openid"=>$openid))->find();

        $need_register = true;

        if($find_oauth_user){
            $find_user = M('Users')->where(array("id"=>$find_oauth_user['uid']))->find();
            if($find_user){
                $need_register=false;
                if($find_user['user_status'] == '0'){
                    $this->error('您可能已经被列入黑名单，请联系网站管理员！');exit;
                }else{
                    session('user',$find_user);

                    $token = md5(json_encode($find_user));
                    save_token($token,$ip,$find_user['id']);
                    $this->ajaxReturn(array('info'=>"登录成功！",'code'=>0,'data'=>array('token'=>$token)));
                }
            }else{
                $need_register=true;
            }
        }

        if($need_register){
            //本地用户中创建对应一条数据
            $new_user_data = array(
                'user_nicename' => $name,
                'avatar' => $head_img,
                'last_login_time' => date("Y-m-d H:i:s"),
                'last_login_ip' => get_client_ip(0,true),
                'create_time' => date("Y-m-d H:i:s"),
                'user_status' => '1',
                "user_type"	  => '2',//会员
            );
            $users_model=M("Users");
            $new_user_id = $users_model->add($new_user_data);

            if($new_user_id){
                //第三方用户表中创建数据
                $new_oauth_user_data = array(
                    'from' => $type,
                    'name' => $name,
                    'head_img' => $head_img,
                    'create_time' =>date("Y-m-d H:i:s"),
                    'uid' => $new_user_id,
                    'last_login_time' => date("Y-m-d H:i:s"),
                    'last_login_ip' => get_client_ip(0,true),
                    'login_times' => 1,
                    'status' => 1,
                    'access_token' => $access_token,
                    'expires_date' => (int)(time()+$expires_in),
                    'openid' => $openid,
                );
                $new_oauth_user_id=$oauth_user_model->add($new_oauth_user_data);
                if($new_oauth_user_id){
                    $find_user = $users_model->where(array("id"=>$new_user_id))->find();

                    session('user',$find_user);
                    $token = md5(json_encode($find_user));
                    save_token($token,$ip,$find_user['id']);
                    $this->ajaxReturn(array('info'=>"登录成功！",'code'=>0,'data'=>array('token'=>$token)));
                }else{
                    $users_model->where(array("id"=>$new_user_id))->delete();
                    $this->error("登陆失败");
                }
            }else{
                $this->error("登陆失败");
            }

        }


    }
}