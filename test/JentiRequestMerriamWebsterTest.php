<?php

require_once "../JentiConfig.php";
require_once "../crawler/JentiRequestMerriamWebster.php";

$config["debug"] = 0;
$config["cache_on"] = true;
$config["wait"] = 0;

$request = new JentiRequestMerriamWebster($config);
$result = $request->get_word("duty");
if ($request->error)
{
    echo("<BR><BR>".$request->error);
}
echo "<PRE>".print_r($result, true)."</PRE>";
//$request->debug_echo_dom(null, 0, null, null);
