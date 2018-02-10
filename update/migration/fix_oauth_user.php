<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/10/24
 * Time: 16:01
 */
require_once("inc/function.php");
function &_pdo(){
    static $___pdo_;
    if(!$___pdo_ instanceof \PDO){
        $dsn = "mysql:host=31.28.92.174;dbname=admin_aisimob_ihuawen";
        $username = "admin_aisimob";
        $password = "TestPw123!";
        $___pdo_  = new \PDO($dsn,$username, $password,array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
    }
    return $___pdo_;
}


$pdo = _pdo();

$st = $pdo->query("SELECT * FROM hw_oauth_user where uid>380");

if($st){
    $oauth_users = $st->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($oauth_users as $row) {

        $stt = $pdo->query("select * from hw_users WHERE user_login='".$row['uid']."'");

        if($stt && $user=$stt->fetch(PDO::FETCH_ASSOC)){
            $count++;
            $pdo->exec("update hw_oauth_user set uid='".$user['id']."' where id=".intval($row['id']));
        }else{
            $post_fields = array(
                'user_login'=>$row['uid'],
                'user_pass'=>sp_password('123456'),
                'user_nicename'=>$row['name'],
                'user_email'=>'',
                'user_url'=>'',
                'avatar'=>$row['head_img'],
                'sex'=>'1',
                'birthday'=>'',
                'signature'=>'',
                'last_login_ip'=>'',
                'last_login_time'=>date('Y-m-d H:i:s'),
                'create_time'=>date('Y-m-d H:i:s'),
                'user_activation_key'=>'',
                'user_status'=>1,
                'score'=>0,
                'user_type'=>2,//用户类型，1:admin ;2:会员
                'coin'=>0,
                'mobile'=>''
            );

            $stmt = $pdo->prepare("insert into hw_users (`".implode('`,`',array_keys($post_fields))."`) values ('".implode("','",array_values($post_fields))."')");

            $stmt->execute();
            $insert_id = $pdo->lastInsertId();
            $pdo->exec("update hw_oauth_user set uid='".$insert_id."' where id=".intval($row['id']));
            $count++;
        }
        EchoR($count);
    }

}