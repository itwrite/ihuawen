<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/8/1
 * Time: 14:05
 */

require_once("inc/function.php");

//$pdo1 = getPdoOld();
$pdo2 = getPdoNew();


$st = $pdo2->query("select * from hw_posts_tag");
$tag_list = $st->fetchAll(PDO::FETCH_ASSOC);



/**
 * 统计
 */
$st = $pdo2->query("select count(*) as total from hw_posts;");
$obj = $st->fetchObject();


/**
 * 获取数据
 */
$p_size = 1000;
$total_pages = ceil($obj->total/$p_size)+1;

/**
 * 将所有的post 过一遍
 */
$count_i = 0;
for($p=0;$p<$total_pages;$p++){
    $offset = $p*$p_size;
    $sql = "select * from hw_posts WHERE 1 limit $offset,$p_size";
    $st = $pdo2->query($sql);
    if($st!==false){
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        $_count = count($list);
        foreach($list as $i=>$row){

            $post_id = $row['id'];

            $smeta = json_decode($row['smeta'],true);

            if(isset($smeta['serial_number'])&& $num = intval($smeta['serial_number'])){

                $pdo2->exec("update hw_posts set serial_number={$num} where id={$post_id}");
            }

        }
    }
}

echo "done!!";
exit;