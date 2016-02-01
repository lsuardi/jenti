<?php

// Copyright 2006-2015 - NINETY-DEGREES



///////////////////////////////////////////////////////////////////
// abstract class for HTTP requests to service endpoints (URLs)
///////////////////////////////////////////////////////////////////
class JentiRequest
{ public $service_endpoint;
  public $service_name;
  public $search_query;
  public $dom;
  public $xpath;
  public $meta_tags;
  public $refresh_interval;
  public $retries;
  public $header;
  public $msg;
  public $error;
  public $debug;
  public $cache_on;
  public $cache_path;
  public $cache_date;
  public $url;
  public $wait;



  function __construct( $args=null)
  {
    $this->refresh_interval  = isset($args["refresh_interval"])
                             ? $args["refresh_interval"]
                             : 10
                             ; 
    $this->msg               = ""; 
    $this->header            = NULL; 
    $this->retries           = 2;
    $this->debug             = isset($args["debug"])
                             ? $args["debug"]
                             : FALSE
                             ;
    $this->cache_on          = isset($args["cache_on"])
                             ? $args["cache_on"]
                             : FALSE
                             ;
    $this->cache_root        = isset($args["cache_root"])
                             ? $args["cache_root"]
                             : NULL
                             ;
    $this->cache_path        = NULL;
    $this->cache_date        = NULL;
    $this->wait              = isset($args["wait"])
                             ? $args["wait"]
                             : 0
                             ;
    $this->rewrite           = isset($args["rewrite"])
                             ? $args["rewrite"]
                             : NULL
                             ;
  }


  
  //////// get a page HTML
  function get_web_page( $url)
  { global $php_errormsg;
  
    $this->url = $url;

    // check cache
    $this->cache_path = $this->path_to_cached_page( $url);
    if (($this->cache_on == TRUE && $this->cache_age( $this->cache_path) >= $this->refresh_interval)
    ||  ($this->cache_on == FALSE))
    {      
      // delay queries to web site
      if ($this->wait > 0)
      { sleep( rand( 1, $this->wait));
      }      

      // if necessary rewrite the URL to handle redirects
      $url_info = parse_url( $url);
      if (isset( $this->rewrite[$url_info["host"]]))
      { $url = $this->rewrite[$url_info["host"]];
      }
        
      // if necessary add HTTP headers
      $context = NULL;
      if (isset( $this->header))
      { $opts = array( 'http' => array( 'header' => $this->header));
        $context = stream_context_create( $opts);
      }

      // try few times in case of HTTP request failure
      // failed to open stream: HTTP request failed!
      for ($i=0; $i<=$this->retries; $i++)
      { $this->debug_echo($url);
        $html = @file_get_contents( $url, FALSE, $context);
        if (!$html && substr_count( $php_errormsg, "HTTP request failed!"))
        { continue;
        }
        break;
      }
      if (!$html)
      { $this->error = get_class($this) . ": cannot file_get_contents({$url}) " . trim( $php_errormsg);
        return( FALSE);
      }

      // save to cache 
      // &&  !@mkdir( dirname( $this->cache_path), 0777, true))
      if ($this->cache_on == TRUE)
      {
        if (!file_exists( dirname( $this->cache_path)) 
        &&  !$this->nd_mkdir( dirname( $this->cache_path), $this->error))
        { return( FALSE);
        }
        if (!@file_put_contents( $this->cache_path, $html))
        { $this->error = $php_errormsg; 
          return( FALSE);
        }
      }
      $this->msg = "HTTP [$this->wait]";
    }
    else
    {
      $html = @file_get_contents( $this->cache_path);
      $this->msg = "HTTP (". $this->cache_age( $this->cache_path) .")";
      $this->debug_echo($this->cache_path);
    }

    // convert HTML to DOM
    $this->dom = new DOMDocument();
    $res = @$this->dom->loadHTML( $html);
    if (!$res)
    { $this->error = $php_errormsg;
      return( FALSE);
    }

    // make XPATH available
    $this->xpath = new DOMXPath( $this->dom);

    if ($this->cache_on == TRUE)
    { $this->cache_date = date( "Ymd", @filemtime( $this->cache_path));
    }

    return( TRUE);
    
  }// get_web_page()


  
  //////// get meta tags from web page HTML
  function get_meta_tags( $url)
  { global $catalog;
 
    if (!$this->xpath)
    {
      // get cached data if any
      $this->cache_path = $this->path_to_cached_page( $url);
      $html = @file_get_contents( $this->cache_path);
      if (!$html)
      { return( array());
      }
      // convert HTML to DOM
      $this->dom = new DOMDocument();
      $res = @$this->dom->loadHTML( $html);
      if (!$res)
      { $this->error = $php_errormsg;
        return( array());
      }

      // make XPATH available
      $this->xpath = new DOMXPath( $this->dom);
    }

    $this->meta_tags = array();
    $meta_tags = $this->xpath->query( '//html/head/meta');
    for ($i=0; $i<$meta_tags->length; $i++) 
    { $name = strtolower( @$meta_tags->item($i)->attributes->getNamedItem( "name")->nodeValue);
      switch ($name)
      { case "title":
        { $this->meta_tags["title"] = 
                 array( "title", $meta_tags->item($i)->attributes
                                 ->getNamedItem( "content")->nodeValue);
          break;
        }
        case "description":
        { $this->meta_tags["description"] = 
                 array( "description", $meta_tags->item($i)->attributes
                                       ->getNamedItem( "content")->nodeValue);
          break;
        }
        case "keywords":
        { $this->meta_tags["keywords"] = 
                 array( "keywords", $meta_tags->item($i)->attributes
                                    ->getNamedItem( "content")->nodeValue);
          break;
        }
      }
    }

    $this->msg = "Acquire site data (". eco_cache_age( $this->cache_path) .")";
    
    return( $this->meta_tags);
    
  }//get_meta_tags()



