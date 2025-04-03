<?php
session_start();
header('Content-Type: application/json');
// Проверка на авторизацию
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Только авторизованные пользователи могут добавлять избранные товары.']);
    exit;
}

$user_id = $_SESSION['user_id'];

$host = 'sql105.infinityfree.com';
$dbname = 'if0_37280528_ryuo_store';
$username = 'if0_37280528';
$password = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения: ' . $e->getMessage()]);
    exit;
}

// Получение данных из AJAX-запроса
$data = json_decode(file_get_contents('php://input'), true);
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$action = isset($data['action']) ? $data['action'] : '';

if ($product_id === 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные запроса.']);
    exit;
}

try {
    if ($action === 'add') {
        // Добавить в избранное
        $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
    } elseif ($action === 'remove') {
        // Удалить из избранного
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
    }

    // Обновить избранные товары в сессии
    $stmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['favorites'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'favorites' => $_SESSION['favorites']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>  