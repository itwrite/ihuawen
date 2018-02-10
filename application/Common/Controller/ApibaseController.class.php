<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/6/9
 * Time: 17:58
 */

namespace Common\Controller;


use Think\Controller;
use Think\Log;

class ApibaseController extends Controller
{

    function _initialize() {
        $this->assign("waitSecond", 3);
        $time=time();
        $this->assign("js_debug",APP_DEBUG?"?v=$time":"");
    }

    public function __construct() {
        Log::init();

        $theme = 'simplebootx';

        C('TMPL_DETECT_THEME',$theme);
//        $current_tmpl_path=$tmpl_path.$theme."/";
        C('VIEW_PATH',APP_PATH. MODULE_NAME."/" . C('DEFAULT_V_LAYER') . '/');
//        print_r(C('VIEW_PATH'));exit;
        C('TMPL_PARSE_STRING.__TMPL__',__ROOT__ . '/' . substr(SPAPP,2) . MODULE_NAME ."/" . C('DEFAULT_V_LAYER') . '/'.$theme."/");

        defined('TMPL_PATH') or define("TMPL_PATH", C("SP_TMPL_PATH"));
        //echo TMPL_PATH;exit;
        parent::__construct();
        $this->theme($theme);
    }
    protected function ajaxReturn($data,$type='json',$json_option=0){
        $data = $this->array_extend(array(
            'status'=>1,
            'info'=>'',
            'data'=>null,
            'code'=>0
        ),$data);
        parent::ajaxReturn($data,$type,$json_option);
        exit;
    }

    protected function ajaxErrorReturn($message,$type='json',$json_option=0){
        parent::ajaxReturn(array(
            'status'=>0,
            'info'=>$message,
            'data'=>null,
            'code'=>-1
        ),$type,$json_option);
    }

    protected function error($message,$type='json',$json_option=0){
        parent::ajaxReturn(array(
            'status'=>0,
            'info'=>$message,
            'data'=>null,
            'code'=>-1
        ),$type,$json_option);
    }

    protected function success($message,$type='json',$json_option=0){
        parent::ajaxReturn(array(
            'status'=>1,
            'info'=>$message,
            'data'=>null,
            'code'=>0
        ),$type,$json_option);
    }


    /**
     * @param array $array1
     * @param array $array2
     * @param null $_
     * @return array
     */
    protected function array_extend(array $array1, array $array2 = null, $_ = null)
    {
        $args = func_get_args();
        if (count($args) == 2) {
            foreach ($array2 as $key => $value) {
                if (isset($array1[$key]) && !is_numeric($key) && is_array($array1[$key]) && is_array($value)) {
                    $array1[$key] = $this->array_extend($array1[$key], $value);
                } elseif ((is_numeric($key) && $array1[$key] != $value)) {
                    $array1[] = $value;
                } elseif ((isset($array1[$key]) && gettype($value) == gettype($array1[$key])) || !isset($array1[$key])) {
                    $array1[$key] = $value;
                }
            }
        } else {
            array_shift($args);
            foreach ($args as $arr) {
                $array1 = $this->array_extend($array1, $arr);
            }
        }
        return $array1;
    }

    function check_login(){
        $redirect=I('get.redirect','');
        if(empty($redirect)){
            $redirect=$_SERVER['HTTP_REFERER'];
        }else{
            $redirect=base64_decode($redirect);
        }
        session('login_http_referer',$redirect);

        $token=I('request.token','');

        $datetime = date('Y-m-d H:i:s');
        $content = "[$datetime]$token";


        $user = get_user_with_token($token);
//       $sql =  M('Token')->getLastSql();
//        $this->ajaxReturn(array('info'=>"请登录！:".$token,'code'=>-1,'token'=>$_REQUEST));
//        exit;
        if($user==null){
            $this->ajaxReturn(array('info'=>"请登录！",'code'=>-1));
            exit;
        }else{
            $this->token = $token;
            $this->user=(object)$user;
        }
    }

    /**
     *
     * @param number $totalSize 总数
     * @param number $pageSize  总页数
     * @param number $currentPage 当前页
     * @param number $listRows 每页显示条数
     * @param string $pageParam 分页参数
     * @param string $pageLink 分页链接
     * @param string $static 是否为静态链接
     */
    protected function page($totalSize = 1, $pageSize = 0, $currentPage = 1, $listRows = 6, $pageParam = '', $pageLink = '', $static = FALSE) {
        if ($pageSize == 0) {
            $pageSize = C("PAGE_LISTROWS");
        }
        if (empty($pageParam)) {
            $pageParam = C("VAR_PAGE");
        }

        $page = new \Page($totalSize, $pageSize, $currentPage, $listRows, $pageParam, $pageLink, $static);

        $page->setLinkWraper("li");
        if(sp_is_mobile()){
            $page->SetPager('default', '{prev}&nbsp;{list}&nbsp;{next}', array("listlong" => "4", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => ""));
        }else{
            $page->SetPager('default', '{first}{prev}&nbsp;{liststart}{list}{listend}&nbsp;{next}{last}', array("listlong" => "4", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => ""));
        }

        return $page;
    }
}