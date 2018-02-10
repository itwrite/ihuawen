<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/6/15
 * Time: 15:07
 */

namespace MobileApi\Controller;


use Common\Controller\ApibaseController;

class LoginController extends ApibaseController{

    function index(){
        $this->dologin();
    }

    // 登录验证提交
    public function dologin(){

        if(!sp_check_verify_code()){
            //$this->error("验证码错误！");
        }

        $users_model=M("Users");

        $rules = array(
            //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
            array('username', 'require', '手机号/邮箱/用户名不能为空！', 1 ),
            array('password','require','密码不能为空！',1),

        );

        if($users_model->validate($rules)->create()===false){
            $this->ajaxErrorReturn($users_model->getError());
        }

        $username=I('post.username');

        if(preg_match('/(^(13\d|15[^4\D]|17[13678]|18\d)\d{8}|170[^346\D]\d{7})$/', $username)){//手机号登录
            $this->_do_mobile_login();
        }else{
            $this->_do_email_login(); // 用户名或者邮箱登录
        }

    }

    // 处理前台用户手机登录
    private function _do_mobile_login(){
        $users_model=M('Users');
        $where = array("user_status"=>1);
        $where['mobile']=I('post.username');
        $password=I('post.password');
        $result = $users_model->where($where)->find();

        if(!empty($result)){
            if(sp_compare_password($password, $result['user_pass'])){
                session('user',$result);

                $expire_times = 3600*24*3;
                if(I('request.remember',0,'intval')==1){
                    $expire_times = 3600*24*365;
                }
                $ip = get_client_ip(0,true);
                $token = md5(json_encode($result));
                save_token($token,$ip,$result['id'],0,$expire_times);

                //写入此次登录信息
                $data = array(
                    'last_login_time' => date("Y-m-d H:i:s"),
                    'last_login_ip' => get_client_ip(0,true),
                );
                $users_model->where(array('id'=>$result["id"]))->save($data);
                $session_login_http_referer=session('login_http_referer');
                $redirect=empty($session_login_http_referer)?__ROOT__."/":$session_login_http_referer;
                session('login_http_referer','');
                $this->ajaxReturn(array('info'=>"登录验证成功！",'code'=>0,'data'=>array('token'=>$token)));
//                $this->success("登录验证成功！", $redirect);
            }else{
                $this->ajaxErrorReturn("密码错误！");
            }
        }else{
            $this->ajaxErrorReturn("用户名不存在或已被拉黑！");
        }
    }

    // 处理前台用户邮件或者用户登录
    private function _do_email_login(){

        $username=I('post.username');
        $password=I('post.password');
        $where = array("user_status"=>1);
        if(strpos($username,"@")>0){//邮箱登陆
            $where['user_email']=$username;
        }else{
            $where['user_login']=$username;
        }

        $users_model=M('Users');
        $result = $users_model->where($where)->find();

        $ucenter_syn=C("UCENTER_ENABLED");

        $ucenter_old_user_login=false;

        $ucenter_login_ok=false;
        if($ucenter_syn){
            cookie("thinkcmf_auth","");
            include UC_CLIENT_ROOT."client.php";
            list($uc_uid, $username, $password, $email)=uc_user_login($username, $password);

            if($uc_uid>0){
                if(!$result){
                    $data=array(
                        'user_login' => $username,
                        'user_email' => $email,
                        'user_pass' => sp_password($password),
                        'last_login_ip' => get_client_ip(0,true),
                        'create_time' => time(),
                        'last_login_time' => time(),
                        'user_status' => '1',
                        'user_type'=>2,
                    );
                    $id= $users_model->add($data);
                    $data['id']=$id;
                    $result=$data;
                }

            }else{

                switch ($uc_uid){
                    case "-1"://用户不存在，或者被删除
                        if($result){//本应用已经有这个用户
                            if(sp_compare_password($password, $result['user_pass'])){//本应用已经有这个用户,且密码正确，同步用户
                                $uc_uid2=uc_user_register($username, $password, $result['user_email']);
                                if($uc_uid2<0){
                                    $uc_register_errors=array(
                                        "-1"=>"用户名不合法",
                                        "-2"=>"包含不允许注册的词语",
                                        "-3"=>"用户名已经存在",
                                        "-4"=>"Email格式有误",
                                        "-5"=>"Email不允许注册",
                                        "-6"=>"该Email已经被注册",
                                    );
                                    $this->ajaxErrorReturn("同步用户失败--".$uc_register_errors[$uc_uid2]);

                                }
                                $uc_uid=$uc_uid2;
                            }else{
                                $this->ajaxErrorReturn("密码错误！");
                            }
                        }

                        break;
                    case -2://密码错
                        if($result){//本应用已经有这个用户
                            if(sp_compare_password($password, $result['user_pass'])){//本应用已经有这个用户,且密码正确，同步用户
                                $uc_user_edit_status=uc_user_edit($username,"",$password,"",1);
                                if($uc_user_edit_status<=0){
                                    $this->ajaxErrorReturn("登陆错误！");
                                }
                                list($uc_uid2)=uc_get_user($username);
                                $uc_uid=$uc_uid2;
                                $ucenter_old_user_login=true;
                            }else{

                                $this->ajaxErrorReturn("密码错误！");
                            }
                        }else{
                            $this->ajaxErrorReturn("密码错误！");
                        }

                        break;

                }
            }
            $ucenter_login_ok=true;
            echo uc_user_synlogin($uc_uid);
        }
        //exit();
        if(!empty($result)){
            if(sp_compare_password($password, $result['user_pass'])|| $ucenter_login_ok){
                session('user',$result);

                $expire_times = 3600*24*3;
                if(I('request.remember',0,'intval')==1){
                    $expire_times = 3600*24*365;
                }
                $ip = get_client_ip(0,true);
                $token = md5(json_encode($result));
                save_token($token,$ip,$result['id'],0,$expire_times);


                //写入此次登录信息
                $data = array(
                    'last_login_time' => date("Y-m-d H:i:s"),
                    'last_login_ip' => $ip,
                );
                $users_model->where("id=".$result["id"])->save($data);

                $session_login_http_referer=session('login_http_referer');
                $redirect=empty($session_login_http_referer)?__ROOT__."/":$session_login_http_referer;
                session('login_http_referer','');
                $ucenter_old_user_login_msg="";

                if($ucenter_old_user_login){
                    //$ucenter_old_user_login_msg="老用户请在跳转后，再次登陆";
                }
                $this->ajaxReturn(array('info'=>"登录验证成功！",'code'=>0,'data'=>array('token'=>$token)));
            }else{
                $this->ajaxErrorReturn("密码错误！");
            }
        }else{
            $this->ajaxErrorReturn("用户名不存在或已被拉黑！");
        }

    }




