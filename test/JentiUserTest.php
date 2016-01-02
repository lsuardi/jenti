<?php

require_once "TestSupport.php";
require_once "../JentiConfig.php";
require_once "../JentiUser.php";

$user = new JentiUser($config);
if($user->error)
{
    test_echobr($user->error);
}
/*
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
*/

$user_info = $user->get_user("gigi@ggg.com");
if ($user->error)
{
    test_echobr($user->error);
}
if (strlen($user_info["NAME"]) == 0)
{
    $pos = strpos($user_info["EMAIL"], "@");
    $user_info["NAME"] = 
        $pos === FALSE 
        ? $user_info["EMAIL"] 
        : substr($user_info["EMAIL"], 0 , $pos)
        ;
}
test_print_r($user_info);

//echo json_encode($word);
