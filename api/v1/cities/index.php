<?php

include_once "../../../loader.php";

use App\Services\CityService;
use App\Utilities\Response;
use App\Utilities\Httpstatus;


if (php_sapi_name() === 'cli') {
    echo "City endpoint is here\n";
    exit;
}
$request_method = $_SERVER['REQUEST_METHOD'];
$request_body = json_decode(file_get_contents('php://input'), true);

switch ($request_method) {

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

    case 'GET':
        try {
            $province_id = $_GET['province_id'] ?? null;

            $request_data = [
                'province_id' => $province_id
            ];

            $cityService = new CityService();
            $response = $cityService->getCities((object)$request_data);

            Response::respondAndDie([
                'status' => 'success',
                'data' => $response
            ], Httpstatus::HTTP_OK);

        } catch (Exception $e) {
            Response::respondAndDie([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Httpstatus::HTTP_INTERNAL_SERVER_ERROR);
        }
        break;

    case 'PUT':
        Response::respondAndDie(['message' => 'PUT Request'], Httpstatus::HTTP_OK);
        break;

    case 'DELETE':
        Response::respondAndDie(['message' => 'DELETE Request'], Httpstatus::HTTP_OK);
        break;

    default:
        Response::respondAndDie(['message' => 'Invalid request method'], Httpstatus::HTTP_METHOD_NOT_ALLOWED);
}
