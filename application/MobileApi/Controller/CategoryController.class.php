<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/6/20
 * Time: 12:02
 */

namespace MobileApi\Controller;


use Common\Controller\ApibaseController;
use Portal\Service\ApiService;

class CategoryController extends ApibaseController {

    function index(){
        //$terms = Api_get_terms('');

        $terms = ApiService::terms("",array('status'=>array('NEQ','-1'),'taxonomy'=>'article'));

        $categories = array();

        foreach ($terms as $row) {
            $categories[]=array('id'=>intval($row['term_id']),'name'=>$row['name']);
        }


        $this->ajaxReturn(array('data'=>array('categories'=>$categories)));
    }
}