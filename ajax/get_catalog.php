<?php

require_once "../JentiConfig.php";
require_once "../JentiSession.php";
require_once "../JentiAjax.php";

function errorHandler($errno, $errstr, $errfile, $errline)
{
	header('HTTP/1.1 500 ['.$errno.'] '.$errstr.' in '.$errfile.' at line '.$errline);
	header('Content-Type: application/json; charset=UTF-8');
	die();
}

set_error_handler("errorHandler");

$session = new JentiSession($config);
if($session->error)
{
    echo ajax_json_response_error($session->error);
    exit;
}

restore_error_handler();

echo ajax_json_response($session->catalog);