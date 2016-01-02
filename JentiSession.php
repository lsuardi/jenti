<?php

// Copyright 2015 - NINETY-DEGREES

require_once "JentiUser.php";

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
    private $long_duration;
        
    // user info
    private $score;
    
    /**
     * Create jenti session.
     *
     * @param array $config the configuration info
     */
    function __construct($config=null) 
    {
//        session_start();
        
        $this->config = $config;
        $this->duration = time() + 60*60; // 1 hour
        $this->long_duration = time() + 365*24*60*60; // 1 year
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
        $user = new JentiUser($this->config);
        $user_info = $user->validate_user($user_array);
        if (!$user->error)
        {
            // set user cookies
            setcookie(COOKIE_EMAIL, $email, $this->long_duration, "/"); 
            setcookie(COOKIE_NAME, $user_info["NAME"], $this->long_duration, "/"); 
        }
        
        $this->error = $user->error;
    }
    
    /**
     * Ends session and unsets email and password session variables.
     *
     */
    public function logout()
    {
        setcookie(COOKIE_EMAIL, '', time()-42000, "/");
        //unset($_SESSION['EMAIL']);             
        //session_destroy();
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
    }

    /**
     * Save user feedback.
     * 
     * @param array $feedback_info the feedback information
     */
    public function save_user_feedback($feedback_info)
    {
        $user = new JentiUser($this->config);
        if (!$user->error)
        {
            $feedback_info["TYPE"] = ACTIVITY_FEEDBACK;
            //TODO $feedback_info["EMAIL"] = $this->email;
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
        if ($this->is_user_authenticated())
        {
            // initialize from database
            $user_mgr = new JentiUser($this->config);
            //TODO $user_mgr->get_user($email);
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
        $accept_languages_array = split(",", $accept_languages_csv);
        $supported_language = array_intersect(
            $accept_languages_array, $this->config["supported_language_codes"]);
        $language = count($supported_language) 
                  ? array_pop(array_values($supported_language))
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
