<?php

require_once '../JentiConfig.php';
require_once '../JentiSession.php';
require_once "../JentiAjax.php";

$session = new JentiSession($config);
if($session->error)
{
    echo ajax_json_response_error($session->error);
    exit;
}

$email = $_REQUEST["BERGAMO"];
$pwd = $_REQUEST["ENDINE"];

$session->login($email, $pwd);
if($session->error)
{
    echo ajax_json_response_error($session->error);
    exit;
}

echo ajax_json_response("");