  ////////////////////////////////////////////////////////////////////////////
  function path_to_cached_page( $url)
  {  

      // build path to cache
      $url_info  = parse_url( $url);
      $file_name = basename( $url_info['path']);
      if (isset($url_info['query']))
      { $file_name .= $url_info['query'];
      }
      if (strlen($file_name) == 0)
      { $file_name = "index.htm";
      }

      // invalid file paths characters /\|*:"<>?&%
      $chars = array( '/', '\\', '|', '*', ':', '"', '<', '>', '?', '&', '%');
      $file_name = str_replace( $chars, '-', $file_name);

      // add html extension if needed
      $file_parts = pathinfo( $file_name);
      if ( !isset( $file_parts["extension"])
      ||   !in_array( $file_parts["extension"], array( "htm", "html")) )
      { $file_name .= ".htm";
      }

      $folder    = "{$this->cache_root}/{$url_info['host']}" . @$url_info['path'];
      $file_path = "$folder/$file_name";

    return( $file_path);
  }
  
  ////////////////////////////////////////////////////////////////////////////
  function cache_age( $file)
  { 
      $file_time        = @filemtime( $file);
      $today_time       = time();
      $days_passed      = floor( ($today_time - $file_time) / (60 * 60 * 24));

      return( $days_passed);
  }
  
  ////////////////////////////////////////////////////////////////////////////
  function escape_chars( $str)
  { 
      return 
        preg_replace(
            array("/[']/"), 
            array("\\'"), 
            $str);
  }
  
  ////////////////////////////////////////////////////////////////////////////
  function nd_mkdir( $dirpath, &$error)
  {
    $paths = explode( '/', $dirpath);
    $create_path = $paths[0];
    for ($i=1; $i<count($paths); $i++)
    { $create_path .= "/{$paths[$i]}";
      if (!file_exists( $create_path)
      &&  !@mkdir( $create_path, 0777))
      { $error = $php_errormsg;
        return( FALSE);
      }
    }
    return( TRUE);
  }
  
  ////////////////////////////////////////////////////////////////////////////
  private function debug_echo($msg)
  {
    if ($this->debug)
    {
      echo $msg . "<BR>";
    }
  }
  
    function debug_echo_dom( $node, $level, $path, $namespace)
    { 
        if (!isset($this->dom->documentElement))
        { 
            printf( "<BR>no dom");
            return;
        }
        
        if (!$node)
        { $node = $this->dom->documentElement;
        }

        if ($namespace)
          $path = $path . "/" . str_replace( $namespace.':', '', $node->nodeName);
        else
          $path = $path . "/" . $node->nodeName;

        $child_count = ($node->firstChild != NULL)
                     ? $node->childNodes->length
                     : 0
                     ;

        $value = "";
        if ($node->nodeName == "#text")
        { $value = $node->nodeValue; 
        }

        printf( "<BR>%s [%d] = %s\n", $path, $child_count, $value);

        if ($node->hasChildNodes())
        { $children = $node->childNodes;
          foreach ($children as $node_child)
            $this->debug_echo_dom( $node_child, $level + 1, $path, $namespace);
        }
    } 

}

