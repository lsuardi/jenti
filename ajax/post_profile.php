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

$user_array["EMAIL"] = $_REQUEST["EMAIL"];
$user_array["PASSWORD"] = $_REQUEST["CITY"];
$user_array["NAME"] = $_REQUEST["NAME"];
$user_array["BIRTHDATE"] = $_REQUEST["BIRTHDATE"];

$session->save_user_info($user_array);
if($session->error)
{
    echo ajax_json_response_error($session->error);
    exit;
}

echo ajax_json_response("");
