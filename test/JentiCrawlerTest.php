<?php

ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('error_reporting', E_ALL);

date_default_timezone_set('Europe/Rome');

require_once "../JentiConfig.php";
require_once "../crawler/JentiCrawler.php";
require_once "TestSupport.php";

$config["debug"] = 0;
$config["cache_on"] = true;
$config["default_source"] = "Merriam-Webster";
$config["save_words"] = false;
//$config["default_source"] = "Wiktionary";
//$config["default_source"] = "Wikizionario";

$words_array = explode(PHP_EOL, "word
duty
walk
pier
suck
sock
self
guide");


//$words_array = explode(PHP_EOL, file_get_contents("../words/en.1000.txt"));

$crawler = new JentiCrawler($config);
$crawler->crawl($words_array);

//test_print_r($words_array);