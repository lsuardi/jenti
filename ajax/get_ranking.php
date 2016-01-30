<?php

require_once "../JentiConfig.php";
require_once "../JentiUser.php";
require_once "../JentiAjax.php";

set_error_handler("ajax_error_handler");

$user = new JentiUser($config);
if($user->error)
{
    echo ajax_json_response_error($user->error);
    exit;
}

echo ajax_json_response($user->get_ranking());

restore_error_handler();
