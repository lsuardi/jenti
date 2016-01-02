<?php

require_once 'JentiAdmin.php';

jenti_admin_page_header();

echo '<h2>ENUMERAZIONI</h2>';

//$config["debug"] = 1;
$config["table_name"]   = 'ENUM';
$config["table_pk"]     = 'ID';
$config["table_pk_auto"] = 'yes';
$config["action"]       = 'IUD'; // insert, view, update, delete
$config["column"]["ID"]["name"] = "ID";
$config["column"]["NAME"]["name"] = "NOME";
$config["column"]["NAME"]["filter"] = "NAME";
$config["column"]["LANGUAGE_CODE"]["name"] = "CODICE_LINGUA";
$config["column"]["LANGUAGE_CODE"]["filter"] = "LANGUAGE_CODE";

$DBEdit = new DBEdit( $config );
if (! $DBEdit->errore)
{
   echo $DBEdit->execute();
}

jenti_admin_page_footer();
?>
