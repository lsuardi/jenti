<?php

require_once "../JentiConfig.php";
require_once "../JentiWord.php";
require_once "../JentiRequestWiktionary.php";

$jenti = new JentiWord($config);
$user_info["LANGUAGE_CODE"] = "it";
$word = $jenti->get_word(853);
echo "<PRE>".print_r($word, true)."</PRE>";
echo json_encode($word);

//$request = new JentiRequestWiktionary($config);
/*
$word_array = $request->get_word("bambino");
if ($request->error)
{
    echo($request->error);
    exit;
}
*/
/*
foreach ($word_array as $word_data)
{
    $jenti->add_word_and_definitions($word_data);
    if ($jenti->error)
    {
        echo($jenti->error);
    }
}
*/
//$word_array = $jenti->get_words_without_definition($request);
