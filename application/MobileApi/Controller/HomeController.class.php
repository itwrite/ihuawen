<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/6/12
 * Time: 18:08
 */

namespace MobileApi\Controller;


use Common\Controller\ApibaseController;

class HomeController extends ApibaseController{
    function index(){

        $data = array();

        $p = I('get.p',1,'intval');

        /**
         * 观察,商业理财,教育,科技创新,文化艺术,时尚,专题,新闻,华闻百科
         */
//        $result = $this->_lists(9,"",'p.id');
        $result = Api_posts('order:posts.post_modified desc;group:posts.id',array('posts.istop'=>0,'terms.taxonomy'=>'article'),20);
        unset($result['page']);
        $tmp_arr = array();
        foreach($result['posts'] as $i=>$post){
            $row = old2new_post($post);
            if(empty($row)){
                unset($result['posts'][$i]);
            }else{
                unset($row['post_content']);
                $tmp_arr[] = $row;
            }
        }
        $result['posts'] = $tmp_arr;

        /**
         * 推荐 的文章
         */
        $commended_lists = Api_sql_posts_paged("where:posts.recommended=1;order:post_date DESC;group:posts.id",1);
        $result['commended_list'] = array();
        if($commended_lists['count']>0){
            $result['commended_list'] = $commended_lists['posts'];
        }else{
            $len = $result['count'];
            $i = rand(0,$len-1);
            if(isset($result['posts'][$i])){
                $result['commended_list'][] = $result['posts'][$i];
            }
        }
        $data['data']['articles'] = $result;


        /**
         * 轮播
         */
        $list = Api_sql_posts("field:posts.id as post_id,post_title,post_excerpt,object_id,smeta,terms.term_id;where:posts.istop=1 and terms.taxonomy!='magazine';order:posts.post_modified desc;limit:5;group:posts.id");

        $last_news = array();
        foreach($list as &$row){
            $smeta=json_decode($row['smeta'],true);

            $new_slide = array();
            $new_slide['cid'] = (int)$row['term_id'];
            $new_slide['slide_id'] = (int)$row['post_id'];
            $new_slide['post_id'] = $new_slide['slide_id'];
            $new_slide['slide_name'] = $row['post_title'];
            $new_slide['slide_pic'] = "";
            if(empty($smeta['thumb'])){
                $new_slide['slide_pic'] = __TMPL__.'Public/assets/images/default_tupian1.png';
            }else{
                $new_slide['slide_pic'] = sp_get_asset_upload_path($smeta['thumb']);
            }
            $new_slide['slide_des'] = $row['post_title'];
            $new_slide['slide_content'] = $row['post_excerpt'];


            $last_news[] = $new_slide;
        }
        $data['data']['slides'] = $last_news;

        $this->ajaxReturn($data);
    }

    public function area_tags(){

        $result = array();

        $list = M('PostsTag')->select();
        foreach ($list as &$row) {
            $row['id'] = intval($row['id']);
        }


        $result['data']['areas'] = $list;
        $this->ajaxReturn($result);
    }

    /**
     * ==================================================================================
     * @param int $limit
     * @param string $keywords
     * @param string $group_by
     * @return mixed
     */
    private function _lists($limit=0,$keywords='',$group_by=''){
        $join = '__POSTS__ as p on tr.object_id = p.id';
        $join2= '__USERS__ as u on p.user_id = u.id';
        //$fields = "t.term_id,t.name,tr.object_id,p.id,p.post_title,p.post_excerpt,p.smeta,p.post_hits,p.post_date";
        $fields = "*";

        $result = array();

        $terms_m = M('Terms');
        $term_relationships_m= M("TermRelationships");

        $where_in = "";
        $and_where_in = "";
        if(!empty($keywords)){
            $arr = explode(',',$keywords);
            $str =implode("','",$arr);
            $where_in = "t.name in ('$str')";
            $and_where_in = " AND $where_in";
        }

        $sql = "SELECT count(tt.t_count) as tt_count FROM (SELECT COUNT(t.term_id) as t_count FROM hw_terms t JOIN hw_term_relationships as tr on tr.term_id=t.term_id  JOIN hw_posts as p on tr.object_id = p.id JOIN hw_users as u on p.user_id = u.id  WHERE 1 AND t.taxonomy!='magazine' $and_where_in GROUP BY t.term_id ORDER BY p.post_date desc) tt";
        $re = $terms_m->query($sql);

        $total = intval($re[0]['tt_count']);

        $p=I('request.p',0,'intval');
        $p_size = intval($limit);

        if(strpos(',',$limit)>-1){
            $temp = explode(',',$limit);
            $p = intval($temp[0]);
            $p_size=intval($temp[1]);
        }
        $p = $p<1?1:$p;
        $offset = ($p-1)*$p_size;
        $_limit = $offset.','.$p_size;

        $join_area_str = "";
        $area_tag_id = (int)(isset($_COOKIE['area_tag_id'])?$_COOKIE['area_tag_id']:$_REQUEST['area_tag_id']);
        $is_magazine = (int)$_SESSION['is_magazine'];
        if($is_magazine==0 && $area_tag_id>0){
            $_sql = M("PostsTagRelationships")->field('DISTINCT post_id')->where(array('tag_id'=>$area_tag_id,'post_id'=>array('neq',0)))->buildSql();
            $content['_sql'] = $_sql;
            $where['p.id'] = array('exp',"in($_sql)");
            //$join_area_str = "__POSTS_TAG_RELATIONSHIPS__ posts_tag_relationships on(posts_tag_relationships.post_id = p.id and posts_tag_relationships.tag_id={$area_tag_id})";
        }


        $result['count'] = intval($total);
        $result['total_pages'] = ceil($total/$p_size);

        $ret = $terms_m->alias('t')
            ->field($fields)
            ->join($term_relationships_m->getTableName()." as tr on tr.term_id=t.term_id")
            ->join($join)
            ->join($join2)
            ->join($join_area_str)
            ->where("t.taxonomy!='magazine'")
            ->order('p.post_date desc');
        if(!empty($group_by)){
            $ret->group($group_by);
        }
        if(!empty($and_where_in)){
            $ret->where("$where_in");
        }
        if($limit){
            $ret->limit($_limit);
        }
        $result['posts'] = $ret->select();
//		echo $ret->getLastSql();exit;
        return $result;
    }
}