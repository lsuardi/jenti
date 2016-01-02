<?php

require_once "../JentiConfig.php";
require_once "../JentiRequestWiktionary.php";

$request = new JentiRequestWiktionary($config);
$result = $request->get_word("guerra");
//$request->get_web_page("http://it.wiktionary.org/wiki/razionale");
if ($request->error)
{
    echo($request->error);
}
echo "<PRE>".print_r($result, true)."</PRE>";
$request->debug_echo_dom(null, 0, null, null);
