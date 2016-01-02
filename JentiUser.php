<?php

// Copyright 2015 - NINETY-DEGREES

require_once "dbedit/DBEManagerSql.1.1.php";

class JentiUser
extends ManagerSql
{
    // database tables
    private $table_user = "USER";
    private $table_user_word = "USER_WORD";
    private $table_user_activity = "USER_ACTIVITY";
    
    function __construct($config=null) 
    {
        parent::__construct($config);
    }
    
    /**
     * Inserts new user in database.
     *
     * @param array $user_info user data
     */
    public function add_user($user_info)
    {
        if (!isset($user_info["NAME"]))
        {
            $pos = strpos($user_info["EMAIL"], "@");
            $user_info["NAME"] = 
                $pos === FALSE 
                ? $user_info["EMAIL"] 
                : substr($user_info["EMAIL"], 0 , $pos)
                ;
        }

        $this->query_insert($this->table_user, $user_info);
    }
    
    /**
     * Inserts new user activity in database.
     *
     * @param array $activity_info user activity data
     */
    public function add_user_activity($activity_info)
    {
        $activity_info["HTTP_USER_AGENT"] = $_SERVER["HTTP_USER_AGENT"];
        $activity_info["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];
        
        $this->query_insert($this->table_user_activity, $activity_info);
    }
    
    /**
     * Check if user exists in USER table.
     *
     * @param string $user_info user data
     * @return array user record or empty array
     */
    public function validate_user($user_info)
    {
        $email = $user_info["EMAIL"];
        $password = $user_info["PASSWORD"];
        $sql = "SELECT * FROM {$this->table_user} "
             . "WHERE EMAIL = '{$email}' "
             . "AND PASSWORD = '{$password}' "
             ;

        $user_info = null;
        $user_array = $this->query_all($sql);
        if (!$this->error)
        {
            if (sizeof($user_array) == 0)
            {
                //TODO use catalog
                $this->error = "User is not registered.";
            }
            else
            {
                $user_info = $user_array[0];
                /* TODO
                    if (!$user_info["VERIFIED"])
                    {
                        //TODO use catalog
                        $this->error = "User email is not confirmed.";
                    }
                */      
            }
        }

        return $user_info;
    }
    
    /**
     * Get user.
     *
     * @param string $email user email
     * @return array user record or null
     */
    public function get_user($email)
    {
        $sql = "SELECT * FROM {$this->table_user} "
             . "WHERE EMAIL = '{$email}' "
             ;

        $user_array = $this->query_all($sql);
        if ($this->error)
        {
            return null;
        }
        if (sizeof($user_array) == 0)
        {
            //TODO use catalog
            $this->error = "User not found.";
            return null;
        }
        
        return $user_array[0];
    }

}
?>