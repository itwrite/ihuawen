<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/9/29
 * Time: 13:09
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


$str = '<p style="text-indent: 2em;">据加拿大《环球新闻》报道，位于加拿大安大略的尼亚加拉学院表示，将于2018年秋季推出一个大麻制造研究生证书课程。<br _moz_dirty="true" style="box-sizing: border-box"/></p><p style="text-indent: 2em;">它将是全世界第一个提供大麻生产研究生文凭的课程。该大学表示，为期一年的课程将主要培养学生们未来能在合法生产大麻的机构合作，包括制作大麻、大麻纤维和大麻种子。据悉，这所大学已经得到了加拿大教育标准部门的批准。<br _moz_dirty="true" style="box-sizing: border-box"/></p><p style="text-indent: 2em;"><img src="/data/upload/ueditor/20170922/59c3e59399f93.jpg" style="box-sizing: border-box;border: 0px;vertical-align: middle;max-width: 90%"/><br _moz_dirty="" style="box-sizing: border-box"/><br _moz_dirty="true" style="box-sizing: border-box"/></p><p style="text-indent: 2em;">就像英国的研究生课程一样，学生们需要完成一个本科学位或文凭才能申请该课程。<br _moz_dirty="true" style="box-sizing: border-box"/></p><p style="text-indent: 2em;">该大学表示，该课程将向那些学习综合农业、农业科学、环境科学/资源研究、园艺或自然科学，或有接受相关教育或有相关经验的人开放。尼亚加拉学院还表示，该项目将在其尼亚加拉湖校区的课程中生产大麻，这符合所有法律法规。<br _moz_dirty="true" style="box-sizing: border-box"/></p><p style="text-indent: 2em;">加拿大政府承诺在2018年7月1日前将娱乐大麻合法化。<br _moz_dirty="true" style="box-sizing: border-box"/></p><p style="text-indent: 2em;"><img src="http://aisimob.ihuawen.com/data/upload/ueditor/20170922/59c3e5948a62b.jpg" style="box-sizing: border-box;border: 0px;vertical-align: middle;max-width: 90%"/><br _moz_dirty="true" style="box-sizing: border-box"/></p><p style="text-indent: 2em;">新法将允许18岁以上的成年人在公共场所拥有多达30克的干大麻或同等的大麻，与其他成年人分享30克干大麻，并从当地监管的零售商那里购买大麻或大麻油。<br _moz_dirty="true" style="box-sizing: border-box"/></p><p style="text-indent: 2em;">加拿大政府还表示，未来将允许生产其他的大麻产品，包括用大麻提取的食品。</p><p><br/></p>';

$pattern = '/<img\s+[^>]*src="http:\\/\\/aisimob\\.ihuawen\\.com\\/data\\/upload\\/[^"]*"[^>]*>/';

$pattern2 = '/<img\s+[^>]*>/';

/**
$testStr = 'esfw <img src="http://aisimob.ihuawen.com/data/upload/ueditor/20170922/59c3e5948a62b.jpg" style="box-sizing: border-box;border: 0px;vertical-align: middle;max-width: 90%"/> esfw <img src="http://aisimob.ihuawen.com/data/upload/ueditor/20170922/59c3e5948a62b.jpg" style="box-sizing: border-box;border: 0px;vertical-align: middle;max-width: 90%"/>';

$new_content = preg_replace_callback($pattern,function($matchs){
    print_r($matchs);
    return str_replace('aisimob.ihuawen.com','www.ihuawen.com',$matchs[0]);
},$testStr);
echo $new_content;
exit;
/**
preg_match_all($pattern,$str,$matchs1);
print_r($matchs1);
exit;
/**/


$pdo = _pdo();

$res = $pdo->query("SELECT id,post_content FROM hw_posts WHERE post_content LIKE '%src=\"http://aisimob.ihuawen.com/data/upload/%';");

$result = $res->fetchAll();

$post_ids = array();
$post_id = 0;
foreach ($result as $row) {

    //48985
    $post_id = $row['id'];
    EchoR($post_id);
    $post_ids[] = $post_id;

    $content = $row['post_content'];
    /**/
    $new_content = preg_replace_callback($pattern,function($matchs){
        print_r($matchs);
        return str_replace('aisimob.ihuawen.com','www.ihuawen.com',$matchs[0]);
    },$content);

    $pdo->exec("update hw_posts set post_content='{$new_content}' WHERE id=".$post_id);
    /**/
//    break;
}

print_r($post_ids);
exit;

//$st = $pdo->query("select id,post_content from hw_posts WHERE id=".$post_id);
//$post = $st->fetch(PDO::FETCH_ASSOC);
//
//preg_match_all($pattern2,$post['post_content'],$matchs1);
//print_r($matchs1);
//
exit;