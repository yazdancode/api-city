<?php

// بارگذاری فایل‌های مورد نیاز (autoload یا تعریفات سرویس‌ها)
include_once "../../../loader.php";

use App\Services\CityService;
use App\Utilities\Response;
use App\Utilities\Httpstatus;

// اگر از طریق CLI اجرا شود
if (php_sapi_name() === 'cli') {
    echo "City endpoint is here\n";
    exit;
}

// گرفتن متد درخواست
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestBody = json_decode(file_get_contents('php://input'), true);

$cityService = new CityService();

try {
    switch ($requestMethod) {

        case 'POST':
            if (!isValidCity($requestBody)) {
                Response::respondAndDie(['error' => 'اطلاعات شهر نامعتبر است.'], Httpstatus::HTTP_NOT_ACCEPTABLE);
            }

            $createdCity = $cityService->createCity($requestBody);
            Response::respondAndDie($createdCity, Httpstatus::HTTP_CREATED);
            break;

        case 'GET':
            $provinceId = $_GET['province_id'] ?? null;

            if (!is_null($provinceId) && !is_numeric($provinceId)) {
                Response::respondAndDie([
                    'status' => 'error',
                    'message' => 'شناسه استان باید عددی باشد.'
                ], Httpstatus::HTTP_BAD_REQUEST);
            }

            $requestData = (object)[
                'province_id' => $provinceId !== null ? (int)$provinceId : null
            ];

            $cities = $cityService->getCities($requestData);

            if (empty($cities)) {
                Response::respondAndDie([
                    'status' => 'error',
                    'message' => 'هیچ شهری یافت نشد.'
                ], Httpstatus::HTTP_NOT_FOUND);
            }

            Response::respondAndDie([
                'status' => 'success',
                'data' => $cities
            ], Httpstatus::HTTP_OK);
            break;

        case 'PUT':
        case 'DELETE':
            Response::respondAndDie([
                'status' => 'error',
                'message' => "عملیات {$requestMethod} هنوز پیاده‌سازی نشده است."
            ], Httpstatus::HTTP_NOT_IMPLEMENTED);
            break;

        default:
            Response::respondAndDie([
                'status' => 'error',
                'message' => 'متد درخواست نامعتبر است.'
            ], Httpstatus::HTTP_METHOD_NOT_ALLOWED);
    }

} catch (Exception $e) {
    Response::respondAndDie([
        'status' => 'error',
        'message' => 'خطای سرور: ' . $e->getMessage()
    ], Httpstatus::HTTP_INTERNAL_SERVER_ERROR);
}
