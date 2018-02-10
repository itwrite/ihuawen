<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/6/12
 * Time: 15:22
 */
define('PV_SOURCE','API{app|pad}');
include_once('track_analysis.php');

$uri = get_uri();
$arr = parse_url($uri);

$path = $arr['path'];
if (preg_match('/^\//', $path)) {
    $path = substr($path, 1);
}
$arr = explode('/',$path);

$_GET['g']=ucfirst(isset($arr[1])?$arr[1]:'portal');
$_GET['m']=ucfirst(isset($arr[2])?$arr[2]:'index');
$_GET['a']=ucfirst($arr[0])."_".(isset($arr[3])?$arr[3]:'index');


if (ini_get('magic_quotes_gpc')) {
    function stripslashesRecursive(array $array){
        foreach ($array as $k => $v) {
            if (is_string($v)){
                $array[$k] = stripslashes($v);
            } else if (is_array($v)){
                $array[$k] = stripslashesRecursive($v);
            }
        }
        return $array;
    }
    $_GET = stripslashesRecursive($_GET);
    $_POST = stripslashesRecursive($_POST);
}

//开启调试模式
define("APP_DEBUG", true);
//网站当前路径
define('SITE_PATH', dirname(__FILE__)."/");
//项目路径，不可更改
define('APP_PATH', SITE_PATH . 'application/');
//项目相对路径，不可更改
define('SPAPP_PATH',   SITE_PATH.'simplewind/');
//
define('SPAPP',   './application/');
//项目资源目录，不可更改
define('SPSTATIC',   SITE_PATH.'statics/');
//定义缓存存放路径
define("RUNTIME_PATH", SITE_PATH . "data/runtime/");
//静态缓存目录
define("HTML_PATH", SITE_PATH . "data/runtime/Html/");
//版本号
define("THINKCMF_VERSION", 'X2.2.3');

define("THINKCMF_CORE_TAGLIBS", 'cx,Common\Lib\Taglib\TagLibSpadmin,Common\Lib\Taglib\TagLibHome');


//载入框架核心文件
require SPAPP_PATH.'Core/ThinkPHP.php';

/**
 * =============================================================================================================
 * =============================================================================================================
 * =============================================================================================================
 * =============================================================================================================
 */
/**
 * @param $path
 * @param string $sep
 * @return array
 */
function path_to_params($path, $sep = '/')
{
    $result = array();
    if (preg_match('/^\//', $path)) {
        $path = substr($path, 1);
    }
    $info = explode($sep, $path);
    if (count($info) < 2) {
        return $result;
    }
    for ($i = 0; $i < count($info); $i++) {
        $tmp = $info[++$i];
        if ($tmp == '') {
            continue;
        }
        $result[$info[$i - 1]] = $tmp;
    }
    return $result;
}

/**
 * @return string
 */
function get_uri()
{
    $uri = '';
    foreach (array('REQUEST_URI', 'HTTP_X_REWRITE_URL', 'argv') as $var) {
        if (isset($_SERVER[$var])) {
            $uri = $_SERVER[$var];
            if ($var == 'argv') {
                $uri = $uri[0];
            }
            break;
        }
    }
    return $uri;
}
