<?php
session_start();
require_once 'db.php';
$total_price = 0;
$total_discount = 0;

if (isset($_SESSION['user_id'])) {
    $query = "
        SELECT c.quantity, p.price, p.discount
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :user_id
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $subtotal = 0;
    $discount = 0;

    foreach ($cart_items as $item) {
        $price = (float)$item['price'];
        $quantity = (int)$item['quantity'];
        $item_discount = (float)$item['discount'];

        $discounted_price = $item_discount > 0 ? $price - ($price * $item_discount / 100) : $price;

        $subtotal += $discounted_price * $quantity;
        $discount += ($price - $discounted_price) * $quantity;
    }

    $total = $subtotal;

    echo json_encode([
        'subtotal' => number_format($subtotal, 0, ',', ' '),
        'discount' => number_format($discount, 0, ',', ' '),
        'total' => number_format($total, 0, ',', ' ')
    ]);
} else {
    echo json_encode(['subtotal' => 0, 'discount' => 0, 'total' => 0]);
}