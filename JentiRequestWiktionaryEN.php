<?php

// Copyright 2006-2015 - NINETY-DEGREES


require_once "JentiRequest.php";



///////////////////////////////////////////////////////////////////
// HTTP GET requests to WIKTIONARY
///////////////////////////////////////////////////////////////////
class   JentiRequestWiktionaryEN
extends JentiRequest
{
    public $language_code = "en";
    
    function __construct( $args=null)
    { 
        parent::__construct( $args);

        $this->service_endpoint   = "http://en.wiktionary.org";
        $this->service_name = "Wiktionary";
    }



    //////// get word request
    function get_word( $word)
    {
        $url = $this->service_endpoint . "/wiki/" . urlencode( $word);
        if ($this->get_web_page( $url))
        { 
            if (!$this->wiktionary_error())
            { 
              return( $this->get_word_data( $word));
            }
        }

        return(array());
    }
    
    
    
    //////// parse WIKTIONARY results and return relevant data
    private function get_word_data( $word)
    {
    	$word_array = array();
    	$find_english = $this->xpath->query("/html/body/div/div/div/h2/span[@id='English']");
    	if ($find_english->length > 0)
    	{
    		$start_node = $find_english->item(0)->parentNode;
    		
    		$noun_array = $this->get_word_type($start_node, $word, "Noun");
    		if ($noun_array)
    		{
    			$word_array[] = $noun_array;
    		}
    		
    		$verb_array = $this->get_word_type($start_node, $word, "Verb");
    		if ($verb_array)
    		{
    			$word_array[] = $verb_array;
    		}
    		
    		$adjective_array= $this->get_word_type($start_node, $word, "Adjective");
    		if ($adjective_array)
    		{
    			$word_array[] = $adjective_array;
    		}

    		/*
    		if (count($word_array) > 0)
    		{
    			// add more words to first word
    			$word_array[0]["MORE_WORDS"] = $this->get_more_words_from_links(
    					$this->xpath->query("/html/body/div/div/div/ol/li/a"));
    		}
    		*/
    		
    		
    		if (count($word_array) == 0)
    		{
    			$this->error = get_class($this) . ": Did not find words at url " . $this->url;
    		}
    		
    		return($word_array);
    	}
    }
    
    
    
    private function get_word_type($english_node, $word, $word_type)
    {
    	$word_data = null;
    	$node = $english_node->nextSibling;
    	while ($node && ($node->nodeName != "h2"))
    	{
    		$node = $node->nextSibling;
    		if ($node && $node->firstChild && $node->firstChild->textContent == $word_type)
    		{
    			$word_definitions = $this->get_word_definitions_from_start_node($node);
    			if (!$word_data)
    			{
		    		if (count($word_definitions) > 0)
		    		{ 
			            $word_data["WORD"] = $word;
			            $word_data["TYPE"] = strtolower($word_type);
			            $word_data["LANGUAGE_CODE"] = $this->language_code;
			            $word_data["DEFINITION_ARRAY"] = $word_definitions;
			        }
    			}
    			else
    			{
    				$word_data["DEFINITION_ARRAY"] = array_merge($word_data["DEFINITION_ARRAY"], $word_definitions);
    			}
    		}
    	}
    	 
    	return $word_data;
    }

  
  
    //////// parse html span that contains a word definitions
    // e.g. /html/body/div/div/div/h3/span[@id='Aggettivo']
    private function get_word_definitions_from_start_node($startNode)
    {
        $definitions_array = array();

        if ($startNode)
        {        
            $node = $startNode;
            while($node && $node->nodeName != "ol" && $node->nodeName != "ul")
            { 
                $node = $node->nextSibling;
            }
            if (!$node)
            {
                return $definitions_array;
            }

            $i = 0;
            $li_definitions = $node->childNodes;
            foreach ($li_definitions as $child_def)
            {
                if ($child_def->nodeName == "li")
                {
                    $definition = "";
                    $tags = "";
                    $processing_tags = $child_def->firstChild->textContent == "(" ? true : false;
                    $definition_tags_array = array();
                    $li_children = $child_def->childNodes;
                    foreach ($li_children as $child)
                    { 
                        if ($child->nodeName == "ul" || $child->nodeName == "ol" || $child->nodeName == "dl")
                        { 
                            break;
                        }
                        elseif ($child->nodeName == "span" && $processing_tags)
                        {
                            $tag = trim(utf8_decode($child->textContent));
                            if($tag == ")")
                            {
                            	$processing_tags = false;
                            }
                            $tags .= $tag;
                        }
                        else 
                        {
                            $definition .= " " . utf8_decode($child->textContent);            
                        }
                    }

                    if ($tags)
                    {
                    	$tags_string = trim($tags, "()");
                    	$tag_array = split(",", $tags_string);
                    	foreach($tag_array as $tag)
                    	{
                    		$definition_tags_array[] = trim($tag);
                    	}
                    }
                    
                    $definition = trim($definition);
                    if (strlen($definition) > 0 
                    &&  substr_count( $definition, 'definizione mancante') == 0)
                    {
                        $definitions_array[$i]["DEFINITION"] = trim(preg_replace('/\s+/', ' ', $definition));
                        $definitions_array[$i]["DEFINITION_SHORT"] = substr(trim(preg_replace('/\s+/', '', $definition)), 0, 10);
                        $definitions_array[$i]["TAGS"] = $tags;
                        $definitions_array[$i]["TAGS_ARRAY"] = $definition_tags_array;
                        $definitions_array[$i]["SOURCE_NAME"] = $this->service_name;
                        $definitions_array[$i]["SOURCE_URL"] = $this->service_endpoint;
                        $i = $i + 1;
                    }
                }
            }            
        }
        
        return $definitions_array;
    }
  
    
    
    //////// parse html links that contain word definitions
    // e.g. /html/body/div/div/div/ul/li/a
    private function get_more_words_from_links($links)
    {
        $more_words = array();
        if ($links->length > 0)
        {   
            foreach ($links as $link_node)
            {
                $word = trim($link_node->textContent);
                if (!strpos($word,' '))
                {
                    $more_words[] = utf8_decode($word);
                }
            }
        }
        
        // remove unwanted words
        $more_words = array_diff($more_words, array("Entra", "Registrati"));
        $more_words = array_unique($more_words);
        
        return $more_words;
    }
    
    
  
    //////// check HTML for errors from WIKTIONARY
    function wiktionary_error()
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

}
