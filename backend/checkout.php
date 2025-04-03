<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Подключение к базе данных
$host = 'sql105.infinityfree.com';
$dbname = 'if0_37280528_ryuo_store';
$username = 'if0_37280528';
$password = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Получение данных пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Получение товаров из корзины
    $stmt = $pdo->prepare("
        SELECT products.name, products.image, cart.quantity, products.price, products.discount 
        FROM cart 
        JOIN products ON cart.product_id = products.id 
        WHERE cart.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Ошибка базы данных: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<?php include 'components/navbar.php'; ?>

<div class="container mx-auto py-8 pt-24 px-4 md:px-12">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Данные для оплаты и доставки -->
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold mb-6">Информация для оплаты и доставки</h2>
            <form action="order_process.php" method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Имя</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Фамилия</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Адрес</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($user['address']) ?>" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Город</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($user['city']) ?>" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Почтовый индекс</label>
                    <input type="text" name="zip_code" value="<?= htmlspecialchars($user['zip_code']) ?>" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Телефон</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <button type="submit" class="bg-black text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-800 transition">Оформить заказ</button>
            </form>
        </div>

        <!-- Заказ -->
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold mb-6">Ваш заказ</h2>
            <div class="space-y-4">
                <?php foreach ($cart_items as $item): ?>
                    <div class="flex items-center gap-4">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-16 h-16 object-cover rounded-lg">
                        <div>
                            <h3 class="text-lg font-semibold"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="text-sm text-gray-600">Количество: <?= htmlspecialchars($item['quantity']) ?></p>
                            <p class="text-sm text-gray-600">
                                <?php if ((float)$item['discount'] > 0): ?>
                                    <?php $discounted_price = $item['price'] - ($item['price'] * $item['discount'] / 100); ?>
                                    <span class="text-red-500"><?= format_price($discounted_price * $item['quantity']) ?>₽</span>
                                    <span class="line-through text-gray-500"><?= format_price($item['price'] * $item['quantity']) ?>₽</span>
                                <?php else: ?>
                                    <span><?= format_price($item['price'] * $item['quantity']) ?>₽</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>

</body>
</html>
