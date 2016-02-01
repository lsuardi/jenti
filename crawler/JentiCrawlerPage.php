<?php 

// Copyright 2006-2015 - NINETY-DEGREES

?>



<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE"/>
  
	<link REL="STYLESHEET" TYPE="text/css" HREF="nd_style.css"/>

  <title>JENTI CRAWL</title>
	
  <script type="text/javascript">
    function scroll_log()
    { log.scrollTop = log.scrollHeight;
    }
    function log_write( text)
    { 
      var newDiv = document.createElement( "div");
	    newDiv.innerHTML = '<font class="SmallText">' + text + '</font>'; 

      log.appendChild( newDiv);
      log.scrollTop = log.scrollHeight;
    }
  </script>

</head>
	

<body class="StandardBody">
		
<p class="PageTitle">Jenti Crawler</p>

<DIV id="log" style="background:#FFFFFF; width:1000px; height: 400px; overflow:auto; white-space: nowrap">
</DIV>



<?php

require_once "../JentiConfig.php";
require_once "../JentiWord.php";
require_once "JentiRequestWiktionaryEN.php";

//////////////////////////////////////////////////////////////////////////
// logging functions
//////////////////////////////////////////////////////////////////////////
function log_open()
{ 
    global $config;
    $log = fopen($config["log_file"], "w");
    return $log;
}
function log_writeln($log, $txt)
{
    fwrite($log, str_replace(PHP_EOL, " ", $txt) . PHP_EOL);
}
function log_close($log)
{
    fclose($log);
}
//////////////////////////////////////////////////////////////////////////
// output string to scrolling log
//////////////////////////////////////////////////////////////////////////
function echo_msg_flush( $msg)
{ 
    echo '<script language="JavaScript" type="text/javascript">'
                 . 'log_write( \''. str_replace( array( '\'', "\n", "\r")
                                               , array( '"', "", "")
                                               , $msg) .'\');'
                 . '</script>';
    for($k=0; $k<50000; $k++)
    { echo ' ';
    }
    echo PHP_EOL;
}


// Turn off output buffering
ini_set('default_socket_timeout', 36000);
ini_set('max_execution_time', 36000);

$words = explode(PHP_EOL, "word");

$request = new JentiRequestWiktionary($config);
$jenti_word = new JentiWord($config);

ob_implicit_flush( TRUE);
$log = log_open();
foreach ($words as $word)
{
    $word_array = $request->get_word($word);
    if ($request->error)
    {
        echo_msg_flush($request->error);
        log_writeln($log, $request->error);
        continue;
    }
    foreach($word_array as $word_info)
    {
        $jenti_word->add_word_and_definitions($word_info);
        if (!$jenti_word->error)
        {
            echo_msg_flush(print_r($word_info, true));            
            log_writeln($log, print_r($word_info, true));
        }

        if ($jenti_word->error)
        {
            echo_msg_flush($jenti_word->error);
            log_writeln($log, $jenti_word->error);
        }
    }
}
log_close($log);
ob_implicit_flush( FALSE);


?>


</body>
</html>
