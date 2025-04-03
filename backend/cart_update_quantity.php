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
$quantity = $data['quantity'] ?? null;

if (!$product_id || !$size || !$quantity) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных для обновления количества.']);
    exit;
}

$host = 'sql105.infinityfree.com';
$dbname = 'if0_37280528_ryuo_store';
$username = 'if0_37280528';
$password = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND size = ?");
    $stmt->execute([$quantity, $_SESSION['user_id'], $product_id, $size]);

    // Получение цены за единицу товара и цены со скидкой
    $stmt = $pdo->prepare("SELECT price, price - (price * discount / 100) AS discounted_price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $unit_price = (float)$product['price'];
        $discounted_price = (float)$product['discounted_price'];

        echo json_encode([
            'success' => true,
            'message' => 'Количество обновлено.',
            'unit_price' => $unit_price,
            'discounted_price' => $discounted_price
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Товар не найден.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
