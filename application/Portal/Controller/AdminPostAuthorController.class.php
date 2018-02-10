<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/8/1
 * Time: 16:28
 */

namespace Portal\Controller;


use Common\Controller\AdminbaseController;

class AdminPostAuthorController extends AdminbaseController {

    protected $author_m;

    function _initialize() {
        parent::_initialize();
        $this->author_m = M("PostsAuthor");
    }

    function index(){
        $list = $this->author_m->select();
        $this->assign('list',$list);
        $this->display();
    }

    function add(){
        $this->display();
    }

    function add_post(){
        if (IS_POST) {
            if ($this->author_m->create()!==false) {
                if ($this->author_m->add()!==false) {
                    $this->success("添加成功！",U("AdminPostAuthor/index"));
                } else {
                    $this->error("添加失败！");
                }
            } else {
                $this->error($this->author_m->getError());
            }
        }
    }

    function edit(){
        $id = I('get.id',0);

        $data = $this->author_m->find($id);
        if(!$data){
            $this->error("目标不存在！",U('AdminPostAuthor/index'));
        }

        $this->assign('data',$data);

        $this->display();
    }

    function edit_post(){
        if (IS_POST) {
            $data = array();
            $data['name'] = I('post.name','');
            $data['status'] = I('post.status',0,'intval')==1?1:0;

            $data['avatar'] = I('post.avatar','');
            $data['content'] = I('post.content','');

            if ($this->author_m->create($data)!==false) {
                if ($this->author_m->where(array('id'=>I('post.author_id',0,'intval')))->save($data)!==false) {
                    $this->success("修改成功！");
                } else {
                    $this->error("修改失败！");
                }
            } else {
                $this->error($this->author_m->getError());
            }
        }
    }

    public function delete() {
        $id = I("get.id",0,'intval');

        if ($this->author_m->delete($id)!==false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }
}