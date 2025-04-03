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
    die(json_encode(['success' => false, 'message' => 'Ошибка подключения: ' . $e->getMessage()]));
}

// Получение данных из запроса
$data = json_decode(file_get_contents('php://input'), true);

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Пользователь не авторизован.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $data['action'] ?? '';

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Не указано действие.']);
    exit;
}

try {
    switch ($action) {
        case 'add':
            // Логика добавления товара в корзину
            $product_id = (int)$data['product_id'];
            $size = $data['size'] ?? '';
            $quantity = (int)($data['quantity'] ?? 1);

            $stmt = $pdo->prepare("
                INSERT INTO cart (user_id, product_id, size, quantity) 
                VALUES (:user_id, :product_id, :size, :quantity) 
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'product_id' => $product_id,
                'size' => $size,
                'quantity' => $quantity
            ]);
            echo json_encode(['success' => true, 'message' => 'Товар добавлен в корзину.']);
            break;


        default:
            echo json_encode(['success' => false, 'message' => 'Неизвестное действие.']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка обработки: ' . $e->getMessage()]);
}
?>
