<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Вы не авторизованы.']);
    exit;
}

// Получение данных из JSON
$data = json_decode(file_get_contents('php://input'), true);

$product_id = $data['product_id'] ?? null;
$size = $data['size'] ?? null;

if (!$product_id || !$size) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных для удаления.']);
    exit;
}

$host = 'sql105.infinityfree.com';
$dbname = 'if0_37280528_ryuo_store';
$username = 'if0_37280528';
$password = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND size = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id, $size]);

    echo json_encode(['success' => true, 'message' => 'Товар удалён из корзины.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
