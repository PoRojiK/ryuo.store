<?php
session_start();
$host = 'sql105.infinityfree.com';
$dbname = 'if0_37280528_ryuo_store';
$username = 'if0_37280528';
$password = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT c.product_id, c.size, c.quantity, p.price, p.discount FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_price = 0;
    $total_discount = 0;

    foreach ($cart_items as $item) {
        $price = (float)$item['price'];
        $discount = (float)$item['discount'];
        $quantity = (int)$item['quantity'];

        $final_price = $price - ($price * $discount / 100);
        $total_price += $final_price * $quantity;
        $total_discount += ($price - $final_price) * $quantity;
    }

    echo json_encode(['success' => true, 'total_price' => $total_price, 'total_discount' => $total_discount]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
