<?php

require_once "../JentiConfig.php";
require_once "../JentiCrawler.php";
require_once "TestSupport.php";

$config["cache_on"] = false;
$config["default_source"] = "Wiktionary";
//$config["default_source"] = "Wikizionario";

//$words_array = explode(PHP_EOL, "sun");


$words_array = explode(PHP_EOL, file_get_contents("../words/en.1000.txt"));

$crawler = new JentiCrawler($config);
$crawler->crawl($words_array);

//test_print_r($words_array);