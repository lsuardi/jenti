<?php

// Copyright 2015 - NINETY-DEGREES

require_once "JentiUser.php";
require_once "JentiWord.php";

// jenti session cookies
define ( "COOKIE_LANGUAGE_CODE",    "jenti_language_code");
define ( "COOKIE_EMAIL",            "jenti_email");
define ( "COOKIE_NAME",             "jenti_name");
define ( "COOKIE_SCORE",            "jenti_score");
define ( "COOKIE_SKIN",             "jenti_skin");

// user activity types
define("ACTIVITY_START",            "start");
define("ACTIVITY_FEEDBACK",         "feedback");

class JentiSession
{  
    // session preferences
    public $language_code;
    public $skin;

    // error
    public $error;
    
    // catalog of display strings in selected user language
    public $catalog;
    
    // config information
    private $config;
    
    // session duration
    private $duration;
        
    // user
    private $user_mgr;
    private $user;
    private $score;
    private $email;
    private $name;

    /**
     * Create jenti session.
     *
     * @param array $config the configuration info
     */
    function __construct($config=null) 
    {
        $this->config = $config;
//        $this->duration = time() + 60*60; // 1 hour
        $this->duration = time() + 60*5; // 5 minutes
        $this->user_mgr = null;
        $this->init();
    }
    
    /**
     * Validate database user.
     *
     * @param string $email user email
     * @param string $password user password
     */
    public function login($email, $password)
    {
        $this->error = null;
    
        $user_array["EMAIL"] = $email;
        $user_array["PASSWORD"] = $password;
        $user_mgr = new JentiUser($this->config);
        $user_info = $user_mgr->validate_user($user_array);
        if (!$user_mgr->error)
        {
            // set user cookies
            setcookie(COOKIE_EMAIL, $email, $this->duration, "/"); 
            setcookie(COOKIE_NAME, $user_info["NAME"], $this->duration, "/"); 
            setcookie(COOKIE_SCORE, $user_info["SCORE"], $this->duration, "/"); 
        }
        
        $this->error = $user_mgr->error;
    }
    
    /**
     * Ends session and unsets email and password session variables.
     *
     */
    public function logout()
    {
        setcookie(COOKIE_EMAIL, '', time()-42000, "/");
    }
    
    /**
     * Check if user is logged in.
     * 
     * @return int true if user is logged in, false otherwise
     */
    public function is_user_authenticated()
    { 
      if (!isset($_COOKIE[COOKIE_EMAIL]) || empty($_COOKIE[COOKIE_EMAIL]))
      {
        return FALSE;
      }
      return TRUE;
    }
    
    /**
     * Save guess information.
     * 
     * @param array $guess_info the guess information
     */
    public function save_guess($guess_info)
    {
        $score = $guess_info["SCORE"] > 0 ? $guess_info["SCORE"] : 0;
        $new_score = $this->score + $score;
        setcookie(COOKIE_SCORE, $new_score, $this->duration, "/");
        
        if ($this->is_user_authenticated())
        {
            // save to database            
            $this->user["SCORE"] = $new_score;
            $this->user_mgr = new JentiUser($this->config);
            $this->user_mgr->update_user($this->user);
            
            $this->error = $this->user_mgr->error;
        }
    }

    /**
     * Save user feedback.
     * 
     * @param array $feedback_info the feedback information
     */
    public function save_user_feedback($feedback_info)
    {
        if(isset($feedback_info["LIKE"]))
        {
            $word = new JentiWord($this->config);
            if(!$word->error)
            {
                $word->update_likes($feedback_info);
            }
            $this->error = $word->error;
        }
        
        $user = new JentiUser($this->config);
        if (!$user->error)
        {
            $feedback_info["TYPE"] = ACTIVITY_FEEDBACK;
            if ($this->is_user_authenticated())
            {
                $feedback_info["EMAIL"] = $this->email;
            }
            $user->add_user_activity($feedback_info);
        }

        $this->error = $user->error;
    }

    /**
     * Save user info.
     * 
     * @param array $user_info the user information
     */
    public function save_user_info($user_info)
    {
        $user = new JentiUser($this->config);
        if (!$user->error)
        {
            $user->add_user($user_info);
        }

        $this->error = $user->error;
    }

    /**
     * Save user start.
     */
    public function save_user_start()
    {
        if (!isset($_COOKIE[COOKIE_LANGUAGE_CODE]))
        {
            $user = new JentiUser($this->config);
            if (!$user->error)
            {
                $start_info["TYPE"] = ACTIVITY_START;
                //TODO $feedback_info["EMAIL"] = $this->email;
                $user->add_user_activity($start_info);
            }

            $this->error = $user->error;
        }
    }
    
    /**
     * Switch to next skin.
     */
    public function change_skin()
    { 
        $skin_index = array_search($this->skin, $this->config["skins"]);
        $next_skin_index    = ($skin_index < count($this->config["skins"]) - 1)
                            ? $skin_index + 1
                            : 0
                            ;
        
        $this->skin = $this->config["skins"][$next_skin_index];
        setcookie(COOKIE_SKIN, $this->skin, $this->duration, "/");         
    }

    /**
     * Initialize session.
     */
    private function init()
    {   global $catalog, $config;
    
        // initialize session
        $this->language_code = $this->init_from_cookie(COOKIE_LANGUAGE_CODE, $this->get_accept_language());
        $this->skin = $this->init_from_cookie(COOKIE_SKIN, $this->config["default_skin"]);
        $this->score = $this->init_from_cookie(COOKIE_SCORE, 0);
        $this->name = $this->init_from_cookie(COOKIE_NAME, null);
        $this->email = $this->init_from_cookie(COOKIE_EMAIL, null);
        if ($this->is_user_authenticated())
        {
            // initialize user session from database
            $this->user_mgr = new JentiUser($this->config);
            $this->user = $this->user_mgr->get_user($this->email);

            // override cookie when session available
            $this->score = $this->user["SCORE"];
        }
        
        // load $catalog from file
        $catalog_path = $config["docroot"] . "/lang/{$this->language_code}.php";
        require_once($catalog_path);
        
        $this->catalog = $catalog;
    }
    
    /**
     * Derive language code from Accept-Language header from browser.
     * 
     * @param string $accept_language Accept-Language header from browser
     */
    private function get_accept_language()
    {
        //$request_headers = getallheaders();
        $accept_language = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
        $len    = strpos($accept_language, ";") 
                ? strpos($accept_language, ";") 
                : strlen($accept_language)
                ;
        $accept_languages_csv = substr($accept_language, 0, $len);
        $accept_languages_array = explode(",", $accept_languages_csv);
        $supported_language = array_intersect(
            $accept_languages_array, $this->config["supported_language_codes"]);
        $supported_language_values = array_values($supported_language);
        $language = count($supported_language) 
                  ? array_pop($supported_language_values)
                  : $this->config["default_language_code"]
                  ;
        
        return $language;
    }
    
    /**
     * Retrieve a cookie value and update cookie timestamp.
     *
     * @param string $cookie_name the name of the cookie
     * @param string $default_value the default value for the cookie
     * @return string the cookie value
     */
    private function init_from_cookie($cookie_name, $default_value)
    {
        $value  = isset($_COOKIE[$cookie_name])
                ? $_COOKIE[$cookie_name]
                : $default_value
                ;
        
        setcookie($cookie_name, $value, $this->duration, "/");
        
        return $value;
    }
}
