<?php
// Установка стоимости доставки в зависимости от выбранного метода
$delivery_cost = 0;

// Если форма отправлена
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_option = $_POST['delivery_option'] ?? 'post'; // Значение по умолчанию — "post"

    // Логика определения стоимости доставки
    switch ($delivery_option) {
        case 'cdek':
            $delivery_cost = 500;
            break;
        case 'post':
        default:
            $delivery_cost = 200;
            break;
    }
} else {
    // Значение по умолчанию для начальной загрузки страницы
    $delivery_cost = 200; // Почта России
}

// Подсчет итоговой суммы
$total = 0;
foreach ($cart_items as $item) {
    $discounted_price = $item['price'] * (1 - ($item['discount'] / 100));
    $total += $discounted_price * $item['quantity'];
}
$final_total = $total + $delivery_cost;
?>
