<?php

require_once "../JentiConfig.php";
require_once "../JentiSession.php";
require_once "TestSupport.php";

ob_start();

//ini_set('error_reporting', 0);

$session = new JentiSession($config);
if($session->error)
{
    test_echobr($session->error);
}

$session->login("gigi@ggg.com", "dddd");
if($session->error)
{
    test_echobr($session->error);
}
else
{
    $session->logout();
    if($session->error)
    {
        test_echobr($session->error);
    }
}

//test_print_r($session);
//test_print_r($_COOKIE);
//test_print_r($catalog);

ob_end_flush();

//echo json_encode($session);
