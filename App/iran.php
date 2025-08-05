<?php
try {
    $pdo = new PDO("mysql:dbname=iran;host=localhost", 'root', '');
    $pdo->exec("set names utf8;");
    // echo "Connection OK!";
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

#==============  Simple Validators  ================
function isValidCity($data): bool
{
    if(empty($data['province_id']) or !is_numeric($data['province_id']))
        return false;
    return !empty($data['name']);
}
function isValidProvince($data): bool
{
    return !empty($data['name']);
}


#================  Read Operations  =================
function getCities($data = null): array
{
    global $pdo;

    $data = (array)($data ?? []);

    $province_id = $data['province_id'] ?? null;
    $page = $data['page'] ?? null;
    $pagesize = $data['pagesize'] ?? null;

    $where = '';
    $limit = '';
    $params = [];

    if (!is_null($province_id) && is_numeric($province_id)) {
        $where = "WHERE province_id = :province_id";
        $params['province_id'] = $province_id;
    }

    if (!is_null($page) && !is_null($pagesize) && is_numeric($page) && is_numeric($pagesize)) {
        $offset = ($page - 1) * $pagesize;
        $limit = "LIMIT :offset, :pagesize";
        $params['offset'] = (int)$offset;
        $params['pagesize'] = (int)$pagesize;
    }

    $sql = "SELECT * FROM city $where $limit";
    $stmt = $pdo->prepare($sql);

    if (isset($params['province_id'])) {
        $stmt->bindValue(':province_id', $params['province_id'], PDO::PARAM_INT);
    }
    if (isset($params['offset'])) {
        $stmt->bindValue(':offset', $params['offset'], PDO::PARAM_INT);
    }
    if (isset($params['pagesize'])) {
        $stmt->bindValue(':pagesize', $params['pagesize'], PDO::PARAM_INT);
    }

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

function getProvinces($data = null): array
{
    global $pdo;
    $sql = "select * from province";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}


#================  Create Operations  =================
function addCity($data){
    global $pdo;
    if(!isValidCity($data)){
        return false;
    }
    $sql = "INSERT INTO city (province_id, name) VALUES (:province_id, :name);";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':province_id'=>$data['province_id'],':name'=>$data['name']]);
    return $stmt->rowCount();
}
function addProvince($data){
    global $pdo;
    if(!isValidProvince($data)){
        return false;
    }
    $sql = "INSERT INTO province (name) VALUES (:name);";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':name'=>$data['name']]);
    return $stmt->rowCount();
}


#================  Update Operations  =================
function changeCityName($city_id,$name): int
{
    global $pdo;
    $sql = "update city set name = '$name' where id = $city_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount();
}
function changeProvinceName($province_id,$name): int
{
    global $pdo;
    $sql = "update province set name = '$name' where id = $province_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount();
}

#================  Delete Operations  =================
function deleteCity($city_id): int
{
    global $pdo;
    $sql = "delete from city where id = $city_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount();
}
function deleteProvince($province_id): int
{
    global $pdo;
    $sql = "delete from province where id = $province_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount();
}

// Function Tests
// $data = addCity(['province_id' => 23,'name' => "Loghman Shahr"]);
// $data = addProvince(['name' => "7Learn"]);
// $data = getCities(['province_id' => 23]);
// $data = deleteProvince(34);
// $data = changeProvinceName(34,"سون لرن");
// $data = getProvinces();
// $data = deleteCity(443);
// $data = changeCityName(445,"لقمان شهر");
// $data = getCities(['province_id' => 1]);
// $data = json_encode($data);
// echo "<pre>";
// print_r($data);
// echo "<pre>";