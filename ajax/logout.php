<?php

require_once 'JentiConfig.php';
require_once 'JentiSession.php';

set_error_handler("ajax_error_handler");

$session = new JentiSession($config);
if($session->error)
{
    echo 'LOGOUT: '.$session->error;
}

$session->logout();
if($session->error)
{
    echo 'LOGOUT: '.$session->error;
}
else
{
    echo "LOGGED OUT";
}

restore_error_handler();
