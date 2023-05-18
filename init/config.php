<?php
/***************** server current path ********************/
define('PRIVATE_DIR', dirname(__DIR__).'/');
/***************** ini setting ********************/
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL);
ini_set( 'error_log', PRIVATE_DIR . 'php.log');
/*****************  session start ********************/
if(!isset($_SESSION))session_start();
/***************** language ********************/
mb_language('Japanese');
mb_internal_encoding('utf-8');
/***************** system name ********************/
define('SITE_NAME', 'PHP.LIB');
/***************** admin dir ********************/
define('ADMIN_DIR', 'admin');
/***************** template extension ********************/
define('TPL_EXT', '.html');
/***************** target to replace ********************/
define('BRACES', ['{{', '}}']);
/***************** version ********************/
define('VERSION', '1.0.0');
/***************** key name ********************/
define('KEY_NAME', [
    'SESSION' => $_SERVER['HTTP_HOST']. ADMIN_DIR.'_Auth'
]);
/*****************  server path  ********************/
define('SERVER_DIR', [
    'LIB' => PRIVATE_DIR.'lib/',
    'INIT' => PRIVATE_DIR.'init/',
    'VIEW' => PRIVATE_DIR.'view/',
    'CSS' => PRIVATE_DIR.'css/',
    'IMG' => PRIVATE_DIR.'images/',
    'JS' => PRIVATE_DIR.'js/',
    'TMP' => PRIVATE_DIR.'tmp/',
]);
/***************** mail server ********************/
define('MAIL_SERVER', [
    'HOST' => '',
    'PORT' => 587,
    'ENCRPT' => 'tls',
    'USER' => '',
    'PASS' => '',
    'EMAIL' => '',
    'FROM_NAME' => 'My Web SIte',
    'ENCODING' => 'base64',
    'CHARSET' => 'UTF-8',
]);
/***************** database config ********************/
define('DATABASE', [
    'TYPE' => 'mysql',
    'NAME' => '',//データベース名
    'HOST' => 'localhost',
    'PORT' => 3306,
    'USER' => '',//データベースユーザー名
    'PASS' => '',//データベースパスワード
]);
/***************** composer autoload ********************/
require(PRIVATE_DIR . 'vendor/autoload.php');
/*****************  original class auto loader ********************/
function autoloadClass($class){
    $class_namespace = explode("\\", $class);
    $class_file_name = SERVER_DIR['LIB'] . end($class_namespace) . '.php';
    if (is_readable($class_file_name)) {
        require $class_file_name;
        return true;
    } else {
        return false;
    }
}
spl_autoload_register('autoloadClass');

/***************** debug ********************/
function debug(){
    $arr = debug_backtrace();
    echo '<div>' . $arr[ 0 ][ 'file' ] . '  ' . $arr[ 0 ][ 'line' ] . '</div>';
    $args = func_get_args();
    echo '<pre style="border:1px solid #CCC; padding: 5px; font-family: monospace; font-size: 12px;">';
    foreach ($args as $val) {
        print_r($val);
    }
    echo '</pre>';
}