    /**
     * ==============================================================================
     */

    // 前台用户注册提交
    public function register(){

        $username = I('post.username','');
        if(preg_match('/(^(13\d|15[^4\D]|17[13678]|18\d)\d{8}|170[^346\D]\d{7})$/', $username)){//手机号登录
            $this->_do_mobile_register();
        }else{
            $this->_do_email_register(); // 用户名或者邮箱登录
        }
    }

    // 前台用户手机注册
    private function _do_mobile_register(){

        if(!sp_check_verify_code()){
            //$this->error("验证码错误！");
        }

        $rules = array(
            //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
            array('username', 'require', '账号不能为空！', 1 ),
            array('username','','账号已被注册！！',0,'unique',3),
            array('password','require','密码不能为空！',1),
            array('password','5,20',"密码长度至少5位，最多20位！",1,'length',3),
        );

        $users_model=M("Users");

        if($users_model->validate($rules)->create()===false){
            $this->ajaxErrorReturn($users_model->getError());
        }

        if(!sp_check_mobile_verify_code()){
            //TODO
            //$this->error("手机验证码错误！");
        }

        $password=I('post.password');
        $username=I('post.username');

        $users_model=M("Users");
        $data=array(
            'user_login' => $username,
            'user_email' => '',
            'mobile' =>$username,
            'user_nicename' =>'',
            'user_pass' => sp_password($password),
            'last_login_ip' => get_client_ip(0,true),
            'create_time' => date("Y-m-d H:i:s"),
            'last_login_time' => date("Y-m-d H:i:s"),
            'user_status' => 1,
            "user_type"=>2,//会员
        );

        $result = $users_model->add($data);
        if($result){
            //注册成功页面跳转
            $data['id']=$result;
            session('user',$data);


            $expire_times = 3600*24*3;
            if(I('request.remember',0,'intval')==1){
                $expire_times = 3600*24*365;
            }
            $ip = get_client_ip(0,true);
            $token = md5(json_encode($result));
            save_token($token,$ip,$result['id'],0,$expire_times);

            $this->ajaxReturn(array('info'=>"注册成功！",'code'=>0,'token'=>$token));

        }else{
            $this->ajaxErrorReturn("注册失败！");
        }
    }

