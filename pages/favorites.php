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
    die("Ошибка подключения: " . $e->getMessage());
}

// Получение избранных товаров
$favorites = isset($_SESSION['user_id']) ? $_SESSION['favorites'] : [];
if (count($favorites) === 0) {
    $products = [];
} else {
    $placeholders = str_repeat('?,', count($favorites) - 1) . '?';
    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($query);
    $stmt->execute($favorites);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Функция форматирования цены
function format_price($price) {
    return number_format($price, 0, ',', ' ');
}

// Проверка, находится ли товар в избранном
function is_favorite($product_id) {
    return in_array($product_id, $_SESSION['favorites']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Избранное</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <?php include '../components/navbar.php'; ?>
  <div class="py-8 pt-24" style="padding-left: 20%; padding-right: 20%">

    <div class="px-4 md:px-0 py-4">
        <nav class="uppercase text-gray-500 font-bold">
            <a href="/" class="hover:underline">RYUO</a><span class="font-bold"> » </span><span class="font-bold">СПИСОК ЖЕЛАЕМОГО</span>
        </nav>
        <h1 class="uppercase text-2xl font-bold text-gray-800">ИЗБРАННОЕ</h1>
    </div>

    <?php if (count($products) > 0): ?>
    <!-- Сетка для карточек -->
    <div class="grid grid-cols-1 gap-6">
      <?php foreach ($products as $product): ?>
      <div class="product-card bg-white rounded-lg shadow-lg p-4 w-full flex flex-col gap-4 relative h-full">
        <!-- Иконка избранного -->
        <img
            src="<?= is_favorite($product['id']) ? '/images/icons/heart-fav.svg' : '/images/icons/heart.svg' ?>"
            data-fav="<?= is_favorite($product['id']) ? '1' : '0' ?>"
            class="heart-icon cursor-pointer absolute top-4 right-4"
            onclick="toggleFavorite(event, this, <?= $product['id'] ?>)"
        >

        <!-- Верхний блок с изображением и информацией -->
        <a href="product.php?id=<?= $product['id'] ?>" class="flex gap-4">
            <img
                src="<?= htmlspecialchars($product['image']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>"
                class="w-32 h-32 object-cover rounded-lg flex-shrink-0"
            />
            <div class="flex-1">
                <h2 class="text-lg font-semibold"><?= htmlspecialchars($product['name']) ?></h2>
                <div class="text-lg font-bold mt-2">
                    <?php if ((float)$product['discount'] > 0): ?>
                    <span class="text-red-500">
                        <?= format_price($product['price'] - ($product['price'] * $product['discount'] / 100)) ?>₽
                    </span>
                    <span class="line-through text-gray-500">
                        <?= format_price($product['price']) ?>₽
                    </span>
                    <?php else: ?>
                    <span><?= format_price($product['price']) ?>₽</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>

        <div class="absolute bottom-4 right-4">
            <button class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                Добавить в корзину
            </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Сообщение об отсутствии товаров -->
    <div class="flex flex-col items-center justify-center text-center mt-20">
        <img src="/images/icons/empty_fav.svg" alt="Пустое избранное" class="mb-6">
        <h2 class="text-3xl text-gray-600 mb-4">В избранном ничего нет</h2>
        <div class="flex items-center space-x-2"> <p class="text-xl text-gray-600">Здесь пока ничего нет, но ты можешь добавить товар в избранное, кликнув на</p> <img src="/images/icons/heart.svg" class="mt-1 w-6 h-6"> </div>
    </div>
    <?php endif; ?>
  </div>

  <?php include '../components/footer.php'; ?>
</body>

<script src="../backend/notifications.js"></script>
<script>
function toggleFavorite(event, element, productId) {
    event.stopPropagation();

    fetch('../api/favorites_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, action: element.dataset.fav === '1' ? 'remove' : 'add' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const isFavorite = element.dataset.fav === '1';

            if (isFavorite) {
                const productCard = element.closest('.product-card');
                if (productCard) {
                    productCard.remove();
                    
                    // Проверяем количество оставшихся товаров
                    const remainingProducts = document.querySelectorAll('.product-card');
                    if (remainingProducts.length === 0) {
                        const containerDiv = document.querySelector('.py-8.pt-24');
                        if (containerDiv) {
                            // Сохраняем навигацию и заголовок
                            const nav = containerDiv.querySelector('nav').outerHTML;
                            const title = containerDiv.querySelector('h1').outerHTML;
                            
                            // Формируем новое содержимое
                            const newContent = `
                                ${nav}
                                ${title}
                                <div class="flex flex-col items-center justify-center text-center mt-20">
                                    <img src="/images/icons/empty_fav.svg" alt="Пустое избранное" class="mb-6">
                                    <h2 class="text-3xl text-gray-600 mb-4">В избранном ничего нет</h2>
                                    <div class="flex items-center space-x-2">
                                        <p class="text-xl text-gray-600">Здесь пока ничего нет, но ты можешь добавить товар в избранное, кликнув на</p>
                                        <img src="/images/icons/heart.svg" class="mt-1 w-6 h-6">
                                    </div>
                                </div>`;
                            
                            containerDiv.innerHTML = newContent;
                        }
                    }
                }
            }

            const actionMessage = isFavorite ? 'удален из избранного' : 'добавлен в избранное';
            showNotification(`Товар успешно ${actionMessage}.`, 'green');
        } else {
            showNotification(data.message || 'Произошла ошибка.', 'red');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showNotification('Не удалось обновить состояние избранного.', 'red');
    });
}
</script>
</html>
