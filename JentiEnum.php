<?php

// Copyright 2015 - NINETY-DEGREES

require_once "dbedit/DBEManagerSql.1.1.php";

class JentiEnum
extends ManagerSql
{
    // default language
    private $default_language_code;
    
    // database table
    private $table_name = "ENUM";
    
    // table data
    public $data = array();

    function __construct($args=null)
    { 
      parent::__construct($args);
      if (!$this->error)
      {
          $this->get_enum_data($args);
      }
      
      $this->default_language_code  = isset($args["default_language_code"])
                                    ? $args["default_language_code"]
                                    : "it"
                                    ;
    }

    /**
     * Get an enumeration values array.
     * 
     * @param type $enum_name the enumeration name
     * @return array the enum values array
     */
    public function get_enum($enum_name)
    {
        return ($this->data[$enum_name]);
    }

    /**
     * Check if enumeration value exists.
     * 
     * @param type $enum_name the enumeration name
     * @return array the enum values array
     */
    public function value_exists($enum_name, $enum_value)
    {
        return (array_search($enum_value, $this->data[$enum_name]) !== false);
    }
    
    /**
     * Get enum data from the database.
     *
     * @return array the enumerations data
     */
    private function get_enum_data($args=null)
    {
        $language_code  = isset($args["LANGUAGE_CODE"]) 
                        ? $args["LANGUAGE_CODE"] 
                        : $this->default_language_code
                        ;

        $sql    = "SELECT NAME, SEQUENCE, VALUE FROM {$this->table_name} "
                . "WHERE LANGUAGE_CODE = '{$language_code}' "
                . "ORDER BY NAME, SEQUENCE";
                
        $query_result = $this->query($sql);
        if ($query_result)
        {
            while ($row = $this->get_row_assoc($query_result))
            {
                $enum_name = $row["NAME"];
                $this->data[$enum_name][] = $row["VALUE"];
            }
        }
        
        return $query_result;
    }

}



