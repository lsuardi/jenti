<?php

// Copyright 2006-2015 - NINETY-DEGREES

///////////////////////////////////////////////////////////////////////////
////////// CONFIGURATION ITEMS ARE DEFINED IN PHP SYNTAX!        ////////// 
////////// INTERNAL DEFAULTS ARE USED WHEN ITEMS ARE NOT DEFINED ////////// 
///////////////////////////////////////////////////////////////////////////

global $config;

// debug flag
$config["debug"] = 0;

$config["wait"] = 6;
$config["cache_on"] = true;
$config["refresh_http"] = 10;
$config["row_count"] = 10;

// language support
$config["supported_language_codes"] = array("en", "it");
$config["default_language_code"] = "en";

// data sources
$config["data_sources"] = array("Wikizionario","Wiktionary");
$config["default_source"] = "Wiktionary";

// skins
$config["skins"] = array("blue","orange","yellow","black");
$config["default_skin"] = "blue";

// database credentials and other system specific properties
if (php_uname('n') == "LSUARDI-PC")
{
    // local development system
    $config["hostname"] = "localhost";
    $config["user_name"] = "root";
    $config["user_pswd"] = "root";
    $config["user_db"] = "jenti";
    $config["docroot"] = str_replace( "\\", "/", $_SERVER['DOCUMENT_ROOT']) . "/jenti";
}
else if (php_uname('n') == "MATTEO-PC")
{
    // local development system
    $config["hostname"] = "localhost";
    $config["user_name"] = "root";
    $config["user_pswd"] = "";
    $config["user_db"] = "jenti";
    $config["docroot"] = str_replace( "\\", "/", $_SERVER['DOCUMENT_ROOT']) . "/jenti";
}
else
{
    // hostmonster system
    //$config["cache_root"] = "/home1/ninetyde/public_html/jenti/dev/cache";
    //$config["log_file"] = "/home1/ninetyde/public_html/jenti/dev/log.txt";
    $config["hostname"] = "localhost";
    $config["user_name"] = "ninetyde_jenti01";
    $config["user_pswd"] = "Rondin@2015";
    $config["user_db"] = "ninetyde_jenti";
    $config["docroot"] = str_replace( "\\", "/", $_SERVER['DOCUMENT_ROOT']) . "/jenti/dev";
}

$config["cache_root"] = $config["docroot"]."/cache";
$config["log_file"] = $config["docroot"]."/logs/log.txt";

?>