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

// Проверка на авторизацию
// Получение товаров из корзины для текущего пользователя и сохранение в сессии
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT product_id, size, quantity 
        FROM cart 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $_SESSION['cart'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $_SESSION['cart'] = [];
}

// Получение товаров из корзины
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (count($cart) === 0) {
    $products = [];
} else {
    // Формируем список ID товаров для выборки из базы данных
    $product_ids = array_column($cart, 'product_id');
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($query);
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Добавляем данные о количестве и размере к каждому товару
    foreach ($products as &$product) {
        foreach ($cart as $cart_item) {
            if ($product['id'] == $cart_item['product_id']) {
                $product['size'] = $cart_item['size'];
                $product['quantity'] = $cart_item['quantity'];
                break;
            }
        }
    }
}


// Получаем товары из корзины
// Получаем товары из корзины
if (isset($_SESSION['user_id'])) {
    $query = "
        SELECT c.product_id, c.size, c.quantity, p.name, p.price, p.image, p.discount
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :user_id
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $cart_items = [];
}

// Функция форматирования суммы в нужный формат
function format_price($price) {
    return number_format($price, 0, ',', ' ');
}

// Рассчитываем итоговую стоимость корзины
$total_price = 0;

foreach ($cart_items as $item) {
    $price = (float)$item['price'];
    $discount = (float)$item['discount'];

    // Если есть скидка, рассчитываем цену со скидкой
    $final_price = $discount > 0 ? $price - ($price * $discount / 100) : $price;

    // Умножаем на количество
    $total_price += $final_price * $item['quantity'];
}

// Добавляем скидку от промокода, если есть
$promo_discount = $_SESSION['promo_discount'] ?? 0;
$cart_total = $_SESSION['cart_total'] ?? $total_price; // Если промокод не применен, используем общую сумму


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Общие стили */
    .promo-code-block h2,
    #discountSummaryBlock h2 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .promo-code-block input {
        width: 100%;
        max-width: 300px;
        padding: 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid #d1d5db;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .promo-code-block input:focus {
        border-color: #1f2937;
        box-shadow: 0 0 0 2px rgba(31, 41, 55, 0.5);
        outline: none;
    }

    .promo-code-block button {
        background-color: #1f2937;
        color: #ffffff;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        transition: background-color 0.3s;
    }

    .promo-code-block button:hover {
        background-color: #4b5563;
    }

    /* Стили для блока скидок */
    #discountSummaryBlock {
        background-color: #f9fafb;
        border-radius: 0.375rem;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 1rem;
        transition: opacity 0.3s, transform 0.3s;
    }

    #discountSummaryBlock p {
        margin-bottom: 0.5rem;
    }

    #subtotal-price {
        color: #4b5563;
    }

    #simple-discount {
        color: #059669; /* Зеленый для скидок на товары */
    }

    #promo-discount {
        color: #2563eb; /* Синий для промокодной скидки */
    }

    #total-price {
        font-weight: bold;
        color: #111827; /* Темный текст для итоговой суммы */
    }

    .discount-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background-color: rgba(107, 114, 128, 0.9); /* Полупрозрачный серый */
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem; /* Скругление углов */
        font-size: 0.875rem; /* Размер текста */
        font-weight: bold; /* Жирный текст */
        z-index: 10; /* Поверх других элементов */
    }
    .cart-summary {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
    }

    #checkout-summary {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-end;
    }

    #checkout-summary p {
        margin-right: 1rem;
    }

    </style>
</head>
<body class="bg-gray-100">

<?php include '../components/navbar.php'; ?>

