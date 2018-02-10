<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/8/1
 * Time: 15:30
 */

namespace Portal\Controller;


use Common\Controller\AdminbaseController;

class AdminPostTagController extends AdminbaseController{

    protected $tag_m;

    function _initialize() {
        parent::_initialize();
        $this->tag_m = M("PostsTag");
    }

    public function index(){

        $list = $this->tag_m->select();
        $this->assign('list',$list);
        $this->display();
    }

    public function add(){
        $this->display();
    }

    // 文章分类添加提交
    public function add_post(){
        if (IS_POST) {
            if ($this->tag_m->create()!==false) {
                if ($this->tag_m->add()!==false) {
                    $this->success("添加成功！",U("AdminPostTag/index"));
                } else {
                    $this->error("添加失败！");
                }
            } else {
                $this->error($this->tag_m->getError());
            }
        }
    }

    public function edit(){
        $id = I('get.id',0);

        $data = $this->tag_m->find($id);
        if(!$data){
            $this->error("目标不存在！",U('AdminPostTag/index'));
        }

        $this->assign('tag',$data);

        $this->display();
    }

    public function edit_post(){
        if (IS_POST) {
            $data = array();
            $data['name'] = I('post.name','');
            $data['status'] = I('post.status',0,'intval')==1?1:0;

            if ($this->tag_m->create($data)!==false) {
                if ($this->tag_m->where(array('id'=>I('post.tag_id',0,'intval')))->save($data)!==false) {
                    $this->success("修改成功！");
                } else {
                    $this->error("修改失败！");
                }
            } else {
                $this->error($this->tag_m->getError());
            }
        }
    }

    public function delete() {
        $id = I("get.id",0,'intval');

        if ($this->tag_m->delete($id)!==false) {
            M('PostsTagRelationships')->where(array('tag_id'=>$id))->delete();
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

}