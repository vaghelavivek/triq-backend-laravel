<?php

use Illuminate\Support\Facades\Config;

/**
 * Get constants from constants file
 */
if (!function_exists('getConst')) {
    function getConst($key = '')
    {
        if (trim($key == '')) {
            return 0;
        } else {
            return Config::get('constants.' . $key);
        }
    }
}

/**
 * Format API success response
 */
if (!function_exists('sendResponse')) {
    function sendResponse($result, $message)
    {
    	$response = [
            'status' => true,
            'data'    => $result,
            'message' => $message,
        ];


        return response()->json($response, 200);
    }
}

/**
 * Format API error response
 */
if (!function_exists('sendError')) {
    function sendError($error, $errorMessages = [], $code = 404)
    {
    	$response = [
            'status' => false,
            'message' => $error,
        ];


        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }
}