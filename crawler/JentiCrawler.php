<?php

// Copyright 2015 - NINETY-DEGREES

require_once "../JentiWord.php";
require_once "JentiRequestWiktionary.php";
require_once "JentiRequestWiktionaryEN.php";
require_once "JentiRequestMerriamWebster.php";

///////////////////////////////////////////////////////////////////
// Crawl web pages searching for words and word definitions
///////////////////////////////////////////////////////////////////
class JentiCrawler
{ 
    public $error;

    private $config;
    private $debug = 0;
    private $level = 0;
    private $log_file = "jenti_log.txt";
    private $log_resource;
    
    function __construct($config)
    {
        $this->config = $config;
        $this->level = isset($config["level"]) ? $config["level"] : $this->level; 
        $this->debug = isset($config["debug"]) ? $config["debug"] : $this->debug;
        $this->log_file = isset($config["log_file"]) ? $config["log_file"] : $this->log_file;
    }

    /**
     * Execute the word discovery process.
     *
     *    within a data source
     *      get words from word list that have not been acquired from source
     *      for each word
     *        get word definitions from source
     *        add word to database
     *        add more words to word list   
     * 
     * @param array $word_array optional list of words 
     */
    public function crawl($word_array=null)
    {
        // prepare for long processing time
        ini_set('default_socket_timeout', 36000);
        ini_set('max_execution_time', 36000);

        $jrequest = $this->create_request();
        $jword = new JentiWord($this->config);

        if ($word_array == null)
        {
            $word_array = $jword->get_words_without_definition($jrequest);
            if ($jword->error)
            {
                echo $jword->error;
                return;
            }
        }

        // send every message to the browser immediately
        ob_implicit_flush( TRUE);
        
        $this->log_open();
        $msg = get_class($this).": processing " . count($word_array) . " words from " . $jrequest->service_name;
        $this->echo_msg_flush($msg);
        foreach ($word_array as $word)
        {
            $word = trim($word);
            $dictionary_word_array = $jrequest->get_word($word);
            if ($jrequest->error)
            {
                $this->echo_msg_flush($jrequest->error);
            }
            else
            {
                foreach($dictionary_word_array as $word_info)
                {
                    if ($this->config["save_words"] == true)
                    {
                        $jword->add_word($word_info);
                    }
                    $word_info["ERROR_ARRAY"] = $jword->error_array;
                    $this->echo_word_info($word_info);
                }
            }

            if (count($dictionary_word_array) == 0)
            {
                // data source cannot provide this word
                $avoid_info["WORD"] = $word;
                $avoid_info["LANGUAGE_CODE"] = $jrequest->language_code;
                $avoid_info["SOURCE_NAME"] = $jrequest->service_name;
                $jword->update_word_list_word($avoid_info);
                if ($jword->error)
                {
                    $this->echo_msg_flush($jword->error);            
                }
            }
        }
        $this->log_close();
        ob_implicit_flush( FALSE);
    }
    
    /**
     * Open log file for writing.
     * 
     * @return resource log file descriptior
     */
    private function log_open()
    { 
        $this->log_resource = fopen($this->log_file, "w");
    }

    /**
     * Write to log file.
     * 
     * @param resource $log log file resource descriptor
     * @param string $txt text to write to log
     */
    private function log_writeln($txt)
    {
        fwrite($this->log_resource, str_replace(PHP_EOL, " ", $txt) . PHP_EOL);
    }

    /**
     * Close log file.
     * 
     * @param resource $log log file resource descriptor
     */
    private function log_close()
    {
        fclose($this->log_resource);
    }

    /**
     * Echo message to browser.
     * 
     * @param string $msg message to be echoed to browser
     */
    private function echo_msg_flush($msg)
    { 
        echo "<PRE>";
        echo "[".date("h:i:s")."] ";
        echo preg_replace('/\s+/', ' ', $msg);
        for($k=0; $k<10000; $k++)
        { echo ' ';
        }
        echo "</PRE>";
        
        echo '<script language="JavaScript" type="text/javascript">'
             . 'window.scrollTo(0, document.body.scrollHeight);'
             . '</script>'
             ;

        echo PHP_EOL;

        //ob_flush();

        $this->log_writeln($msg.PHP_EOL);
    }
    
    /**
     * Echo word info to browser.
     * 
     * @param array $word_info message to be echoed to browser
     */
    private function echo_word_info($word_info)
    { 
        $error_array = $word_info["ERROR_ARRAY"];
        $error_count = count($error_array);
        $this->echo_msg_flush
            ("ERRORS [{$error_count}] "
            ."WORD [{$word_info["WORD"]}] "
            ."TYPE [{$word_info["TYPE"]}] "
            ."DEFINITION [{$word_info["DEFINITION_ARRAY"][0]["DEFINITION"]}] "
            ."TAGS [{$word_info["DEFINITION_ARRAY"][0]["TAGS"]}] "
            );

        // log errors except duplicates
        foreach($error_array as $error)
        {
            if (!strpos($error, "[1062:"))
            {
                $this->log_writeln($error.PHP_EOL);
                if ($this->config["debug"])
                {
                    $this->echo_msg_flush($error);
                }
            }
        }
    }

    /**
     * Create a request object based on configured data sources
     * 
     * @return object A request object
     */
    private function create_request()
    {
        switch ($this->config["default_source"])
        {
            case "Merriam-Webster":
            {
                return new JentiRequestMerriamWebster($this->config);
            }
            case "Wiktionary":
            {
                return new JentiRequestWiktionaryEN($this->config);
            }
            default:
            {
                return new JentiRequestWiktionary($this->config);
            }
        }
    }
}

