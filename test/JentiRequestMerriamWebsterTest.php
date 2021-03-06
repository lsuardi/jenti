<?php

require_once "../JentiConfig.php";
require_once "../crawler/JentiRequestMerriamWebster.php";

ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('error_reporting', E_ALL);

$config["debug"] = 0;
$config["cache_on"] = true;
$config["wait"] = 0;

$request = new JentiRequestMerriamWebster($config);
$result = $request->get_word("self");
if ($request->error)
{
    echo("<BR><BR>".$request->error);
}
echo "<PRE>".print_r($result, true)."</PRE>";
//$request->debug_echo_dom(null, 0, null, null);
/*
$nodes = $request->xpath->query("/html/body/div/div/div/div/div/main/article/div/div/div/h2[text()='Examples of ']");
foreach($nodes as $n)
{
    $request->debug_echo_dom($n, 0, null, null);
}
*/
