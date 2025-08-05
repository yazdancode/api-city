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

    $provinceId = $data['province_id'] ?? null;
    $page = $data['page'] ?? null;
    $pageSize = $data['pagesize'] ?? null;
    $fields = $data['fields'] ?? '*';
    $orderby = $data['orderby'] ?? null;

    // لیست فیلدهای مجاز
    $allowedFields = ['id', 'name', 'province_id', 'created_at', 'updated_at'];

    // اعتبارسنجی فیلدهای درخواستی
    if ($fields !== '*') {
        $requestedFields = array_map('trim', explode(',', $fields));
        $validatedFields = array_intersect($requestedFields, $allowedFields);

        if (empty($validatedFields)) {
            errorResponse('فیلدهای درخواستی نامعتبر هستند.', Httpstatus::HTTP_BAD_REQUEST);
        }

        $fields = implode(', ', $validatedFields);
    }

    // پردازش و اعتبارسنجی ORDER BY
    $orderBySql = '';
    if (!empty($orderby)) {
        // پشتیبانی از حالت "name asc" یا "id desc"
        $parts = preg_split('/\s+/', trim($orderby));
        $field = $parts[0] ?? '';
        $direction = strtolower($parts[1] ?? 'asc');

        if (!in_array($field, $allowedFields) || !in_array($direction, ['asc', 'desc'])) {
            errorResponse('مقدار orderby نامعتبر است.', Httpstatus::HTTP_BAD_REQUEST);
        }

        $orderBySql = "ORDER BY $field $direction";
    }

    // WHERE
    $where = '';
    $params = [];
    if (!is_null($provinceId) && is_numeric($provinceId)) {
        $where = "WHERE province_id = :province_id";
        $params[':province_id'] = (int)$provinceId;
    }

    // صفحه‌بندی
    $limitSql = '';
    if (!is_null($page) && !is_null($pageSize) && is_numeric($page) && is_numeric($pageSize)) {
        $offset = ((int)$page - 1) * (int)$pageSize;
        $limitSql = "LIMIT :offset, :pagesize";
        $params[':offset'] = $offset;
        $params[':pagesize'] = (int)$pageSize;
    }

    // SQL نهایی
    $sql = "SELECT $fields FROM city $where $orderBySql $limitSql";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
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
