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

$jenti = new JentiWord($config);
if ($jenti->error)
{
    echo ajax_json_response_error($jenti->error);
    exit;
}

$user_info["LANGUAGE_CODE"] = $session->language_code;
$response = $jenti->get_next_word($user_info);
if ($jenti->error)
{
    echo ajax_json_response_error($jenti->error);
    exit;
}

echo ajax_json_response($response);

restore_error_handler();
