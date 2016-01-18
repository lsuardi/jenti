<?php

require_once "../JentiConfig.php";
require_once "../JentiSession.php";
require_once "../JentiWord.php";
require_once "../JentiAjax.php";

set_error_handler("ajax_error_handler");

$session = new JentiSession($config);
if($session->error)
{
    echo ajax_json_response_error($session->error);
    exit;
}

$activity_info["WORD_ID"] = $_REQUEST["WORD_ID"];
$activity_info["DEFINITION_ID"] = $_REQUEST["DEFINITION_ID"];

if(isset($_REQUEST["FEEDBACK"]))
    $activity_info["FEEDBACK"] = trim($_REQUEST["FEEDBACK"]);

if(isset($_REQUEST["LIKE"]))
    $activity_info["LIKE"] = $_REQUEST["LIKE"];
    
if ((isset($activity_info["LIKE"])&&$activity_info["LIKE"])||isset($activity_info["FEEDBACK"]))
{
    $session->save_user_feedback($activity_info);
    if($session->error)
    {
        echo ajax_json_response_error($session->error);
        exit;
    }
}

echo ajax_json_response("");

restore_error_handler();
