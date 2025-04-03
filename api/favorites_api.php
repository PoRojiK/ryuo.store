<?php
session_start();
header('Content-Type: application/json');

// Подключение к базе данных
$host = 'sql105.infinityfree.com';
$dbname = 'if0_37280528_ryuo_store';
$username = 'if0_37280528';
$password = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения: ' . $e->getMessage()]);
    exit;
}

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Пользователь не авторизован.']);
    exit;
}

// Обработка запроса
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['action']) || !isset($data['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректные данные.']);
    exit;
}

$userId = $_SESSION['user_id'];
$productId = (int)$data['product_id'];
$action = $data['action'];

try {
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'message' => 'Товар добавлен в избранное.']);
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'message' => 'Товар удален из избранного.']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Некорректное действие.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
}
?>
