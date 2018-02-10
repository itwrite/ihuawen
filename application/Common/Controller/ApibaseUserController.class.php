<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/6/15
 * Time: 14:39
 */

namespace Common\Controller;


class ApibaseUserController extends ApibaseController{

    protected $token = '';
    protected $user = array();
    function __construct(){

        $this->check_login();
        parent::__construct();
    }
}