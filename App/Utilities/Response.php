<?php

namespace App\Utilities;

class Response
{
    public static function respond($data, $statusCode = 200)
    {
        header("HTTP/1.1 $statusCode");
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
