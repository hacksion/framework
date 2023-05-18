<?php
$CURRENT_DIR = trim(str_replace(['index.php', 'install.php'], '', $_SERVER['SCRIPT_NAME']), '/');
$URL = (empty($_SERVER["HTTPS"]) ? "http://" : "https://").$_SERVER['HTTP_HOST'].'/'.($CURRENT_DIR ? $CURRENT_DIR.'/':'');

$DBFILE = "<?php
define('PUBLIC_URL', [
	'URL' => '$URL',
	'CURRENT_DIR' => '$CURRENT_DIR',
    'ASYNC' => '{$URL}async/',
    'JS' => '{$URL}js/',
    'CSS' => '{$URL}css/',
    'IMG' => '{$URL}images/'
]);";
$resutl = file_put_contents(PRIVATE_DIR.'init/public.php', $DBFILE);
if($resutl)header("Location: ./");