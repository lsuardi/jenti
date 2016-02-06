<?php

require 'TestSupport.php';


$var = '<span>  <em>archaic</em>   <span class="intro-colon">:</span>  a low shoe or slipper</span>';
//$var = str_replace(" ", "", $var);
//$var = str_replace(" ", "", $var);
$var = htmlentities($var, null, 'utf-8');
$var = str_replace("&nbsp;", "", $var);
$var = html_entity_decode($var, null, 'utf-8');
echo $var;