<div class="py-8 pt-24" style="padding-left: 20%; padding-right: 20%">
    <!-- Заголовок страницы -->
    <div class="px-4 md:px-0 py-4">
        <nav class="uppercase text-gray-500 font-bold">
            <a href="/" class="hover:underline">RYUO</a><span class="font-bold"> » </span><span class="font-bold">КОРЗИНА</span>
        </nav>
        <h1 class="uppercase text-2xl font-bold text-gray-800">КОРЗИНА</h1>
    </div>


    <?php if (count($cart_items) > 0): ?>
        <!-- Сетка для корзины -->
        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($cart_items as $item): ?>
    <!-- Карточка товара в корзине -->
    <div class="bg-white rounded-lg shadow-lg p-4 flex gap-4 relative">
        <?php if ((float)$item['discount'] > 0): ?>
            <?php $discount_percentage = round($item['discount']); ?>
            <!-- Бейдж скидки -->
            <div class="discount-badge">
                -<?= $discount_percentage ?>%
            </div>
        <?php endif; ?>

        <!-- Изображение товара -->
        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-32 h-32 object-cover rounded-lg flex-shrink-0" />
        <div class="flex-1">
            <h2 class="text-lg font-semibold"><?= htmlspecialchars($item['name']) ?></h2>
            <p class="text-sm text-gray-600">Размер: <?= htmlspecialchars($item['size']) ?></p>

            <!-- Цена товара -->
            <div class="text-lg font-bold mt-2">
                <?php if ((float)$item['discount'] > 0): ?>
                    <?php $discounted_price = $item['price'] - ($item['price'] * $item['discount'] / 100); ?>
                    <span class="product-total-amount text-red-500">
                        <?= format_price($discounted_price * $item['quantity']) ?>₽
                    </span>
                    <span class="line-through text-gray-500 product-original-total-amount">
                        <?= format_price($item['price'] * $item['quantity']) ?>₽
                    </span>
                <?php else: ?>
                    <span class="product-total-amount">
                        <?= format_price($item['price'] * $item['quantity']) ?>₽
                    </span>
                <?php endif; ?>
            </div>

            <!-- Количество товара -->
                            <!-- Количество товара с кнопками -->
                <div class="mt-2 flex items-center gap-2">
                    <!-- Кнопка уменьшения количества -->
                    <button class="decrease-quantity quantity-btn bg-gray-200 rounded-full p-2 flex items-center justify-center hover:bg-gray-300 decrease-quantity" 
                        data-product-id="<?= $item['product_id'] ?>" 
                        data-size="<?= $item['size'] ?>">
                        <img src="/images/icons/minus.svg" alt="-" class="w-4 h-4">
                    </button>

                    <!-- Поле ввода количества -->
                    <input type="number" value="<?= $item['quantity'] ?>" min="1" max="99"
                                   data-product-id="<?= $item['product_id'] ?>" 
                                   data-size="<?= $item['size'] ?>" 
                                   class="quantity-input w-16 px-2 py-1 border text-center rounded-md">

                    <!-- Кнопка увеличения количества -->
                    <button class="increase-quantity quantity-btn bg-gray-200 rounded-full p-2 flex items-center justify-center hover:bg-gray-300 increase-quantity" 
                        data-product-id="<?= $item['product_id'] ?>" 
                        data-size="<?= $item['size'] ?>">
                        <img src="/images/icons/plus.svg" alt="+" class="w-4 h-4">
                    </button>
                </div>

        </div>

        <!-- Квадратная кнопка с крестиком -->
        <button class="absolute top-4 right-4 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-red-200 delete-item" data-product-id="<?= $item['product_id'] ?>" data-size="<?= $item['size'] ?>">
            <img src="/images/icons/X.svg" alt="Удалить" class="w-4 h-4">
        </button>
    </div>
<?php endforeach; ?>

        </div>

        <!-- Блок для промокода и итоговой стоимости -->
                    <!-- Промокод и итог -->
        <div class="cart-summary mt-6 bg-white p-4 rounded-lg shadow-lg w-full flex flex-col md:flex-row gap-6 ">
            <!-- Левый блок: ввод промокода -->
            <div class="discountSummaryBlock flex-1 bg-gray-100 p-4 rounded-lg shadow-inner">
                <p>Скидка на товары: <span id="simple-discount">0₽</span></p>
            </div>
    

            <!-- Правый блок: отображение скидок -->
            <div id="checkout-summary" class="cart-summary">
                <p>Общая сумма: <span id="total-price">0₽</span></p> <!-- Убедитесь, что этот элемент присутствует -->
                <a id="checkoutButton" href="../pages/checkout.php"
                    class="bg-black text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-800 transition">
                    Оформить заказ
                </a>
            </div>


        </div>
    </div>






    <?php else: ?>
        <p class="text-gray-700 text-center">Ваша корзина пуста. Добавьте товары в корзину.</p>
    <?php endif; ?>
</div>

<?php include '../components/footer.php'; ?>

<script src="../backend/cart.js"></script>
<script src="../backend/notifications.js"></script>


</body>
</html>
