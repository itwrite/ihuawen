<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/6/20
 * Time: 10:20
 */

/**
 * @return \PDO
 */
function &getPdoOld(){
    static $___pdo1;
    if(!$___pdo1 instanceof \PDO){
//        $dsn = "mysql:host=120.24.19.215;dbname=ihuawen_old";
//        $username = "mysql-dev";
//        $password = "mysqldev";

        $dsn = "mysql:host=localhost;dbname=ihuawen_old";
        $username = "root";
        $password = "123456";
        $___pdo1  = new \PDO($dsn,$username, $password,array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
    }
    return $___pdo1;
}

/**
 * @return \PDO
 */
function &getPdoNew(){
    static $___pdo2;
    if(!$___pdo2 instanceof \PDO){
//        $dsn = "mysql:host=107.191.48.247;dbname=ihuawen";
//        $username = "devuser";
//        $password = "devuser1234";

        $dsn = "mysql:host=localhost;dbname=ihuawen";
        $username = "root";
        $password = "123456";

//        $dsn = "mysql:host=localhost;dbname=ihuawen";
//        $username = "devuser";
//        $password = "devuser1234";
        $___pdo2  = new \PDO($dsn,$username, $password,array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
    }
    return $___pdo2;
}

/**
 * @param $array
 * @param string $indent
 * @return string
 */
function array_eval($array, $indent = "\t")
{
    if (!is_array($array)) {
        return "'" . $array . "'";
    }
    $evaluate = "array(\n\r";
    $comma = $indent;
    if (is_array($array)) {
        foreach ($array as $key => $val) {
            $key = is_string($key) ? '\'' . $key . '\'' : $key;
            $val = !is_array($val) && !preg_match("/^\-?[0-9]\d*$/", $val) ? '\'' . addcslashes($val, '\'\\') . '\'' : $val;
            $evaluate .= $comma . $key . "=>";
            if (is_array($val)) {
                $evaluate .= array_eval($val, $indent . "\t");
            } else {
                $evaluate .= $val;
            }
            $comma = ",\n\r" . $indent;
        }
    }
    $evaluate .= "\n\r$indent)";
    return $evaluate;
}


/**
 * @param $str
 * @param string $charset
 * @return int
 */
if(!function_exists('_html')){
    //
    function _html($string)
    {
        $search_arr = array("/(javascript|jscript|js|vbscript|vbs|about):/i", "/on(mouse|exit|error|click|dblclick|key|load|unload|change|move|submit|reset|cut|copy|select|start|stop)/i", "/<script([^>]*)>/i", "/<iframe([^>]*)>/i", "/<iframe([^>]*)>/i", "/<link([^>]*)>/i", "/@import/i");
        $replace_arr = array("\\1\n:", "on\n\\1", "&lt;script\\1&gt;", "&lt;iframe\\1&gt;", "&lt;frame\\1&gt;", "&lt;link\\1&gt;", "@\nimport");
        return preg_replace($search_arr, $replace_arr, $string);
    }
}

if(!function_exists('strlen_ex')){
    function strlen_ex($str,$charset='utf-8')
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, $charset);
        } elseif (function_exists('iconv_strlen')) {
            return iconv_strlen($str, $charset);
        } elseif ($charset == 'utf-8') {
            preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $str, $ar);
            return count($ar[0]);
        } else {
            $len = 0;
            $strlen = strlen($str);
            for ($i = 0; $i < $strlen; $i++) {
                $len++;
                if (ord(substr($str, $i, 1)) > 129) {
                    $i++;
                }
            }
            return $len;
        }
    }
}

/**
 * @param $text
 * @param int $start
 * @param int $limit
 * @param string $charset
 * @return array
 */
function substr_ex($text, $start = 0, $limit = 12,$charset="utf-8")
{
    $tmpstr="";
    if (function_exists('mb_substr')) {
        $text = mb_substr($text, $start, $limit, $charset);
        $more = (mb_strlen($text, $charset) > $limit) ? true : false;
        return array($text, $more);
    } elseif (function_exists('iconv_substr')) {
        $text = iconv_substr($text, $start, $limit, $charset);
        $more = (iconv_strlen($text) > $limit) ? true : false;
        return array($text, $more);
    } elseif (strtolower($charset) == "utf-8") {
        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $text, $ar);
        if (count($ar[0]) > ($limit + abs($start))) {
            $more = true;
            $text = join("", array_slice($ar[0], $start, $limit)) . "...";
        } else {
            $more = false;
            $text = join("", array_slice($ar[0], $start, $limit));
        }
        return array($text, $more);
    } else {
        $fStart = $start * 2;
        $limit = $limit * 2;
        $strlen = strlen($text);
        for ($i = 0; $i < $strlen; $i++) {
            if ($i >= $fStart && $i < ($fStart + $limit)) {
                if (ord(substr($text, $i, 1)) > 129) {
                    $tmpstr .= substr($text, $i, 2);
                } else {
                    $tmpstr .= substr($text, $i, 1);
                }
            }
            if (ord(substr($text, $i, 1)) > 129) {
                $i++;
            }
        }
        $more = strlen($tmpstr) < $strlen;
        return array($tmpstr, $more);
    }
}


/**
 * @param $text
 * @param int $limit
 * @param string $ext
 * @return string
 */
if(!function_exists('substr_text')){
    function substr_text($text, $limit = 12, $ext = '...')
    {
        if ($limit) {
            $val = substr_ex($text, 0, $limit);
            return $val[1] ? $val[0] . $ext : $val[0];
        } else {
            return $text;
        }
    }
}

/**
 * @param $desc
 * @param int $limit
 * @param string $ext
 * @return string
 */
function meta_description($desc,$limit=140, $ext = '...'){
    return trim(substr_text(preg_replace(array('/"/', '/<br[^>]*>/', "/\\r\\n/", "/\\n/","/(<[^>]*>)/is","/(<\/[^>]*>)/is"), array('\"', '', '', '',' ',''), _html($desc)), $limit, $ext));
}

/**
 * @see 生成加密密码
 * @param $pw
 * @param string $authcode
 * @return string
 */
function sp_password($pw, $authcode='xlJ0uAq0fiqxMDr9tG'){
    if(empty($authcode)){
        $authcode=C("AUTHCODE");
    }
    $result="###".md5(md5($authcode.$pw));
    return $result;
}

function EchoR(){
    print_r(implode(' ',func_get_args())."\r\n");
}