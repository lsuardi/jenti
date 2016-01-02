<?php

global $config;
$config["docroot"] = str_replace( "\\", "/", dirname(dirname( $_SERVER['SCRIPT_FILENAME'])));

function test_newline()    {echo "\n";}
function test_echoln( $str){echo "$str\n";}
function test_echobr( $str){echo "<BR>$str";}
function test_print_r( $x) {echo "<PRE>" . print_r( $x, TRUE) . "</PRE>";}
function test_callstack(){echo "<PRE>"; debug_print_backtrace(); echo "</PRE>";}
function test_debug( $str) 
{ global $config; 
  if (isset( $config["debug"])) 
  { echo( "<BR>$str"); 
  }
}
function test_echo_flush( $str)
{ echo $str;
  for($k=0; $k<10000; $k++)
    echo ' ';
}

// convenience access
function test_get_request( $name) {return( isset( $_REQUEST[$name])
                                       ? $_REQUEST[$name]
                                       : '');}
function test_get_session( $name) {return( isset( $_SESSION[$name])
                                       ? $_SESSION[$name]
                                       : '');}
function test_get_cookie( $name)  {return( isset( $_COOKIE[$name])
                                       ? $_COOKIE[$name]
                                       : '');}

//test_print_r($_SERVER);
//echo phpinfo();