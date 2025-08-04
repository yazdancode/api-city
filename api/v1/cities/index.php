<?php

include_once "../../../loader.php";

use App\Services\CityService;
use App\Utilities\Response;
use App\Utilities\Httpstatus;

if (php_sapi_name() === 'cli') {
    echo "City endpoint is here\n";
}

$cs = new CityService();
$cities = $cs->getCities((object)[1, 2, 33, 44, 55]);

echo Response::respond($cities, Httpstatus::HTTP_OK);
