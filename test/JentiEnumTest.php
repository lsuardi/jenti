<?php

require_once "../JentiConfig.php";
require_once "../JentiEnum.php";

$enum = new JentiEnum($config);
echo "<PRE>".print_r($enum->data, true)."</PRE>";
echo "<BR>".$enum->data["WORD_TYPE"][1];
echo "<BR>".$enum->data["LANGUAGE"][1];
echo "<BR>it exists [{$enum->value_exists("LANGUAGE_CODE", "it")}]";



$config["LANGUAGE_CODE"] = "en";

$enum = new JentiEnum($config);
echo "<PRE>".print_r($enum->data, true)."</PRE>";
echo "<BR>".$enum->data["WORD_TYPE"][1];
echo "<BR>".$enum->data["LANGUAGE"][1];
