<?php

require_once 'Utilities/helpers.php';
include __DIR__ . '/../vendor/autoload.php';


use App\Utilities\Httpstatus;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

const JWT_KEY = 'test-project';
const JWT_ALG = 'HS256';

try {
    $pdo = new PDO("mysql:dbname=iran;host=localhost", 'root', '');
    $pdo->exec("set names utf8;");
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// ==============  Simple Validators  ================
function isValidCity($data): bool
{
    return !empty($data['province_id']) && is_numeric($data['province_id']) && !empty($data['name']);
}

function isValidProvince($data): bool
{
    return !empty($data['name']);
}

// ================  Read Operations  =================
function getCities($data = null): array
{
    global $pdo;

    $data = (array)($data ?? []);

    $provinceId = $data['province_id'] ?? null;
    $page = $data['page'] ?? null;
    $pageSize = $data['pagesize'] ?? null;
    $fields = $data['fields'] ?? '*';
    $orderby = $data['orderby'] ?? null;

    $allowedFields = ['id', 'name', 'province_id', 'created_at', 'updated_at'];

    if ($fields !== '*') {
        $requestedFields = array_map('trim', explode(',', $fields));
        $validatedFields = array_intersect($requestedFields, $allowedFields);
        if (empty($validatedFields)) {
            errorResponse('فیلدهای درخواستی نامعتبر هستند.', Httpstatus::HTTP_BAD_REQUEST);
        }
        $fields = implode(', ', $validatedFields);
    }

    $orderBySql = '';
    if (!empty($orderby)) {
        $parts = preg_split('/\s+/', trim($orderby));
        $field = $parts[0] ?? '';
        $direction = strtolower($parts[1] ?? 'asc');
        if (!in_array($field, $allowedFields) || !in_array($direction, ['asc', 'desc'])) {
            errorResponse('مقدار orderby نامعتبر است.', Httpstatus::HTTP_BAD_REQUEST);
        }
        $orderBySql = "ORDER BY $field $direction";
    }

    $where = '';
    $params = [];
    if (!is_null($provinceId) && is_numeric($provinceId)) {
        $where = "WHERE province_id = :province_id";
        $params[':province_id'] = (int)$provinceId;
    }

    $limitSql = '';
    if (!is_null($page) && !is_null($pageSize) && is_numeric($page) && is_numeric($pageSize)) {
        $offset = ((int)$page - 1) * (int)$pageSize;
        $limitSql = "LIMIT :offset, :pagesize";
        $params[':offset'] = $offset;
        $params[':pagesize'] = (int)$pageSize;
    }

    $sql = "SELECT $fields FROM city $where $orderBySql $limitSql";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

function getProvinces(): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM province");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// ================  Create Operations  =================
function addCity($data)
{
    global $pdo;
    if (!isValidCity($data)) return false;

    $stmt = $pdo->prepare("INSERT INTO city (province_id, name) VALUES (:province_id, :name)");
    $stmt->execute([
        ':province_id' => (int)$data['province_id'],
        ':name' => $data['name']
    ]);

    return $stmt->rowCount();
}

function addProvince($data)
{
    global $pdo;
    if (!isValidProvince($data)) return false;

    $stmt = $pdo->prepare("INSERT INTO province (name) VALUES (:name)");
    $stmt->execute([':name' => $data['name']]);

    return $stmt->rowCount();
}

// ================  Update Operations  =================
function changeCityName($city_id, $name): int
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE city SET name = :name WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':id' => (int)$city_id
    ]);
    return $stmt->rowCount();
}

function changeProvinceName($province_id, $name): int
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE province SET name = :name WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':id' => (int)$province_id
    ]);
    return $stmt->rowCount();
}

// ================  Delete Operations  =================
function deleteCity($city_id): int
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM city WHERE id = :id");
    $stmt->execute([':id' => (int)$city_id]);
    return $stmt->rowCount();
}

function deleteProvince($province_id): int
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM province WHERE id = :id");
    $stmt->execute([':id' => (int)$province_id]);
    return $stmt->rowCount();
}

// ================  User/Auth Operations  =================
$users = [
    (object)['id'=>1,'name'=>'Loghman','email'=>'loghman@7learn.com','role'=>'admin','allowed_provinces'=>[1,2,3,4]],
    (object)['id'=>2,'name'=>'Sara','email'=>'sara@7learn.com','role'=>'Governor','allowed_provinces'=>[7,8,9]],
    (object)['id'=>3,'name'=>'Ali','email'=>'ali@7learn.com','role'=>'mayor','allowed_provinces'=>[3]],
    (object)['id'=>4,'name'=>'Hassan','email'=>'hassan@7learn.com','role'=>'president','allowed_provinces'=>[]]
];

function getUserById($id)
{
    global $users;
    foreach ($users as $user) {
        if ($user->id == $id) {
            return $user;
        }
    }
    return null;
}

function getUserByEmail($email)
{
    global $users;
    foreach ($users as $user) {
        if (strtolower($user->email) === strtolower($email)) {
            return $user;
        }
    }
    return null;
}

function createapitoken($user)
{
    $payload = ['user_id' => $user->id];
    return JWT::encode($payload, JWT_KEY, JWT_ALG);
}

function getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

function getBearerToken(): ?string {
    $headers = getallheaders();

    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            if (preg_match('/Bearer\s(\S+)/', $value, $matches)) {
                return $matches[1];
            }
        }
    }

    return null;
}

function isValidToken($jwt_token)
{
    if (empty($jwt_token)) {
        errorResponse("توکن ارسال نشده است.", Httpstatus::HTTP_UNAUTHORIZED);
        exit;
    }

    try {
        $jwtDecoded = JWT::decode($jwt_token, new Key(JWT_KEY, JWT_ALG));

        if (!isset($jwtDecoded->user_id)) {
            errorResponse("توکن معتبر نیست: user_id یافت نشد.", Httpstatus::HTTP_UNAUTHORIZED);
            exit;
        }

        $user = getUserById($jwtDecoded->user_id);

        if (!$user) {
            errorResponse("کاربری با این توکن یافت نشد.", Httpstatus::HTTP_NOT_FOUND);
            exit;
        }

        return $user;

    } catch (Exception $e) {
        errorResponse("توکن نامعتبر است: " . $e->getMessage(), Httpstatus::HTTP_UNAUTHORIZED);
        exit;
    }
}

function hasAccessToProvince($user, $provinceId)
{
    if (in_array($user->role, ['admin', 'president'])) {
        return true;
    }

    // اگر allowed_provinces رشته باشه
    if (!is_array($user->allowed_provinces)) {
        $user->allowed_provinces = json_decode($user->allowed_provinces, true);
    }

    // اگر باز هم آرایه نبود
    if (!is_array($user->allowed_provinces)) {
        return false;
    }

    // مقایسه عددی دقیق
    return in_array((int)$provinceId, array_map('intval', $user->allowed_provinces));
}




//$user = getUserByEmail('sara@7learn.com');
//$token = createapitoken($user);
//echo $token;
