<?php

require_once "../JentiConfig.php";
require_once "../dbedit/DBEManagerSql.1.1.php";
require_once "../test/TestSupport.php";

ini_set('default_socket_timeout', 36000);
ini_set('max_execution_time', 36000);

ob_implicit_flush(TRUE);

$words_array = explode(PHP_EOL, file_get_contents("en.1000.txt"));
$sqlmgr = new ManagerSql($config);
foreach ($words_array as $word)
{
    $word_list_data["WORD"] = $word;
    $word_list_data["LANGUAGE_CODE"] = "en";
    $sqlmgr->query_insert("WORD_LIST", $word_list_data);

    test_echo_flush("[{$word}] {$sqlmgr->error} <BR>");
}
//echo "<PRE>".print_r($config, true)."</PRE>";
//echo "<PRE>".print_r($words_array, true)."</PRE>";

ob_implicit_flush(FALSE);

