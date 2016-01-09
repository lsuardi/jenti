<?php

require_once "../JentiConfig.php";
require_once "../JentiSession.php";
require_once "../JentiAjax.php";

set_error_handler("ajax_error_handler");

$session = new JentiSession($config);
if($session->error)
{
    echo ajax_json_response_error($session->error);
    exit;
}

$session->change_skin();

echo ajax_json_response("");

restore_error_handler();
