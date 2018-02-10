<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/9/30
 * Time: 15:19
 */

require_once("inc/function.php");

$pdoOld = getPdoOld();
$pdoNew = getPdoNew();

/**
 * 统计
 */
$st = $pdoOld->query("select count(*) as total from article;");
$obj = $st->fetchObject();


/**
 * 获取数据
 */
$p_size = 1000;
$total_pages = ceil($obj->total / $p_size) + 1;



$others = array();
$others_count = 0;

/**
$count_i = 0;
for ($p = 0; $p < $total_pages; $p++) {
    $offset = $p * $p_size;
    $sql = "select * from article WHERE 1 limit $offset,$p_size";
    $st = $pdoOld->query($sql);
    if ($st !== false) {
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        $_count = count($list);
        $_sql = "insert into hw_posts (`old_id`,`user_id`,`author`,`post_keywords`,`post_source`,`post_date`,`post_content`,`post_title`,`post_excerpt`,`post_status`,`comment_status`,`post_modified`,`post_content_filtered`,`post_parent`,`post_type`,`post_mime_type`,`comment_count`,`smeta`,`post_hits`,`post_like`,`istop`,`recommended`) values ";
        $insert_sql = "";
        foreach ($list as $i => $row) {

            $st1 = $pdoNew->query("select * from hw_posts WHERE old_id>0 and old_id=" . $row['id']." or post_title='".$row['name']."'");
            $res = $st1->fetch(PDO::FETCH_ASSOC);
            $count_i++;
            if ($res == false) {
                $others[] = $row;
                $others_count++;
                EchoR($others_count);
            }else{
                EchoR($count_i);
            }
        }
    }
}

exit;
/**/

$pdoNew->exec("delete from hw_posts WHERE id in (1,2,3)");
$pdoNew->exec("delete from hw_term_relationships WHERE object_id in (1,2,3)");

$count_i = 0;
for ($p = 0; $p < $total_pages; $p++) {
    $offset = $p * $p_size;
    $sql = "select * from article WHERE 1 limit $offset,$p_size";
    $st = $pdoOld->query($sql);
    if ($st !== false) {
        $list = $st->fetchAll(PDO::FETCH_ASSOC);
        $_count = count($list);
        $_sql = "insert into hw_posts (`old_id`,`user_id`,`author`,`post_keywords`,`post_source`,`post_date`,`post_content`,`post_title`,`post_excerpt`,`post_status`,`comment_status`,`post_modified`,`post_content_filtered`,`post_parent`,`post_type`,`post_mime_type`,`comment_count`,`smeta`,`post_hits`,`post_like`,`istop`,`recommended`) values ";
        $insert_sql = "";
        foreach ($list as $i => $row) {
            $sttt = $pdoNew->query("select * from hw_posts WHERE old_id>0 and old_id=" . $row['id']." or post_title='".$row['name']."'");

            if ($sttt!=false && $sttt->fetch(PDO::FETCH_ASSOC)) {
                //如果文章已存在，则跳过
                EchoR('continue:');
                continue;
            }

            $st1 = $pdoOld->query("select url,big_url,thumb_url,small_url,width,height,url as alt from img WHERE article_id=" . $row['id']);
            $photos = $st1->fetchAll(PDO::FETCH_ASSOC);

            $smeta = array(
                'thumb' => $row['thumb'],
                'template' => 'article',
                'credits' => $row['credits'],
                'photo' => $photos
            );
            $post_fields = array(
                'old_id' => $row['id'],
                'user_id' => 2,
                'author' => $row['credits'],
                'post_keywords' => $row['tag'],
                'post_source' => "",
                'post_date' => date('Y-m-d H:i:s', strtotime($row['date'])),
                'post_content' => $row['content'],
                'post_title' => $row['name'],
                'post_excerpt' => meta_description($row['content']),
                'post_status' => $row['publish'],
                'comment_status' => 0,
                'post_modified' => date('Y-m-d H:i:s'),
                'post_content_filtered' => '',
                'post_parent' => 0,
                'post_type' => 1,
                'post_mime_type' => 1,
                'comment_count' => 0,
                'smeta' => json_encode($smeta),
                'post_hits' => $row['views'],
                'post_like' => 0,
                'istop' => 0,
                'recommended' => $row['recommend']
            );
            $str = "('" . implode("','", array_values($post_fields)) . "')";
            $res = $pdoNew->exec($_sql . $str);
            if ($res) {
                $post_id = $pdoNew->lastInsertId();

                /**
                 * 原来的author_id 不一定还存在，所以选用后更新方式，如有而更新
                 */
                update_posts_uid($row['author_id'], $post_id);


                $count_i++;
//                echo $count_i."\r\n";
                EchoR('已插入第', $count_i, '行');

                $c_ids = array_map(function ($id) {
                    return substr($id, 1, strlen($id) - 2);
                }, explode(',', $row['category_ids']));

                if (array_values($c_ids)) {
                    $ids_sql = implode(',', $c_ids);
                    $stt = $pdoNew->query("select * from hw_terms WHERE old_term_id in ($ids_sql)");
                    if ($stt != false) {
                        $categories = $stt->fetchAll(PDO::FETCH_ASSOC);
                        $c_new_ids = array();
                        $insert_term_relationship_sql = "insert into hw_term_relationships (object_id, term_id) VALUES ";
                        foreach ($categories as $ci => $cat) {
                            $cid = $cat['term_id'];
                            $c_new_ids[] = $cid;
                            $insert_term_relationship_sql .= ($ci > 0 ? ',' : '') . "($post_id,$cid)";
                        }
                        $pdoNew->exec($insert_term_relationship_sql);
                    }
                }
            }
        }
    }
}


function update_posts_uid($author_id, $post_id)
{
    $pdoNew = getPdoNew();
    $user_login = 'admin_' . $author_id;
    $sql = "select * from hw_users WHERE user_login='$user_login'";
    $stt = $pdoNew->query($sql);
    $user = $stt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {//如果不存在管理员则有可能是其他人上传的文章
        $sql = "select * from hw_users WHERE user_login='$author_id' AND user_login!='' AND user_login IS NOT NULL ";
        $stt = $pdoNew->query($sql);
        $user = $stt->fetch(PDO::FETCH_ASSOC);
    }

    if ($user) {
        $_sql = "update hw_posts set user_id=:user_id WHERE id=:id";
        $update_stmt = $pdoNew->prepare($_sql);
        $res = $update_stmt->execute(array(
            ':user_id' => $user['id'],
            ':id' => $post_id
        ));
    }
}

EchoR("完成");
exit;
/**
 * ======================================================================================
 * ======================================================================================
 * ======================================================================================
 */

