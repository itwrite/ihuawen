<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/8/4
 * Time: 17:42
 */

function sp_get_terms($tag,$where=array()){
    return \Portal\Service\ApiService::terms($tag,$where);
}
