<?php

require_once 'JentiAdmin.php';

jenti_admin_page_header();

echo '<h2>WORD LIST</h2>';

$config["debug"] = 0;
$config["table_name"]   = 'word_list';
$config["table_pk"]     = 'ID';
$config["table_pk_auto"] = 'yes';
$config["action"]       = 'IUD'; // insert, update, delete
$config["row_count"] = 100;

$config["column"]["ID"]["name"] = "ID";
$config["column"]["WORD"]["name"] = "PAROLA";
$config["column"]["WORD"]["filter"] = "WORD";
$config["column"]["LANGUAGE_CODE"]["name"] = "LINGUA";
$config["column"]["LANGUAGE_CODE"]["filter"] = "LANGUAGE_CODE";

$DBEdit = new DBEdit( $config );
if (! $DBEdit->errore)
{
   echo $DBEdit->execute();
}

jenti_admin_page_footer();
?>
