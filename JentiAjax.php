<?php

// Copyright 2015 - NINETY-DEGREES

/**
 * Return ajax response in JSON format.
 * 
 * @param array $data the data to be included in response.
 * @return data encoded in json format
 */
function ajax_json_response($data)
{
    return json_encode($data);
}

/**
 * Return ajax error response.
 * 
 * @param string $error the error message.
 * @param array $data record where error must be embedded.
 * @return type
 */
function ajax_json_response_error($error, $data=null)
{
    $response = array("ERROR" => $error);
    if ($data)
    {
        $response = array_merge($response, $data);
    }
    return json_encode($response);
}

function ajax_error_handler($errno, $errstr, $errfile, $errline)
{
    if ($errno == E_DEPRECATED)
    {
        // ignore deprecated errors
        return;
    }
        
    header('HTTP/1.1 500 ['.$errno.'] '.$errstr.' in '.$errfile.' at line '.$errline);
    header('Content-Type: application/json; charset=UTF-8');
    die();
}


?>