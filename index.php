<?php
try {
	require('./init/config.php');
	require('./init/public.php');
	if(empty(PUBLIC_URL['URL']))require('./init/resource.php');
	$router = array_values(array_filter(explode('/', $_SERVER['REQUEST_URI'])));
	if(PUBLIC_URL['CURRENT_DIR'])$router = array_values(array_diff($router, explode('/', PUBLIC_URL['CURRENT_DIR'])));
	(new TM\Controller(['router' => $router, 'public_url' => PUBLIC_URL]))->view();
} catch (\Throwable $e) {
	//$e->getMessage();
	debug($e);
}