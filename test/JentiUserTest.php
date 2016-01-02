<?php

require_once "TestSupport.php";
require_once "../JentiConfig.php";
require_once "../JentiUser.php";

$user = new JentiUser($config);
if($user->error)
{
    test_echobr($user->error);
}

$user_input["EMAIL"] = "eliasuardi@gmail.com";
$user_input["PASSWORD"] = "houston77077";
$user->add_user($user_input);
if($user->error)
{
    test_echobr($user->error);
}

$user_info = $user->validate_user($user_input);
if($user->error)
{
    test_echobr($user->error);
}

test_print_r($user_info);

//echo json_encode($word);
