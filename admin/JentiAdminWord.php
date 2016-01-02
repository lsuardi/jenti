<?php

require_once 'JentiAdmin.php';

jenti_admin_page_header();

echo '<h2>WORD</h2>';

//$config["debug"] = 1;
$config["table_name"]   = 'WORD';
$config["table_pk"]     = 'ID';
$config["table_pk_auto"] = 'yes';
$config["action"]       = 'IUD'; // insert, update, delete
$config["table_sql_select"] = "
SELECT WO.ID, WO.WORD, WO.TYPE, WO.LANGUAGE_CODE, WD.TAGS, 
       WD.DEFINITION, WD.SOURCE_NAME
FROM WORD WO, WORD_DEFINITION WD
WHERE WD.WORD_ID = WO.ID
";

$config["column"]["ID"]["name"] = "ID";
$config["column"]["WORD"]["name"] = "PAROLA";
$config["column"]["WORD"]["filter"] = "WORD";
$config["column"]["TYPE"]["name"] = "TIPO";
$config["column"]["TYPE"]["filter"] = "TYPE";
$config["column"]["LANGUAGE_CODE"]["name"] = "LINGUA";
$config["column"]["LANGUAGE_CODE"]["filter"] = "LANGUAGE_CODE";
$config["column"]["TAGS"]["name"] = "ETICHETTE";
$config["column"]["TAGS"]["filter"] = "TAGS";
$config["column"]["SOURCE_NAME"]["name"] = "FONTE";

$DBEdit = new DBEdit( $config );
if (! $DBEdit->errore)
{
   echo $DBEdit->execute();
}

jenti_admin_page_footer();
?>
