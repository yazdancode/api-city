<?php

namespace App\Utilities;

class Response
{
    /**
     * ارسال پاسخ JSON و پایان اجرای اسکریپت
     *
     * @param mixed $data
     * @param int $statusCode
     * @return void
     */
    public static function respond($data, int $statusCode = Httpstatus::HTTP_OK): void
    {
        self::setHeaders($statusCode);

        $response = [
            'http_status' => $statusCode,
            'http_message' => Httpstatus::STATUS_TEXTS[$statusCode] ?? 'Unknown Status',
            'data' => $data,
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * معادل respond ولی برای سازگاری با کدهای قبلی
     *
     * @param mixed $data
     * @param int $statusCode
     * @return void
     */
    public static function respondAndDie($data, int $statusCode = Httpstatus::HTTP_OK): void
    {
        self::respond($data, $statusCode);
    }

    /**
     * تنظیم هدرهای HTTP
     *
     * @param int $statusCode
     * @param array $customHeaders
     * @return void
     */
    public static function setHeaders(int $statusCode, array $customHeaders = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Max-Age: 3600');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $statusText = Httpstatus::STATUS_TEXTS[$statusCode] ?? 'Unknown Status';
        header("HTTP/1.1 $statusCode $statusText");

        foreach ($customHeaders as $key => $value) {
            header("$key: $value");
        }
    }
}
