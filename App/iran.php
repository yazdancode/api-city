<?php
try {
    $pdo = new PDO("mysql:dbname=iran;host=localhost", 'root', '');
    $pdo->exec("set names utf8;");
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}


#==============  Simple Validators  ================
function isValidCity($data): bool
{
    if(empty($data['province_id']) or !is_numeric($data['province_id']))
        return false;
    return empty($data['name']) ? false : true;
}
function isValidProvince($data): bool
{
    return empty($data['name']) ? false : true;
}


#================  Read Operations  =================
function getCities($data = null) {
    global $pdo;
    if (is_object($data)) {
        $data = (array)$data;
    }
    $province_id = $data['province_id'] ?? null;
    $where = '';
    if(!is_null($province_id) and is_numeric($province_id)){
        $where = "WHERE province_id = :province_id";
    }
    $sql = "SELECT * FROM city $where";
    $stmt = $pdo->prepare($sql);

    if (!empty($where)) {
        $stmt->execute(['province_id' => $province_id]);
    } else {
        $stmt->execute();
    }

    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

function getProvinces($data = null): array {
    global $pdo;
    $sql = "SELECT * FROM province";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

function getCityById($city_id) {
    global $pdo;
    $sql = "SELECT * FROM city WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $city_id]);
    return $stmt->fetch(PDO::FETCH_OBJ);
}

function getProvinceById($province_id) {
    global $pdo;
    $sql = "SELECT * FROM province WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $province_id]);
    return $stmt->fetch(PDO::FETCH_OBJ);
}

function searchCitiesByName($keyword) {
    global $pdo;
    $sql = "SELECT * FROM city WHERE name LIKE :keyword";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['keyword' => "%$keyword%"]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

function countCitiesByProvince($province_id) {
    global $pdo;
    $sql = "SELECT COUNT(*) as total FROM city WHERE province_id = :province_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['province_id' => $province_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

function getProvincesWithCityCount() {
    global $pdo;
    $sql = "
        SELECT p.*, COUNT(c.id) as city_count
        FROM province p
        LEFT JOIN city c ON c.province_id = p.id
        GROUP BY p.id
        ORDER BY city_count DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

function getAllCitiesGroupedByProvince() {
    global $pdo;
    $sql = "
        SELECT p.name as province_name, c.name as city_name
        FROM province p
        JOIN city c ON c.province_id = p.id
        ORDER BY p.name, c.name
    ";
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
    $sql = "INSERT INTO city (province_id, name) VALUES (:province_id, :name)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['province_id'=>$data['province_id'],'name'=>$data['name']]);
    return $stmt->rowCount();
}

function addProvince($data){
    global $pdo;
    if(!isValidProvince($data)){
        return false;
    }
    $sql = "INSERT INTO province (name) VALUES (:name)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name'=>$data['name']]);
    return $stmt->rowCount();
}


#================  Update Operations  =================
function changeCityName($city_id, $name): int {
    global $pdo;
    $sql = "UPDATE city SET name = :name WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => $name, 'id' => $city_id]);
    return $stmt->rowCount();
}

function changeProvinceName($province_id, $name): int {
    global $pdo;
    $sql = "UPDATE province SET name = :name WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => $name, 'id' => $province_id]);
    return $stmt->rowCount();
}

function updateCity($city_id, $data) {
    global $pdo;
    $sql = "UPDATE city SET name = :name, province_id = :province_id WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id' => $city_id,
        'name' => $data['name'],
        'province_id' => $data['province_id']
    ]);
    return $stmt->rowCount();
}


#================  Delete Operations  =================
function deleteCity($city_id): int {
    global $pdo;
    $sql = "DELETE FROM city WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $city_id]);
    return $stmt->rowCount();
}

function deleteProvince($province_id): int {
    global $pdo;
    $sql = "DELETE FROM province WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $province_id]);
    return $stmt->rowCount();
}

# =================== Test Examples ===================
// echo "<pre>";
// print_r(getProvincesWithCityCount());
// print_r(getAllCitiesGroupedByProvince());
// print_r(searchCitiesByName("تهران"));
// print_r(getCityById(1));
// print_r(updateCity(1, ['name' => 'نام جدید', 'province_id' => 2]));
// echo "</pre>";
