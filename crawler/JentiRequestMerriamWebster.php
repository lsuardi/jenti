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
    private $word_array = null;
    private $word_type = null;

    
    
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
        $this->word_array = array();

        $div_nodes = $this->xpath->query('/html/body/div/div/div/div/div/main/article/div');
        if ($div_nodes->length)
        {
            foreach ($div_nodes as $div)
            {
                echo "<BR>div node<BR>";
                $this->debug_echo_dom($div, 0, null, null);
                echo "<BR>";

                // does div contain word type
                $type_em_nodes = $this->xpath->query('div/div/span/em', $div);
                if ($type_em_nodes->length > 0)
                {
                    $this->word_type = $type_em_nodes->item(0)->textContent;
                    echo "<BR>type {$this->word_type}<BR>";
                }
            }
        }

        // search for noun, adjective, verb
        /*
        $word_type_nodes = $this->xpath->query('/html/body/div/div/div/div/div/main/article/div/div/div/span/em');
        if ($word_type_nodes->length)
        {
            foreach ($word_type_nodes as $type_node)
            {
                // word definitions
                $this->process_word_type_node($word, $type_node, $word_array);
            }
            if (count($word_array) > 0)
            {
                // add examples and more words
                $this->process_examples($word_array);
                $this->process_more_words($word_array);
            }
        }
        */

        // find word entries
        /*
        $word_entry_nodes = $this->xpath->query('/html/body/div/div/div/div/div/main/article/div/div/div/div/span');
        if ($word_entry_nodes->length)
        {
            foreach ($word_entry_nodes as $entry_node)
            {
                $this->debug_echo_dom($entry_node, 0, null, null);
                echo "<BR>";
                continue;

                $word_entry_index = intval($entry_node->textContent);
                if ($word_entry_index == $this->word_entry_index_next)
                {
                    // find word definitions
                    $this->process_word_entry($word, $entry_node, $word_array);
                   
                    $this->word_entry_index_next = $this->word_entry_index_next + 1;
                }
                
            }
        }
        */
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
        &&  !isset($word_array[$word_type]))
        {
            echo "<BR>word type<BR>";
            $div_node = $word_type_node->parentNode->parentNode;
            $this->debug_echo_dom($div_node, 0, null, null);
            echo "<BR>";
            echo "<BR>next sibling<BR>";
            $this->debug_echo_dom($div_node->nextSibling->nextSibling->nextSibling, 0, null, null);
            echo "<BR>";
            return;

            $i = 0;
            $definitions_array = array();


            // simple definitions
            $definition_nodes = $this->xpath->query("div/div/ul/li/p/span", $content_node);
            if ($definition_nodes->length)
            {
                foreach ($definition_nodes as $definition)
                {
                    $definition_text = utf8_decode($definition->childNodes->item(1)->textContent);

                    $definitions_array[$i]["DEFINITION"] = trim(preg_replace('/\s+/', ' ', $definition_text));
                    $definitions_array[$i]["DEFINITION_SHORT"] = substr(trim(preg_replace('/\s+/', '', $definition_text)), 0, 10);
                    $definitions_array[$i]["TAGS"] = null;
                    //$definitions_array[$i]["TAGS_ARRAY"] = $definition_tags_array;
                    $definitions_array[$i]["SOURCE_NAME"] = $this->service_name;
                    $definitions_array[$i]["SOURCE_URL"] = $this->service_endpoint;
                    $i = $i + 1;
                }
            }

            // numbered definitions
            $definition_nodes = $this->xpath->query("div/div/ol/li/p/span", $content_node);
            if ($definition_nodes->length)
            {
                foreach ($definition_nodes as $definition)
                {
                    $this->debug_echo_dom($definition, 0, null, null);
                    echo "<BR>";
                }                
            }

            if ($i > 0)
            {
                $word_data["WORD"] = $word;
                $word_data["TYPE"] = $word_type;
                $word_data["LANGUAGE_CODE"] = $this->language_code;
                $word_data["DEFINITION_ARRAY"] = $definitions_array;

                $word_array[$word_type] = $word_data;
            }
        }
    }
    
    
    
    private function process_word_entry($word, $word_entry_node, &$word_array)
    {
        $content_node = $word_entry_node->parentNode->parentNode->parentNode;
        $word_type_nodes = $this->xpath->query("div/span/em", $content_node);
        if ($word_type_nodes->length > 0)
        {
            $i = 0;
            $definitions_array = array();

            // definitions
            $definition_nodes = $this->xpath->query("div/*/li/p/span", $content_node);
            if ($definition_nodes->length)
            {
                foreach ($definition_nodes as $span)
                {
                    //$definition_text = utf8_decode($definition->textContent);
                    $span_children = $span->childNodes;
                    foreach ($span_children as $span_child)
                    {
                        if (trim($span_child->textContent) == ":")
                            break;
                    }
                    $definition_text = $span_child->nextSibling->textContent;
                    $definition_text = htmlentities($definition_text, null, 'utf-8');
                    $definition_text = str_replace("&nbsp;", "", $definition_text);
                    $definition_text = html_entity_decode($definition_text, null, 'utf-8');

                    $definitions_array[$i]["DEFINITION"] = trim(preg_replace('/\s+/', ' ', $definition_text), " �");
                    $definitions_array[$i]["DEFINITION_SHORT"] = substr(trim(preg_replace('/\s+/', '', $definition_text)), 0, 10);
                    $definitions_array[$i]["TAGS"] = null;
                    //$definitions_array[$i]["TAGS_ARRAY"] = $definition_tags_array;
                    $definitions_array[$i]["SOURCE_NAME"] = $this->service_name;
                    $definitions_array[$i]["SOURCE_URL"] = $this->service_endpoint;
                    $i = $i + 1;
                }
            }

            $word_type = trim($word_type_nodes->item(0)->textContent);

            if ($i > 0)
            {
                $word_data["WORD"] = $word;
                $word_data["TYPE"] = $word_type;
                $word_data["LANGUAGE_CODE"] = $this->language_code;
                $word_data["DEFINITION_ARRAY"] = $definitions_array;

                if (isset($word_array[$word_type]))
                {
                    // add more definitions to same word
                    foreach ($definitions_array as $definition)
                    {
                        $word_array[$word_type]["DEFINITION_ARRAY"][] = $definition;
                    }
                }
                else
                {
                    // new word
                    $word_array[$word_type] = $word_data;
                }
            }

            //$this->debug_echo_dom($content_node, 0, null, null);
            //echo "<BR>";

        }

                
        return;
        
        $word_type = $word_type_node->textContent;
        if ((array_search($word_type, $this->type_array) !== FALSE)
        &&  !$this->word_type_exists($word_type, $word_array))
        {
            $content_node = $word_type_node->parentNode->parentNode->parentNode->parentNode;

            // numbered definitions
            $definition_nodes = $this->xpath->query("div/div/ol/li/p/span", $content_node);
            if ($definition_nodes->length)
            {
                foreach ($definition_nodes as $definition)
                {
                    $this->debug_echo_dom($definition, 0, null, null);
                    echo "<BR>";
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
    
    
    
    private function process_more_words(&$word_array)
    {
        if (count($word_array) > 0)
        {
            $more_words = array();
            
            // related to word
            $word_link_nodes = $this->xpath->query("/html/body/div/div/div/div/div/main/article/div/div/div/div/div/a");
            foreach ($word_link_nodes as $word_link)
            {
                $more_words[] = trim($word_link->textContent);
            }
            
            // similar terms
            $word_link_nodes = $this->xpath->query("/html/body/div/div/div/div/div/main/article/div/div/div/div/p/a");
            foreach ($word_link_nodes as $word_link)
            {
                $more_words[] = trim($word_link->textContent);
            }
            
            $word_array[0]["MORE_WORDS"] = $more_words;
        }
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
