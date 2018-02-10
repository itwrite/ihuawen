<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/7/19
 * Time: 15:16
 */

require_once("inc/function.php");

$pdo1 = getPdoOld();
$pdo2 = getPdoNew();

/**
$st = $pdo2->query("show columns from hw_users;");
$result = $st->fetchAll(\PDO::FETCH_ASSOC);

$arr = array();
foreach($result as $res){
$arr[$res['Field']] = "$".$res['Field'];
}

$insert_sql = "insert into hw_users (`".implode("`,`",array_keys($arr))."`) values (:".implode(",:",array_keys($arr)).") \r\n";
print_r($insert_sql);

print_r(array_eval($arr));
exit;
/**/


/**
 * 清空数据
 */
$delete_users_sql = "delete from hw_users  WHERE id>3";
$pdo2->exec($delete_users_sql);


/**
 * 统计
 */
/**/
$st = $pdo1->query("select count(*) as total from user;");
$obj = $st->fetchObject();

$p_size = 1000;
$total_pages = ceil($obj->total/$p_size);
//print($total_pages);
//exit;

for($i=0;$i<$total_pages;$i++){
    $offset = $i*$p_size;
    $sql = "select * from user  limit $offset,$p_size";
    $st = $pdo1->query($sql);
    if($st!==false){
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        $_count = count($list);
        $_sql = "insert into hw_users (`user_login`,`user_pass`,`user_nicename`,`user_email`,`user_url`,`avatar`,`sex`,`birthday`,`signature`,`last_login_ip`,`last_login_time`,`create_time`,`user_activation_key`,`user_status`,`score`,`user_type`,`coin`,`mobile`) values ";
        $insert_sql = "";
        foreach($list as $i=>$row){
            $post_fields = array(
                'user_login'=>$row['uid'],
                'user_pass'=>sp_password('123456'),
                'user_nicename'=>$row['name'],
                'user_email'=>'',
                'user_url'=>'',
                'avatar'=>$row['avatar'],
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
            $str = "('".implode("','",array_values($post_fields))."')";
            if($i>0){
                if($i%100==0 || $i== $_count-1){
                    echo $_sql.$insert_sql.";\r\n";
                    $pdo2->exec($_sql.$insert_sql);
                    $insert_sql = "";
                }else{
                    $insert_sql .= ",".$str;
                }
            }else{
                $insert_sql.=$str;
            }
        }
    }
}
/**/

/**
 *
*/

/**
 * 统计
 */
$st = $pdo1->query("select count(*) as total from editor;");
$obj = $st->fetchObject();

$p_size = 1000;
$total_pages = ceil($obj->total/$p_size);
//print($total_pages);
//exit;

for($i=0;$i<$total_pages;$i++){
    $offset = $i*$p_size;
    $sql = "select * from editor  limit $offset,$p_size";
    $st = $pdo1->query($sql);
    if($st!==false){
        $list = $st->fetchAll(PDO::FETCH_ASSOC);

        $_sql = "insert into hw_users (`user_login`,`user_pass`,`user_nicename`,`user_email`,`user_url`,`avatar`,`sex`,`birthday`,`signature`,`last_login_ip`,`last_login_time`,`create_time`,`user_activation_key`,`user_status`,`score`,`user_type`,`coin`,`mobile`) values ";
        $insert_sql = "";
        $_count = count($list);
        foreach($list as $i=>$row){
            $post_fields = array(
                'user_login'=>'admin_'.$row['uid'],
                'user_pass'=>sp_password('123456'),
                'user_nicename'=>$row['name'],
                'user_email'=>'',
                'user_url'=>'',
                'avatar'=>'',
                'sex'=>'1',
                'birthday'=>'',
                'signature'=>'',
                'last_login_ip'=>'',
                'last_login_time'=>date('Y-m-d H:i:s'),
                'create_time'=>date('Y-m-d H:i:s'),
                'user_activation_key'=>'',
                'user_status'=>1,
                'score'=>0,
                'user_type'=>1,//用户类型，1:admin ;2:会员
                'coin'=>0,
                'mobile'=>''
            );
            $str = "('".implode("','",array_values($post_fields))."')";
            if($i>0){
                if($i%100==0 || $i==$_count-1){
                    echo $_sql.$insert_sql.";\r\n";
                    $pdo2->exec($_sql.$insert_sql);
                    $insert_sql = "";
//                    exit;
                }else{
                    $insert_sql .= ",".$str;
                }
            }else{
                $insert_sql.=$str;
            }
        }
    }
}

/**
 * 给新的管理员加权限
 */

$st = $pdo2->query("select * from hw_users WHERE user_login like 'admin%' AND id>3");
if($st!=false){
    $list = $st->fetchAll();
    $insert_sql = "insert into ";
    $arr = array();
    foreach($list as $i=>$row){

        $arr[]=$row['id'];
    }
}
