<?php
namespace TM;

class Token
{
	// *****************************************
    // トークン生成
    // *****************************************
    private static function create() : string
	{
    	return hash('sha256', hash('sha256', getenv('REMOTE_ADDR')).hash('md5', time()).hash('md5', mt_rand()));
    }
    // *****************************************
    // トークン取得
    // *****************************************
    public static function setToken() : string
	{
    	$origin_token = self::create();
    	$_SESSION['HarfToken'] = substr( $origin_token, 10 );
    	$_SESSION['OriginToken'] = $origin_token;
    	return substr( $origin_token, 0, 10 );
    }
    // *****************************************
    // 照合
    // *****************************************
    public static function verification( string $harf_token, $redirect = null )
	{
        $result = strcmp( $_SESSION['OriginToken'], $harf_token.$_SESSION['HarfToken'] ) === 0 ? true:false;
        if($redirect && !$result){
            header("HTTP/1.1 301 Moved Permanently");
            header("Location:" . $redirect);
            exit;
        }
        return $result;
    }
}