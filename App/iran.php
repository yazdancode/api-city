<?php

use App\Utilities\Httpstatus;

try {
    $pdo = new PDO("mysql:dbname=iran;host=localhost", 'root', '');
    $pdo->exec("set names utf8;");
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

#==============  Simple Validators  ================
function isValidCity($data): bool
{
    return !empty($data['province_id']) && is_numeric($data['province_id']) && !empty($data['name']);
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
    $fields = $data['fields'] ?? '*';

    // Validate fields
    $allowedFields = ['id', 'name', 'province_id', 'created_at', 'updated_at'];
    if ($fields !== '*') {
        $requestedFields = array_map('trim', explode(',', $fields));
        $validatedFields = array_intersect($requestedFields, $allowedFields);

        if (empty($validatedFields)) {
            errorResponse('فیلدهای درخواستی نامعتبر هستند.', Httpstatus::HTTP_BAD_REQUEST);
        }

        $fields = implode(',', $validatedFields);
    }

    $where = '';
    $limit = '';
    $params = [];

    if (!is_null($province_id) && is_numeric($province_id)) {
        $where = "WHERE province_id = :province_id";
        $params['province_id'] = (int)$province_id;
    }

    if (!is_null($page) && !is_null($pagesize) && is_numeric($page) && is_numeric($pagesize)) {
        $offset = ((int)$page - 1) * (int)$pagesize;
        $limit = "LIMIT :offset, :pagesize";
        $params['offset'] = $offset;
        $params['pagesize'] = (int)$pagesize;
    }

    $sql = "SELECT $fields FROM city $where $limit";
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
    $sql = "SELECT * FROM province";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

#================  Create Operations  =================
function addCity($data)
{
    global $pdo;
    if (!isValidCity($data)) {
        return false;
    }

    $sql = "INSERT INTO city (province_id, name) VALUES (:province_id, :name)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':province_id' => (int)$data['province_id'],
        ':name' => $data['name']
    ]);

    return $stmt->rowCount();
}

function addProvince($data)
{
    global $pdo;
    if (!isValidProvince($data)) {
        return false;
    }

    $sql = "INSERT INTO province (name) VALUES (:name)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':name' => $data['name']]);

    return $stmt->rowCount();
}

#================  Update Operations  =================
function changeCityName($city_id, $name): int
{
    global $pdo;
    $sql = "UPDATE city SET name = :name WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':id' => (int)$city_id
    ]);
    return $stmt->rowCount();
}

function changeProvinceName($province_id, $name): int
{
    global $pdo;
    $sql = "UPDATE province SET name = :name WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':id' => (int)$province_id
    ]);
    return $stmt->rowCount();
}

#================  Delete Operations  =================
function deleteCity($city_id): int
{
    global $pdo;
    $sql = "DELETE FROM city WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (int)$city_id]);
    return $stmt->rowCount();
}

function deleteProvince($province_id): int
{
    global $pdo;
    $sql = "DELETE FROM province WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (int)$province_id]);
    return $stmt->rowCount();
}