    // 前台用户邮件注册
    private function _do_email_register(){

        if(!sp_check_verify_code()){
            //$this->error("验证码错误！");
        }

        $rules = array(
            //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
            array('username', 'require', '账号不能为空！', 1 ),
//            array('username','','账号已被注册！！',0,'unique',3),
            array('password','require','密码不能为空！',1),
            array('password','5,20',"密码长度至少5位，最多20位！",1,'length',3),
            array('repassword', 'require', '重复密码不能为空！', 1 ),
            array('repassword','password','确认密码不正确',0,'confirm'),
//            array('email','email','邮箱格式不正确！',1), // 验证email字段格式是否正确
        );

        $users_model=M("Users");

        if($users_model->validate($rules)->create()===false){
            $this->ajaxErrorReturn($users_model->getError());
        }

        $password=I('post.password');
        $username=I('post.username');
        $user_login=str_replace(array(".","@"), "_",$username);
        //用户名需过滤的字符的正则
        $stripChar = '?<*.>\'"';
        if(preg_match('/['.$stripChar.']/is', $user_login)==1){
            $this->ajaxErrorReturn('用户名中包含'.$stripChar.'等非法字符！');
        }

// 	    $banned_usernames=explode(",", sp_get_cmf_settings("banned_usernames"));

// 	    if(in_array($username, $banned_usernames)){
// 	        $this->error("此用户名禁止使用！");
// 	    }

        $email = (strpos($username,"@")>0)?$username:"";
        $where['user_login']=$user_login;
        if(!empty($email)){
            $where['_logic'] = 'OR';
            $where['user_email']= $email;
        }

        $ucenter_syn=C("UCENTER_ENABLED");
        $uc_checkemail=1;
        $uc_checkusername=1;
        if($ucenter_syn){
            include UC_CLIENT_ROOT."client.php";
            $uc_checkemail=uc_user_checkemail($username);
            $uc_checkusername=uc_user_checkname($username);
        }

        $users_model=M("Users");
        $result = intval($users_model->where($where)->count());
//        $sql = $users_model->getLastSql();
//        $this->ajaxReturn(array('data'=>$result,'sql'=>$sql));exit;
        if($result || $uc_checkemail<0 || $uc_checkusername<0){
            $this->ajaxErrorReturn("用户名或者该邮箱已经存在！");
        }else{
            $uc_register=true;
            if($ucenter_syn){

                $uc_uid=uc_user_register($username,$password,$username);
                //exit($uc_uid);
                if($uc_uid<0){
                    $uc_register=false;
                }
            }
            if($uc_register){
                $need_email_active=C("SP_MEMBER_EMAIL_ACTIVE");
                $data=array(
                    'user_login' => $username,
                    'user_email' => $email,
                    'user_nicename' =>$username,
                    'user_pass' => sp_password($password),
                    'last_login_ip' => get_client_ip(0,true),
                    'create_time' => date("Y-m-d H:i:s"),
                    'last_login_time' => date("Y-m-d H:i:s"),
                    'user_status' => $need_email_active?2:1,
                    "user_type"=>2,//会员
                );
                $w=array();
                $w['user_login'] = $username;
                if(!empty($email)){
                    $w['_logic'] = 'OR';
                    $w['user_email']= $email;
                }

                $count = intval($users_model->where($w)->count());
                if($count>0){
                    $this->ajaxErrorReturn("注册失败,账号已被注册！");
                }else{
                    $rst = $users_model->add($data);
                    if($rst){
                        //注册成功页面跳转
                        $data['id']=$rst;
                        session('user',$data);

                        $expire_times = 3600*24*3;
                        if(I('request.remember',0,'intval')==1){
                            $expire_times = 3600*24*365;
                        }
                        $ip = get_client_ip(0,true);
                        $token = md5(json_encode($data));
                        save_token($token,$ip,$data['id'],0,$expire_times);


                        //发送激活邮件
                        if($need_email_active&&false){//取消邮件激活账号
                            $this->_send_to_active();
                            session('user',null);

                            $this->ajaxReturn(array('info'=>"注册成功,激活后才能使用！",'code'=>0,'data'=>array('token'=>$token)));
                        }else {
                            $this->ajaxReturn(array('info'=>"注册成功！",'code'=>0,'data'=>array('token'=>$token)));
                        }

                    }else{
                        $this->ajaxErrorReturn("注册失败！");
                    }
                }


            }else{
                $this->ajaxErrorReturn("注册失败！");
            }

        }
    }

    /**
     * 发送注册激活邮件
     */
    protected  function _send_to_active(){
        $option = M('Options')->where(array('option_name'=>'member_email_active'))->find();
        if(!$option){
            $this->error('网站未配置账号激活信息，请联系网站管理员');
        }
        $options = json_decode($option['option_value'], true);
        //邮件标题
        $title = $options['title'];
        $uid=session('user.id');
        $username=session('user.user_login');

        $activekey=md5($uid.time().uniqid());
        $users_model=M("Users");

        $result=$users_model->where(array("id"=>$uid))->save(array("user_activation_key"=>$activekey));
        if(!$result){
            $this->error('激活码生成失败！');
        }
        //生成激活链接
        $url = U('user/register/active',array("hash"=>$activekey), "", true);
        //邮件内容
        $template = $options['template'];
        $content = str_replace(array('http://#link#','#username#'), array($url,$username),$template);

        $send_result=sp_send_email(session('user.user_email'), $title, $content);

        if($send_result['error']){
            $this->error('激活邮件发送失败，请尝试登录后，手动发送激活邮件！');
        }
    }

}