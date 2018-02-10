<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MainController extends AdminbaseController {
	
    public function index(){


    	$mysql= M()->query("select VERSION() as version");
    	$mysql=$mysql[0]['version'];
    	$mysql=empty($mysql)?L('UNKNOWN'):$mysql;
    	
    	//server infomaions
    	$info = array(
    			L('OPERATING_SYSTEM') => PHP_OS,
    			L('OPERATING_ENVIRONMENT') => $_SERVER["SERVER_SOFTWARE"],
    	        L('PHP_VERSION') => PHP_VERSION,
    			L('PHP_RUN_MODE') => php_sapi_name(),
				L('PHP_VERSION') => phpversion(),
    			L('MYSQL_VERSION') =>$mysql,
    			L('PROGRAM_VERSION') => THINKCMF_VERSION,// . "&nbsp;&nbsp;&nbsp; [<a href='http://www.thinkcmf.com' target='_blank'>ThinkCMF</a>]",
    			L('UPLOAD_MAX_FILESIZE') => ini_get('upload_max_filesize'),
    			L('MAX_EXECUTION_TIME') => ini_get('max_execution_time') . "s",
    			L('DISK_FREE_SPACE') => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
    	);
    	$this->assign('server_info', $info);

		date_default_timezone_set('UTC');

		$today = date('Y-m-d');
		$lst = M()->query("SELECT COUNT(DISTINCT ip_address) as __count__  FROM hw_site_pv WHERE visit_date='{$today}' AND source='website'");
		$this->assign('pv_count',$lst[0]['__count__']);
//		$lst = M()->query("SELECT COUNT(DISTINCT ip_address) as __count__  FROM hw_site_pv WHERE visit_date='{$today}' AND source<>'website'");
//		$this->assign('api_pv_count',$lst[0]['__count__']);

		$yesterday = date('Y-m-d',strtotime('-1 day'));
		$lst2 = M()->query("SELECT COUNT(DISTINCT ip_address) as __count__ FROM hw_site_pv WHERE visit_date='{$yesterday}' AND source='website'");
		$this->assign('yesterday_pv_count',$lst2[0]['__count__']);
//		$lst2 = M()->query("SELECT COUNT(DISTINCT ip_address) as __count__ FROM hw_site_pv WHERE visit_date='{$yesterday}' AND source<>'website'");
//		$this->assign('api_yesterday_pv_count',$lst2[0]['__count__']);



    	$this->display();
    }
}