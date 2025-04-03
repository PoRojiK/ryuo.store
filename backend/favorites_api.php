<?php
session_start();

// Подключение к базе данных
$host = 'sql105.infinityfree.com'; 
$dbname = 'if0_37280528_ryuo_store'; 
$username = 'if0_37280528'; 
$password = 'm9RLB5iHMPr'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "Ошибка подключения: " . $e->getMessage()]));
}

// Получение данных из запроса
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$product_id = $data['product_id'] ?? 0;

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Пользователь не авторизован."]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($action == 'add') {
    $stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $product_id]);
    $_SESSION['favorites'][] = $product_id;
    echo json_encode(["success" => true, "message" => "Товар добавлен в избранное."]);
} elseif ($action == 'remove') {
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $_SESSION['favorites'] = array_diff($_SESSION['favorites'], [$product_id]);
    echo json_encode(["success" => true, "message" => "Товар удален из избранного."]);
} else {
    echo json_encode(["success" => false, "message" => "Неверное действие."]);
}
?>
