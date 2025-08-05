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


function errorResponse(string $message, int $statusCode): void
{
    Response::respondAndDie(['status' => 'error', 'message' => $message], $statusCode);
}

try {
    switch ($requestMethod) {
        case 'POST':
            if (!isValidCity($requestBody)) {
                errorResponse('اطلاعات شهر نامعتبر است.', Httpstatus::HTTP_NOT_ACCEPTABLE);
            }

            $createdCity = $cityService->createCity($requestBody);
            Response::respondAndDie($createdCity, Httpstatus::HTTP_CREATED);
            break;

        case 'GET':
            $provinceId = $_GET['province_id'] ?? null;
            if ($provinceId !== null && !is_numeric($provinceId)) {
                errorResponse('شناسه استان باید عددی باشد.', Httpstatus::HTTP_BAD_REQUEST);
            }

            $cities = $cityService->getCities((object)['province_id' => $provinceId ? (int)$provinceId : null]);

            if (empty($cities)) {
                errorResponse('هیچ شهری یافت نشد.', Httpstatus::HTTP_NOT_FOUND);
            }

            Response::respondAndDie(['status' => 'success', 'data' => $cities]);
            break;

        case 'PUT':
            $cityId = $requestBody['city_id'] ?? null;
            $cityName = $requestBody['name'] ?? '';

            if (!is_numeric($cityId) || empty(trim($cityName))) {
                errorResponse('شناسه یا نام جدید شهر نامعتبر است.', Httpstatus::HTTP_BAD_REQUEST);
            }

            $updateResult = $cityService->updateCityName((int)$cityId, trim($cityName));

            if (!$updateResult) {
                errorResponse('شهر با این شناسه یافت نشد یا خطا در بروزرسانی.', Httpstatus::HTTP_NOT_FOUND);
            }

            Response::respondAndDie([
                'status' => 'success',
                'message' => 'نام شهر با موفقیت بروزرسانی شد.'
            ]);
            break;

        case 'DELETE':
            $cityId = $_GET['city_id'] ?? null;

            if (!is_numeric($cityId)) {
                errorResponse('شناسه شهر نامعتبر است.', Httpstatus::HTTP_BAD_REQUEST);
            }

            $resultDelete = $cityService->deleteCity((int)$cityId);

            if (!$resultDelete) {
                errorResponse('شهر با این شناسه یافت نشد یا حذف انجام نشد.', Httpstatus::HTTP_NOT_FOUND);
            }

            Response::respondAndDie([
                'status' => 'success',
                'message' => 'شهر با موفقیت حذف شد.'
            ]);
            break;

        default:
            errorResponse('متد درخواست نامعتبر است.', Httpstatus::HTTP_METHOD_NOT_ALLOWED);
    }

} catch (Exception $e) {
    errorResponse('خطای سرور: ' . $e->getMessage(), Httpstatus::HTTP_INTERNAL_SERVER_ERROR);
}
