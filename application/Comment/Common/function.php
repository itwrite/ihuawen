<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/8/9
 * Time: 14:28
 */


function cc_check_user_action($userid,$object="",$count_limit=1,$ip_limit=false,$expire=0){
    $common_action_log_model=M("CommonActionLog");
    $action=MODULE_NAME."-".CONTROLLER_NAME."-".ACTION_NAME;

    $ip=get_client_ip(0,true);//修复ip获取

    $where=array("user"=>$userid,"action"=>$action,"object"=>$object);
    if($ip_limit){
        $where['ip']=$ip;
    }

    $find_log=$common_action_log_model->where($where)->find();

    $time=time();
    if($find_log){

        if($find_log['count']>=$count_limit){
            return false;
        }

        if($expire>0 && ($time-$find_log['last_time'])<$expire){
            return false;
        }

        return $common_action_log_model->where($where)->save(array("count"=>array("exp","count+1"),"last_time"=>$time,"ip"=>$ip));
    }

    return $common_action_log_model->add(array("user"=>$userid,"action"=>$action,"object"=>$object,"count"=>array("exp","count+1"),"last_time"=>$time,"ip"=>$ip));
}