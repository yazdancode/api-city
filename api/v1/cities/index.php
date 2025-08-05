<?php

include_once "../../../loader.php";

use App\Services\CityService;
use App\Utilities\Response;
use App\Utilities\Httpstatus;

if (php_sapi_name() === 'cli') {
    echo "City endpoint is here\n";
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestBody = json_decode(file_get_contents('php://input'), true);
$cityService = new CityService();

try {
    switch ($requestMethod) {

        case 'POST':
            if (!isValidCity($requestBody)) {
                Response::respondAndDie(
                    ['error' => 'اطلاعات شهر نامعتبر است.'],
                    Httpstatus::HTTP_NOT_ACCEPTABLE
                );
            }

            $createdCity = $cityService->createCity($requestBody);
            Response::respondAndDie($createdCity, Httpstatus::HTTP_CREATED);
            break;

        case 'GET':
            $provinceId = $_GET['province_id'] ?? null;

            if ($provinceId !== null && !is_numeric($provinceId)) {
                Response::respondAndDie([
                    'status' => 'error',
                    'message' => 'شناسه استان باید عددی باشد.'
                ], Httpstatus::HTTP_BAD_REQUEST);
            }

            $cities = $cityService->getCities((object)['province_id' => $provinceId ? (int)$provinceId : null]);

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
            if (
                !isset($requestBody['city_id']) || !is_numeric($requestBody['city_id']) ||
                !isset($requestBody['name']) || empty(trim($requestBody['name']))
            ) {
                Response::respondAndDie([
                    'status' => 'error',
                    'message' => 'شناسه یا نام جدید شهر نامعتبر است.'
                ], Httpstatus::HTTP_BAD_REQUEST);
            }

            $updateResult = $cityService->updateCityName((int)$requestBody['city_id'], trim($requestBody['name']));

            if (!$updateResult) {
                Response::respondAndDie([
                    'status' => 'error',
                    'message' => 'شهر با این شناسه یافت نشد یا خطا در بروزرسانی.'
                ], Httpstatus::HTTP_NOT_FOUND);
            }

            Response::respondAndDie([
                'status' => 'success',
                'message' => 'نام شهر با موفقیت بروزرسانی شد.'
            ], Httpstatus::HTTP_OK);
            break;

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
