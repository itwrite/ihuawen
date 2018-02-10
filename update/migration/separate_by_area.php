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

print_r($pdo2->getAttribute(PDO::ATTR_SERVER_INFO)) ;
echo "\r\n";


$st = $pdo2->query("select * from hw_posts_tag");
$tag_list = $st->fetchAll(PDO::FETCH_ASSOC);


$area_arr = array(
    '中国'=>"中国，深圳，广东，合肥，北京，上海",
    '英国'=>"英国，伦敦，伯明翰，中英，卡迪夫，肯特，苏格兰，威尔士，中英，斯劳，兰卡斯特，爱丁堡，牛津，哈罗盖特，利物浦，曼彻斯特",
    '爱尔兰'=>"爱尔兰，都柏林，高威，考克，利默里克",
    '法国'=>"法国，巴黎，尼斯",
    '瑞士'=>"瑞士，苏黎世，楚格，达沃斯，施维茨，日内瓦，卢加诺，圣莫里茨，韦尔比耶，卢塞恩，拉格斯，蒙特勒，洛伊克巴德",
    '意大利'=>"意大利，威尼斯，罗马，佛罗伦萨，米兰，西西里岛，庞贝，那不勒斯",
    '斯洛文尼亚'=>"斯洛文尼亚，卢布尔雅那，普乐莫斯卡，阿伊多夫什契纳，空斯卡",
    '美国'=>"佛罗里达，美国，纽约，华盛顿，旧金山，洛杉矶",
    '日本'=>"日本，冲绳，东京，京都",
    '德国'=>"德国，慕尼黑，法兰克福，柏林，巴登巴登，杜塞尔多夫",
    '比利时'=>"比利时，布鲁塞尔，斯帕",
    '马耳他'=>"马耳他",
    '匈牙利'=>"匈牙利，布达佩斯，黑维兹",
    '荷兰'=>"荷兰，阿姆斯特丹",
    '冰岛'=>"冰岛，雷克雅未克",
    '瑞典'=>"瑞典，斯德哥尔摩",
    '埃及'=>"埃及，开罗",
);

$pdo2->exec("TRUNCATE TABLE hw_posts_tag_relationships;");

$pdo2->exec("TRUNCATE TABLE hw_posts_images;");

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
//            print_r($row);exit;

            /**
             * 这条post 是否已找到对应关系?
             * 如果过一遍 tags list,如果没有找到对应关系，文章则放入“其它地区”
             */
            $relative_area_count = 0;
            foreach ($tag_list as $tag) {
                //如果需要对应关系
                if(isset($area_arr[$tag['name']])){
                    $tag_id = $tag['id'];//标签的id
                    $tag_str = $area_arr[$tag['name']];
                    $arr = explode('，',$tag_str);

                    $search_count = 0;
                    foreach($arr as $str){
                        if(strpos($row['post_content'],$str)){
                            $search_count++;
                        }
                    }

                    if($search_count>0){
                        echo $relative_area_count."\r\n";
                        //
                        $insert_sql = "insert into hw_posts_tag_relationships (post_id,tag_id) VALUES ({$post_id},{$tag_id}) ";
                        $res = $pdo2->exec($insert_sql);
                        if($res){
                            $relative_area_count++;
                        }
                    }
                }
            }

            if($relative_area_count==0){
                //已知“其它地区”的ID是18
                $insert_sql = "insert into hw_posts_tag_relationships (post_id,tag_id) VALUES ({$post_id},18) ";
                $res = $pdo2->exec($insert_sql);
            }

            $smeta = json_decode($row['smeta'],true);

            if(isset($smeta['photo'])){
                foreach($smeta['photo'] as $photo){
                    if(isset($photo['height'])){
                        $arr = array();
                        $arr['url'] = $photo['url'];
                        $arr['file_name'] = $photo['url'];
                        $arr['big_url'] =  $photo['big_url'];
                        $arr['thumb_url'] =  $photo['thumb_url'];
                        $arr['small_url'] =  $photo['small_url'];

                        $arr['width'] = $photo['width'];
                        $arr['height'] = $photo['height'];

                        $arr['post_id'] = (int)$post_id;
                        $arr['user_id'] = 1;

                        $fields_str = implode(',',array_keys($arr));
                        $values_str = "'".implode("','",array_values($arr))."'";

                        $insert_str = "insert into hw_posts_images ({$fields_str}) values({$values_str});";
                        $pdo2->exec($insert_str);
                    }
                }
            }
        }
    }
}






//foreach ($area_arr as $key=>$area_str) {
//
//    $st = $pdo2->query("select * from hw_posts_tag posts_tag WHERE posts_tag.name = '{$key}'");
//    if($st!=false){
//        $tag = $st->fetch(PDO::FETCH_ASSOC);
//
//        $keywords = str_replace('，','%',$area_str);
//        $_sql = "select * from hw_posts posts WHERE posts.post_content LIKE '%{$keywords}%'";
//        $sst = $pdo2->query($_sql);
//        $posts = $sst->fetchAll(PDO::FETCH_ASSOC);
//
//
//
//        if($posts){
//            $insert_sql = "insert into hw_posts_tag_relationships (post_id,tag_id) VALUES ";
//            foreach ($posts as $i => $row) {
//                $object_id = $row['id'];
//                $tag_id = $tag['id'];
//                if($i==0){
//                    $insert_sql.= "({$object_id},{$tag_id})";
//                }else{
//                    $insert_sql.= ",({$object_id},{$tag_id})";
//                }
//            }
//
//            echo $insert_sql."\r\n";
//
//            $effect_rows = $pdo2->exec($insert_sql);
//            echo $effect_rows."\r\n\r\n";
//        }
//
//    }
//}

echo "done!!";
exit;