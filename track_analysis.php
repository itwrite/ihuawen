<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/1/24
 * Time: 0:00
 */
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');

class Arr
{
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  array $array
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    static public function get($array, $key, $default = null)
    {
        if (is_null($key)) return $array;

        if (isset($array[$key])) return $array[$key];

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return self::value($default);
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     * @param $array
     * @param $key
     * @param $value
     * @return array
     */
    static public function set(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = array();
            }

            $array =& $array[$key];
        }
        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * @param $value
     * @return mixed
     */
    static public function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}

class Server{
    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    static public function get($key='',$default=null)
    {
        if(empty($key))return $_SERVER;

        return Arr::get($_SERVER,$key,$default);
    }
}

class Cookie {

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    static public function get($key,$default = null){
        return Arr::get($_COOKIE,$key,$default);
    }

    /**
     * @param $key
     * @param $value
     * @param int $life
     * @return bool
     */
    static public function set($key, $value, $life = 0)
    {
        $life = $life ? time() + $life : 0;
        $secure = Server::get('SERVER_PORT') == '443' ? 1 : 0;
        return setcookie($key, $value, $life,null,null, $secure);
    }

    //delete cookie
    /**
     * @param $key
     */
    static public function remove($key)
    {
        if (is_array($key)) {
            foreach (array_values($key) as $k) {
                self::set($k, null, -360000);
            }
        } else if(is_string($key)){
            self::set($key, null, -360000);
        }
    }
}
/**
 * @return PDO
 */
function pdo(){
    static $_pdo;
    if(!$_pdo instanceof \PDO){
        $dsn = 'mysql:host=localhost;dbname=admin_aisimob_ihuawen;';
        $username = 'admin_aisimob';
        $password ='TestPw123!';
        $_pdo = new \PDO($dsn,$username, $password,array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
    }
    return $_pdo;
}

/**
 * @param int $type
 * @param bool $adv
 * @return mixed
 */
function ip($type = 0, $adv = false)
{
    $type      = $type ? 1 : 0;
    static $ip = null;
    if (null !== $ip) {
        return $ip[$type];
    }

    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim(current($arr));
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? [$ip, $long] : ['0.0.0.0', 0];
    return $ip[$type];
}

/**
 * @return mixed
 */
function uri(){
    $argv = Server::get('argv',array(''));
    return Server::get('REQUEST_URI',Server::get('HTTP_X_REWRITE_URL',$argv[0]));
}
$uri = uri();
$arr = parse_url($uri);
$query = $arr['query'];
parse_str($query,$params);

$PV_SOURCE = 'website';
if(isset($params['g'])&&($params['g'] =='MobileApi' || $params['g'] =='mobileApi')){
    $PV_SOURCE = 'API{app|pad}';
}
$time = Cookie::get('__track_time',0);

if(($time==0 ||time()-$time>2)&& strpos($uri,'user/public/avatar')===false&&strpos($uri,'images/survey/res/')===false){

    $ip_address = ip(0,1);
    $unique_id = md5($uri);
    $visit_date = date('Y-m-d');
    $sql_where = "where unique_id='{$unique_id}' and ip_address = '{$ip_address}' and visit_date = '{$visit_date}'";
    $st = pdo()->query("select * from hw_site_pv {$sql_where}");

    $sql_insert = "insert into hw_site_pv(`unique_id` ,`ip_address`,`request_uri`,`visit_date`,`visit_count`,`create_time`,`source`) VALUES (? ,?,?,?,?,?,?)";
    $insert_bok = false;
    if($st){
        $row = $st->fetch();
        if($row){
            $pv_id = $row['id'];
            $sql_where .=" and id='{$pv_id}'";
            pdo()->exec("update hw_site_pv set visit_count = visit_count+1 {$sql_where}");
        }else{
            $stt = pdo()->prepare($sql_insert);
            $stt->execute(array($unique_id,$ip_address,$uri,$visit_date,1,date('Y-m-d H:i:s'),$PV_SOURCE));
        }
    }else{
        $stt = pdo()->prepare($sql_insert);
        $stt->execute(array($unique_id,$ip_address,$uri,$visit_date,1,date('Y-m-d H:i:s'),$PV_SOURCE));
    }
    Cookie::set('__track_time',time());
}
