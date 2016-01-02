<?php

require_once 'JentiConfig.php';
require_once 'JentiSession.php';

$session = new JentiSession($config);
if($session->error)
{
    echo $session->error;
}

$session->validate_session();
if($session->error)
{
    echo 'RESUME: '.$session->error;
}
else
{
    echo "USER AUTHENTICATED";
}
