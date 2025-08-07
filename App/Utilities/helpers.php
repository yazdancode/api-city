<?php

use App\Utilities\Httpstatus;


if (!function_exists('errorResponse')) {
    function errorResponse($message, $code = Httpstatus::HTTP_OK)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
