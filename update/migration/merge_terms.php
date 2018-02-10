<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/9/30
 * Time: 17:08
 */

require_once("inc/function.php");

$pdo1 = getPdoOld();
$pdo2 = getPdoNew();

/**
 * 合并分类以及关联文章
 */
//13,18,26,28
$map = array(
    '新闻资讯' => array(2, 42),
    '观察评论' => array(27, 36, 35),
    '商业理财' => array(5, 12,43),
    '教育职场' => array(3, 23, 24, 9, 32, 31, 30, 11, 14, 33),
    '科技创新' => array(38),
    '文化设计' => array(25),
    '生活时尚' => array(1, 34, 15, 16, 7, 10, 22, 29, 17 ),
    '华闻专栏' => array(41),
    '轻阅读' => array(37),
    '华人动态' => array(4, 6, 39, 20, 19, 21, 8, 40)
);

foreach ($map as $new_name => $items) {

    $items_ids = implode(',',$items);
    $sql = "select * from `hw_terms` WHERE old_term_id in($items_ids)";
    $st = $pdo2->query($sql);
    $list = $st->fetchAll(\PDO::FETCH_ASSOC);
    $first_id = 0;
    foreach ($list as $i => $row) {
        $id = $row['term_id'];
        $old_id = $row['old_term_id'];
        if (0 === $i) {
            EchoR('合并分类到',$old_id);
            $first_id = $id;
            $update_sql = "update `hw_terms` set name=:new_term,status=1 where old_term_id=:old_term_id";
            $update_stmt = $pdo2->prepare($update_sql);
            $update_stmt->bindParam(':old_term_id', $old_id);
            $update_stmt->bindParam(':new_term', $new_name);
            $update_stmt->execute();
        }else{
            $update_sql = "update `hw_terms` set status=-1 where old_term_id=:old_term_id";
            $update_stmt = $pdo2->prepare($update_sql);
            $update_stmt->bindParam(':old_term_id', $old_id);
            $update_stmt->execute();

            if($first_id!=0){
                $update_sql = "update `hw_term_relationships` set term_id=:tid where term_id=:term_id";
                $update_stmt = $pdo2->prepare($update_sql);
                $update_stmt->bindParam(':term_id', $id);
                $update_stmt->bindParam(':tid', $first_id);
                $update_stmt->execute();
            }else{
                $update_sql = "update `hw_term_relationships` set status=-1 where term_id=:term_id";
                $update_stmt = $pdo2->prepare($update_sql);
                $update_stmt->bindParam(':term_id', $id);
                $update_stmt->execute();
            }

        }
    } // end inner foreach
} // end outer foreach
EchoR("完成!");
exit('finished');