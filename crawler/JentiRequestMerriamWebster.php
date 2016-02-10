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
    
    private $word = null;
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
        $this->word = $word;
        $this->word_array = array();

        $div_nodes = $this->xpath->query('/html/body/div/div/div/div/div/main/article/div');
        if ($div_nodes->length)
        {
            foreach ($div_nodes as $div)
            {
                // does div contain word type
                $type_em_nodes = $this->xpath->query('div/div/span/em', $div);
                if ($type_em_nodes->length > 0)
                {
                    if ($this->div_in_kids_section($div))
                    {
                        // we reached kids definitions, stop
                        break;
                    }

                    // noun, verb, adjective
                    $this->word_type = $type_em_nodes->item(0)->textContent;
                    if ((array_search($this->word_type, $this->type_array) !== FALSE)
                            && !isset($this->word_array[$this->word_type]))
                    {
                        $this->word_array[$this->word_type]["WORD"] = $word;
                        $this->word_array[$this->word_type]["TYPE"] = $this->word_type;
                        $this->word_array[$this->word_type]["LANGUAGE_CODE"] = $this->language_code;
                        $this->word_array[$this->word_type]["DEFINITION_ARRAY"] = array();
                        $this->word_array[$this->word_type]["EXAMPLES_ARRAY"] = array();
                        $this->word_array[$this->word_type]["MORE_WORDS"] = array();

                        echo "<BR>type {$this->word_type}<BR>";
                        
                        $this->process_div_definitions($div);
                    }
                }
                else
                {
                    // look for more definition div
                    $this->process_div_definitions($div);
                    $this->process_div_examples($div);
                    $this->process_div_more_words($div);
                }
                                
                echo "<BR>div node<BR>";
                $this->debug_echo_dom($div, 0, null, null);
                echo "<BR>";
            }
        }

        if (count($this->word_array) == 0)
        { 
            $this->error = "JentiRequestMerriamWebster: Did not find words at url " . $this->url;
        }

        return($this->word_array);
    }

    
    
    private function process_div_definitions($div_node)
    {
        $h2_full_definition_nodes = $this->xpath->query("div/h2", $div_node);
        if (($h2_full_definition_nodes->length > 0)
        &&  (strpos($h2_full_definition_nodes->item(0)->textContent, "Full Definition") !== FALSE))
        {
            // skip full definitions
            return;
        }
        
        $i = sizeof($this->word_array[$this->word_type]["DEFINITION_ARRAY"]);

        // definitions
        $definition_nodes = $this->xpath->query("div/div/*/li/p/span", $div_node);
        if ($definition_nodes->length)
        {
            foreach ($definition_nodes as $span)
            {
                $span_child = $span->firstChild;
                while ($span_child)
                {
                    if (trim($span_child->textContent) == ":")
                    {
                        $span_child = $span_child->nextSibling;
                        break;
                    }                    
                    $span_child = $span_child->nextSibling;
                }
                
                $definition_text = "";
                while ($span_child)
                {
                    $definition_text = $definition_text . " " . trim($span_child->textContent);
                    $span_child = $span_child->nextSibling;
                }
                
                $definition_text = htmlentities($definition_text, null, 'utf-8');
                $definition_text = str_replace("&nbsp;", "", $definition_text);
                $definition_text = html_entity_decode($definition_text, null, 'utf-8');

                $word_info["DEFINITION"] = trim(preg_replace('/\s+/', ' ', $definition_text), " �");
                $word_info["DEFINITION_SHORT"] = substr(trim(preg_replace('/\s+/', '', $definition_text)), 0, 10);
                $word_info["TAGS"] = null;
                //$word_info["TAGS_ARRAY"] = $definition_tags_array;
                $word_info["SOURCE_NAME"] = $this->service_name;
                $word_info["SOURCE_URL"] = $this->service_endpoint;
                
                $this->word_array[$this->word_type]["DEFINITION_ARRAY"][$i] = $word_info;
                
                $i = $i + 1;
            }
        }
    }

        
       
    private function div_in_kids_section($div_node)
    {
        // /html/body/div/div/div/div/div/main/article/h2/#text [0] = SOCK Defined for Kids 
        $result = false;
        $h2_kids_node = $div_node;
        for ($i=0; ($i<5 && isset($h2_kids_node->previousSibling)); $i++)
        {
            $h2_kids_node = $h2_kids_node->previousSibling;
            if (strpos($h2_kids_node->nodeValue, "Defined for Kids"))
            {
                $result = true;
                break;
            }
        }
        return $result;
    }
    
    
    
    private function process_div_examples($div_node)
    {
        $i = sizeof($this->word_array[$this->word_type]["EXAMPLES_ARRAY"]);

        if ($this->xpath_query_contains("div/div/h2", "Examples of", $div_node))
        {
            // example nodes
            $example_nodes = $this->xpath->query("div/div/div/ol/li", $div_node);
            if ($example_nodes->length)
            {
                foreach ($example_nodes as $example)
                {
                    // TODO remove < > from text
                    $example_text = trim($example->textContent);
                    if (strlen($example_text) > 0)
                    {
                        $this->word_array[$this->word_type]["EXAMPLES_ARRAY"][$i] = $example_text;
                        $i = $i + 1;
                    }
                }
            }
        }
    }
    
    
    
    private function process_div_more_words($div_node)
    {
        $i = sizeof($this->word_array[$this->word_type]["MORE_WORDS"]);

        if ($this->xpath_query_contains("div/div/h2", "Related to", $div_node))
        {
            // related to nodes
            $word_link_nodes = $this->xpath->query("div/div/div/div/a", $div_node);
            foreach ($word_link_nodes as $word_link)
            {
                $this->word_array[$this->word_type]["MORE_WORDS"][$i] = trim($word_link->textContent);
                $i = $i + 1;
            }
        }

        // TODO div/div/p/a      
    }
    
    
    
    private function process_more_words(&$word_array)
    {
        if (count($word_array) > 0)
        {
            $more_words = array();
                        
            // similar terms
            $word_link_nodes = $this->xpath->query("/html/body/div/div/div/div/div/main/article/div/div/div/div/p/a");
            foreach ($word_link_nodes as $word_link)
            {
                $more_words[] = trim($word_link->textContent);
            }
            
            $word_array[0]["MORE_WORDS"] = $more_words;
        }
    }
    
    
    
    private function xpath_query_contains($path, $value, $context_node)
    {
        $nodes = $this->xpath->query($path, $context_node);
        foreach ($nodes as $node)
        {
            $text = $node->textContent;
            if (strpos(strtolower($text), strtolower($value)) !== FALSE)
            {
                return true;
            }
        }
        
        return false;
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
