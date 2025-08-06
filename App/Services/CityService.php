<?php


namespace App\Services;


class CityService{

    public function getCities($data): array
    {
        return getCities($data);
    }
    public function createCity($data){
        return addCity($data);
    }
    public function updateCityName($city_id,$name): int
    {
        return changeCityName($city_id,$name);
    }
    public function deleteCity($city_id): int
    {
        return deleteCity($city_id);
    }
}