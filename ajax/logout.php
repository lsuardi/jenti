<?php

require_once 'JentiConfig.php';
require_once 'JentiSession.php';

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
