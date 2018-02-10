<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/6/16
 * Time: 13:38
 */

namespace MobileApi\Controller;


use Common\Controller\ApibaseController;

class ArticleController extends ApibaseController{

    function detail(){
        $article_id=I('get.id',0,'intval');

        $posts_model=M("Posts");
        $user_favorites_model=M("UserFavorites");

        $token = I('request.token','');
        $uid = 0;
        if(!empty($token)){
            $user = get_user_with_token($token);
            $uid = intval($user['id']);
        }


        $article=$posts_model
            ->alias("a")
            ->field('a.*,c.user_login,c.user_nicename,b.term_id,b.object_id,if(f.object_id>0,1,0) as is_collected,t.name')
            ->join("__TERM_RELATIONSHIPS__ b ON a.id = b.object_id")
            ->join("__TERMS__ t ON b.term_id = t.term_id")
            ->join("__USERS__ c ON a.user_id = c.id")
            ->join($user_favorites_model->getTableName()." as f ON (f.object_id = a.id and f.table = 'posts' and f.uid=$uid)",'left')
            ->where(array('a.id'=>$article_id))
            ->find();
        if(empty($article)){


            $this->ajaxErrorReturn("文章不存在！");

            return;
        }


        $data = array();

        $pattern = '/<img\s+[^>]*>/';

        $article['post_content'] = preg_replace_callback($pattern,function($matchs){
            $pattern_style = '/style="[^"]*"/';
            $pattern_width = '/width="[^"]*"/';
            $pattern_height = '/height="[^"]*"/';

            $img_html = preg_replace($pattern_width,'',$matchs[0]);
            $img_html = preg_replace($pattern_height,'',$img_html);
            return preg_replace_callback($pattern_style,function($styles){
                $style_str = preg_replace('/width:[^;"]*;?/','',$styles[0]);
                $style_str = preg_replace('/height:[^;"]*;?/','',$style_str);
                return $style_str;
//                return substr($styles[0],0,strlen($styles[0])-1).';width:auto !important;height:auto !important;"';
            },$img_html);
        },$article['post_content']);

//        $terms_model= M("Terms");
//        $term=$terms_model->where(array('term_id'=>$term_id))->find();

        $posts_model->where(array('id'=>$article_id))->setInc('post_hits');

        $data['data']['article'] = old2new_post($article);
        $data['data']['article']['is_collected'] = intval($article['is_collected']);

        $author_id = (int)$article['author_id'];
        $author = M('PostsAuthor')->field("id,name,content as description,avatar")->where(array('id'=>$author_id))->find();

        $data['data']['author'] = null;
        if($author){
            $author['avatar'] = sp_get_asset_upload_path($author['avatar']);
            $data['data']['author'] = $author;
        }

        $this->ajaxReturn($data);
    }

    function add_favorite(){
        $this->check_login();
        if(IS_POST){
            $table='posts';
            $object_id=I('post.post_id');

            $post['table']=$table;
            $post['object_id']=$object_id;

            $posts_m = M('Posts');
            $article  = $posts_m->where(array($posts_m->getPk()=>$object_id))->find();
            if($article){
                $post['title'] = $article['post_title'];
                $post['url'] = leuu('portal/article/index',array('id'=>$article['id']));
                $post['uid']=$this->user->id;
                $user_favorites_model=M("UserFavorites");
                $find_favorite=$user_favorites_model->where(array('table'=>$table,'object_id'=>$object_id,'uid'=>$post['uid']))->find();
                if($find_favorite){
                    $this->error("亲，您已收藏过啦！");
                }else {
                    $post['createtime']=time();
                    $result=$user_favorites_model->add($post);
                    if($result){
                        $this->ajaxReturn(array('info'=>"收藏成功！"));
                    }else {
                        $this->error("收藏失败！");
                    }
                }
            }else{
                $this->error("收藏失败！");
            }

        }else{
            $this->error("非法操作！");
        }
    }

    function remove_favorite(){
        $this->check_login();
        if(IS_POST){
            $id=I("post.post_id",0,"intval");
            $uid=$this->user->id;
            $user_favorites_model=M("UserFavorites");
            $result=$user_favorites_model->where(array('object_id'=>$id,'uid'=>$uid,'table'=>'posts'))->delete();
//            $sql = $user_favorites_model->getLastSql();
            if($result){
//                $this->success("已取消收藏！");
                $this->ajaxReturn(array('info'=>"已取消收藏！"));
            }else {
                $this->error("取消失败！");
            }
        }else{
            $this->error("非法操作！");
        }

    }
}