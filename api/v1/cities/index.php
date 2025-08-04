<?php

// بارگذاری فایل‌های مورد نیاز (autoload یا تعریفات سرویس‌ها)
include_once "../../../loader.php";

use App\Services\CityService;
use App\Utilities\Response;
use App\Utilities\Httpstatus;

// اگر اسکریپت در خط فرمان اجرا شد (CLI)، پیام نشان داده و خارج شود
if (php_sapi_name() === 'cli') {
    echo "City endpoint is here\n";
    exit;
}

// گرفتن متد درخواست (POST, GET, ...)
$request_method = $_SERVER['REQUEST_METHOD'];
// خواندن محتوای بدنه درخواست در صورت نیاز (برای PUT و POST)
$request_body = json_decode(file_get_contents('php://input'), true);

switch ($request_method) {

    // ---- POST: ساخت شهر جدید ----
    case 'POST':
        try {
            $name = $_POST['name'] ?? null;
            $province_id = $_POST['province_id'] ?? null;

            if (!$name || !$province_id) {
                throw new Exception("نام و شناسه استان الزامی هستند.");
            }

            $request_data = (object)[
                'name' => $name,
                'province_id' => $province_id
            ];

            $cityService = new CityService();
            $newCity = $cityService->createCity($request_data);

            Response::respondAndDie([
                'status' => 'success',
                'message' => 'شهر با موفقیت ثبت شد.',
                'data' => $newCity
            ], Httpstatus::HTTP_CREATED);

        } catch (Exception $e) {
            Response::respondAndDie([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Httpstatus::HTTP_BAD_REQUEST);
        }
        break;

    // ---- GET: گرفتن لیست شهرها ----
    case 'GET':
        try {
            $province_id = $_GET['province_id'] ?? null;

            if (!is_null($province_id) && !is_numeric($province_id)) {
                Response::respondAndDie([
                    'status' => 'error',
                    'message' => 'شناسه استان معتبر نیست. باید عددی باشد.'
                ], Httpstatus::HTTP_BAD_REQUEST);
            }

            $request_data = (object)[
                'province_id' => is_null($province_id) ? null : (int)$province_id
            ];

            $cityService = new CityService();
            $response = $cityService->getCities($request_data);

            Response::respondAndDie([
                'status' => 'success',
                'data' => $response
            ]);

        } catch (Exception $e) {
            Response::respondAndDie([
                'status' => 'error',
                'message' => 'خطای سرور: ' . $e->getMessage()
            ], Httpstatus::HTTP_INTERNAL_SERVER_ERROR);
        }
        break;

    // ---- PUT: ویرایش (فعلاً فقط پاسخ ثابت می‌دهد) ----
    case 'PUT':
        Response::respondAndDie(['message' => 'PUT Request']);
        break;

    // ---- DELETE: حذف (فعلاً فقط پاسخ ثابت می‌دهد) ----
    case 'DELETE':
        Response::respondAndDie(['message' => 'DELETE Request']);
        break;

    // ---- در صورت استفاده از متد نامعتبر ----
    default:
        Response::respondAndDie(['message' => 'Invalid request method'], Httpstatus::HTTP_METHOD_NOT_ALLOWED);
}
