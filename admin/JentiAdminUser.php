<?php

require_once 'JentiAdmin.php';

jenti_admin_page_header();

echo '<h2>USER</h2>';

//$config["debug"] = 1;
$config["table_name"]   = 'user';
$config["table_pk"]     = 'ID';
$config["table_pk_auto"] = 'yes';
$config["action"]       = 'IUD'; // insert, view, update, delete

$DBEdit = new DBEdit( $config );
if (! $DBEdit->errore)
{
   echo $DBEdit->execute();
}

jenti_admin_page_footer();
?>
