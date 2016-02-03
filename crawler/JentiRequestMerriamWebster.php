<?php

// Copyright 2006-2015 - NINETY-DEGREES

require_once "JentiRequest.php";



///////////////////////////////////////////////////////////////////
// HTTP GET requests
///////////////////////////////////////////////////////////////////
class   JentiRequestMerriamWebster
extends JentiRequest
{
    public $language_code = "en";
    private $type_array = array('noun', 'adjective', 'verb');
    
    
    function __construct( $args=null)
    {
        parent::__construct( $args);

        $this->service_endpoint   = "http://www.merriam-webster.com";
        $this->service_name = "Merriam-Webster";
    }



    //////// get word request
    function get_word( $word)
    {
        $url = $this->service_endpoint . "/dictionary/" . urlencode( $word);
        $bool = $this->get_web_page($url);
        if ($bool && $this->merriam_wordoftheday())
        {
            // attempt another get page after hitting word of the day page
            $bool = $this->get_web_page($url);
        }
        
        if ($bool)
        { 
            return( $this->get_word_data( $word));
        }

        return(array());
    }



    //////// parse results and return relevant data
    private function get_word_data( $word)
    {
        $word_array = array();

        // search for noun, adjective, verb
        $word_type_nodes = $this->xpath->query('/html/body/div/div/div/div/div/main/article/div/div/div/span/em');
        if ($word_type_nodes->length)
        {
            foreach ($word_type_nodes as $type_node)
            {
                $this->process_word_type_node($word, $type_node, $word_array);
            }
            if (count($word_array) > 0)
            {
                $this->process_examples($word_array);
            }
        }

        if (count($word_array) == 0)
        { 
            $this->error = "JentiRequestMerriamWebster: Did not find words at url " . $this->url;
        }

        return($word_array);
    }

    
    
    private function process_word_type_node($word, $word_type_node, &$word_array)
    {
        $word_type = $word_type_node->textContent;
        if ((array_search($word_type, $this->type_array) !== FALSE)
        &&  !$this->word_type_exists($word_type, $word_array))
        {
            // simple definitions
            $content_node = $word_type_node->parentNode->parentNode->parentNode->parentNode;
            $definition_nodes = $this->xpath->query("div/div/ul/li/p/span", $content_node);
            if ($definition_nodes->length)
            {
                $i = 0;
                $definitions_array = array();
                foreach ($definition_nodes as $definition)
                {
                    $definition_text = utf8_decode($definition->childNodes->item(1)->textContent);

                    $definitions_array[$i]["DEFINITION"] = trim(preg_replace('/\s+/', ' ', $definition_text));
                    $definitions_array[$i]["DEFINITION_SHORT"] = substr(trim(preg_replace('/\s+/', '', $definition_text)), 0, 10);
                    //$definitions_array[$i]["TAGS"] = $tags;
                    //$definitions_array[$i]["TAGS_ARRAY"] = $definition_tags_array;
                    $definitions_array[$i]["SOURCE_NAME"] = $this->service_name;
                    $definitions_array[$i]["SOURCE_URL"] = $this->service_endpoint;
                    $i = $i + 1;
                }

                if ($i > 0)
                {
                    $word_data["WORD"] = $word;
                    $word_data["TYPE"] = $word_type;
                    $word_data["LANGUAGE_CODE"] = $this->language_code;
                    $word_data["DEFINITION_ARRAY"] = $definitions_array;

                    $word_array[] = $word_data;
                }
            }
        }
    }

    
    
    private function process_examples(&$word_array)
    {
        $word_count = 0;

        // example nodes
        $example_header_nodes = $this->xpath->query("/html/body/div/div/div/div/div/main/article/div/div/div/h2[text()='Examples of ']");
        foreach ($example_header_nodes as $example_header)
        {
            if (isset($word_array[$word_count]))
            {
                $examples_array = array();

                // example nodes
                $example_nodes = $this->xpath->query("div/ol/li", $example_header->parentNode);
                if ($example_nodes->length)
                {
                    foreach ($example_nodes as $example)
                    {
                        $example_text = trim($example->textContent);
                        if (strlen($example_text) > 0)
                        {
                            $examples_array[] = $example_text;
                        }
                    }
                    
                    $word_array[$word_count]["EXAMPLES_ARRAY"] = $examples_array;
                }
                
                $word_count = $word_count + 1;
            }
        }
    }
    
    
    
    private function word_type_exists($word_type, $word_array)
    {
        foreach ($word_array as $word)
        {
            if ($word["TYPE"] == $word_type)
            {
                return(TRUE);
            }
        }
        return(FALSE);
    }
    
  
    //////// check HTML for errors
    private function wiktionary_error()
    {
        $body = $this->xpath->query( '//html/body');
        $text = @strtolower( $body->item(0)->textContent);
        if (substr_count( $text, 'automated requests'))
        { $this->error = "WIKTIONARY ERROR";
          return( TRUE);
        } 
        $this->error = "";
        return( FALSE);
    }

    private function merriam_wordoftheday()
    {
        // http://www.merriam-webster.com/interstitial-ad?next=/dictionary/duty
        
        // the word of the day page has a SKIP link
        $skip_nodes = $this->xpath->query("/html/body/div/div/div/div[@class='skip-btn skip-btn-bot']");
        if ($skip_nodes->length > 0)
        {
            return(TRUE);
        }
        return(FALSE);
    }
}
