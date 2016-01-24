<?php

// Copyright 2015 - NINETY-DEGREES

require_once "dbedit/DBEManagerSql.1.1.php";

class JentiWord
extends ManagerSql
{
    // aggregate multiple errors for methods
    // that execute many sql queries
    public $error_array;

    // config
    private $config;
    private $enum;

    // database tables
    private $table_word = "word";
    private $table_word_definition = "word_definition";
    private $table_word_tag = "word_tag";
    private $table_word_list = "word_list";
    private $play_table_prefix = "play";
    
    function __construct($config=null) 
    {
        parent::__construct($config);

        $this->config = $config;
    }

    /**
     * Return the next word for a playing user.
     * 
     * @param array $user_info the user data necessary to generate the next word
     * @return array $word_info the word user must guess
     */
    public function get_next_word($user_info)
    {
        // identify the table that is used for this user game
        $table_name     = $this->play_table_prefix
                        . "_" . $user_info["LANGUAGE_CODE"]
                        . (isset($user_info["TAG"])
                            ? "_" . $user_info["TAG"]
                            : "")
                        ;
        
        // the table contains as many rows as many words used to play
        $word_count = $this->get_table_row_count($table_name);
        if (!$this->error && $word_count > 0)
        {
            // next word chosen randomly among the the words in the table
            $word_index = rand(1, $word_count);
            $word_id = $this->get_next_word_id($table_name, $word_index);
            if (!$this->error)
            {
                return $this->get_word($word_id);
            }
        }        
        
        return null;
    }

    /**
     * Add word and definition to the database.
     * Use $error_array to check for errors.
     *
     * @param array $word_info the word data
     */
    public function add_word($word_info)
    {
        $this->error_array = array();

        // insert word
        $word_id = $this->add_word_word($word_info);
        if ($this->errno == 1062)
        {
            // word already in the database, get word id
            $word_id = $this->get_word_id($word_info);
        }
        
        if ($word_id > 0)
        {
            // insert word definition
            $word_info["WORD_ID"] = $word_id;
            $this->add_word_definitions($word_info);

            // insert new words
            $this->add_word_list_words($word_info);
        }
    }

    /**
     * Get a word id.
     *
     * @param array $word_info the word data
     */
    public function get_word_id($word_info)
    {
        $sql = "SELECT ID FROM {$this->table_word} "
             . "WHERE WORD = '".$word_info["WORD"]."' "
             . "AND TYPE = '".$word_info["TYPE"]."' "
             . "AND LANGUAGE_CODE = '".$word_info["LANGUAGE_CODE"]."'"
             ;
        $query_result = $this->query($sql);
        if ($query_result)
        {
            $result_assoc = $this->get_row_assoc($query_result);  
            return $result_assoc["ID"];
        }

        return 0;
    }

    /**
     * Get a word id from one of the game tables.
     *
     * @param string $table_name the name of the game table
     * @param string $word_index the word index which is the id in the game table
     */
    public function get_next_word_id($table_name, $word_index)
    {
        $sql = "SELECT WORD_ID FROM {$table_name} "
             . "WHERE ID = {$word_index} "
             ;
        $query_result = $this->query($sql);
        if ($query_result)
        {
            $result_assoc = $this->get_row_assoc($query_result);  
            return $result_assoc["WORD_ID"];
        }
        return 0;
    }

    /**
     * Get a word data.
     *
     * @param string $word_id the id of the needed word
     */
    public function get_word($word_id)
    {
        $sql = "SELECT * FROM {$this->table_word} WO, {$this->table_word_definition} WD "
             . "WHERE WO.ID = {$word_id} "
             . "AND WD.WORD_ID = WO.ID "
             . "ORDER BY WD.ID"
             ;
        $query_result = $this->query($sql);
        if ($query_result)
        {
            $result_assoc = $this->get_row_assoc($query_result);
            
            // build more definitions array
            $more_definitions = array();
            for ($i=1; $i<$this->get_num_rows($query_result); $i++)
            {
                $row = $this->get_row_assoc($query_result);
                $more_definitions[] = array(
                    "DEFINITION" => $row["DEFINITION"],
                    "TAGS" => $row["TAGS"],
                    "SOURCE_NAME" => $row["SOURCE_NAME"]);
            }
            $result_assoc["MORE_DEFINITIONS"] = $more_definitions;
            
            return $result_assoc;
        }
        return null;
    }

    /**
     * Get a list of words to be acquired from source.
     *
     * @param object $request source specific request object
     */
    public function get_words_without_definition($request)
    {
        $sql = "SELECT WORD FROM {$this->table_word_list} WL "
             . "WHERE WL.LANGUAGE_CODE = '{$request->language_code}' "
             . "AND (WL.AVOID_SOURCES IS NULL OR WL.AVOID_SOURCES NOT LIKE '%({$request->service_name})%') "
             . "AND NOT EXISTS "
             . "(SELECT WORD FROM WORD WO, WORD_DEFINITION WD "
             . " WHERE WD.SOURCE_NAME = '{$request->service_name}' "
             . " AND WD.WORD_ID = WO.ID "
             . " AND WO.WORD = WL.WORD)"
             ;

        $word_array = array();
        $query_result = $this->query($sql);
        if ($query_result)
        {
            for ($i=0; $i<$this->get_num_rows($query_result); $i++)
            {
                $word_array = $this->get_row_array($query_result);
            }
        }

        return $word_array;
    }

    /**
     * Update word list to indicate sources to avoid for a word.
     *
     * @param array $word_info the word information
     */
    public function update_word_list_word($word_info)
    {
        $sql = "UPDATE {$this->table_word_list} "
             . "SET AVOID_SOURCES = CONCAT_WS('', AVOID_SOURCES, '(".$word_info["SOURCE_NAME"].")') "
             . "WHERE WORD = '{$word_info["WORD"]}' "
             . "AND LANGUAGE_CODE = '{$word_info["LANGUAGE_CODE"]}' "
             . "AND (AVOID_SOURCES IS NULL OR AVOID_SOURCES NOT LIKE '%(".$word_info["SOURCE_NAME"].")%') "
             ;
             
        return $this->query($sql);
    }
    
    /**
     * Update likes -> increment word likes and word definition likes by 1
     *
     * @param array $word_info the word information
     */
    public function update_likes($word_info)
    {
        $this->update_word_likes($word_info);
        $this->update_word_definition_likes($word_info);
    }

    /**
     * Add word tags to the database.
     *
     * @param array $definition_info the word definition data
     */
    private function add_word_definition_tags($definition_info, $language_code)
    {
        $tags_array = $definition_info["TAGS_ARRAY"];
        foreach ($tags_array as $tag)
        {
            $tag_data["TAG"] = $tag;
            $tag_data["LANGUAGE_CODE"] = $language_code;
            $this->query_insert($this->table_word_tag, $tag_data);
            if ($this->error)
            {
                $this->error_array[] = $this->error;
            }
        }
    }
    
    /**
     * Update word definition likes -> increment definition likes by 1
     *
     * @param array $word_info the word information
     */
    private function update_word_definition_likes($word_info)
    {
        $sql = "UPDATE {$this->table_word_definition} "
             . "SET LIKES = LIKES + 1 "
             . "WHERE WORD_ID = '{$word_info["WORD_ID"]}' "
             . "AND ID = '{$word_info["DEFINITION_ID"]}' "
             ;
             
        return $this->query($sql);
    }
    
    /**
     * Update word likes -> increment word likes by 1
     *
     * @param array $word_info the word information
     */
    private function update_word_likes($word_info)
    {
        $sql = "UPDATE {$this->table_word} "
             . "SET LIKES = LIKES + 1 "
             . "WHERE ID = '{$word_info["WORD_ID"]}' "
             ;
             
        return $this->query($sql);
    }

    /**
     * Add new words to word list.
     *
     * @param array $word_info the word data
     */
    private function add_word_list_words($word_info)
    {
        if (isset($word_info["MORE_WORDS"]))
        {
            foreach ($word_info["MORE_WORDS"] as $word)
            {
                $word_list_info["WORD"] = $word;
                $word_list_info["LANGUAGE_CODE"] = $word_info["LANGUAGE_CODE"];
                $this->query_insert($this->table_word_list, $word_list_info);
                if ($this->error)
                {
                    $this->error_array[] = $this->error;
                }
            }            
        }
    }
    
    /**
     * Add word to the database.
     *
     * @param array $word_info the word data
     * @return int the id of the inserted or existing word
     */
    private function add_word_word($word_info)
    {
        $word_id = 0;

        if ($this->validate_word_data($word_info))
        {
            // insert word
            $query_result = $this->query_insert($this->table_word, $word_info);
            if ($query_result)
            {
                // need id of word just inserted
                $word_id = $this->get_insert_id();
            }
            else
            {
                $this->error_array[] = $this->error;                
            }
        }
        
        return $word_id;
    }

    /**
     * Add word definitions to the database.
     *
     * @param array $word_info the word data
     */
    private function add_word_definitions($word_info)
    {
        if (isset($word_info["WORD_ID"]))
        {
            $definition_array = $word_info["DEFINITION_ARRAY"];
            foreach ($definition_array as $definition_info)
            {
                $definition_info["WORD_ID"] = $word_info["WORD_ID"];
                $this->query_insert($this->table_word_definition, $definition_info);
                if ($this->error)
                {
                    $this->error_array[] = $this->error;
                }

                // tags
                $this->add_word_definition_tags($definition_info, $word_info["LANGUAGE_CODE"]);
            }
        }
        else
        {
            $this->error_array[] = get_class($this) 
                . " ERROR: WORD_ID is required to add word definitions.";            
        }
    }

    /**
     * Validate word data.
     *
     * @param array $word_info the word data
     * @return boolean 
     */
    private function validate_word_data($word_info)
    {
        // check that values exist in enumerations
        // TODO we do not need to check enums for now...
        //if (!$this->enum)
        //{
        //    $this->enum = new JentiEnum(array_merge($this->config, $word_info));
        //}
        if (array_search($word_info["LANGUAGE_CODE"], $this->config["supported_language_codes"]) === FALSE)
        {
            $this->error_array[] = get_class($this) 
                . " ERROR : Invalid word data"
                . " LANGUAGE_CODE={$word_info["LANGUAGE_CODE"]}";
      
            return false;
        }
        return true;
    }

}



