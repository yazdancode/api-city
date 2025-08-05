<?php
namespace App\Services;
class Province
{
    public function getProvince($province_id)
    {
        return getProvinces($province_id);

    }

    public function createProvince($data)
    {
        return addProvince($data);

    }

    public  function updateProvince($province_id,$data)
    {
        return changeProvinceName($province_id,$data);
    }

    public function deleteProvince($province_id)
    {
        return deleteProvince($province_id);
    }

}
