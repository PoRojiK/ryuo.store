
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

// Получение избранных товаров для текущего пользователя и сохранение в сессии
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $_SESSION['favorites'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    $_SESSION['favorites'] = [];
}

// Проверка избранного
function is_favorite($product_id) {
    return in_array($product_id, $_SESSION['favorites']);
}

// Функции для работы с категориями и продуктами
function getProductsByIds($productIds, $pdo) {
    $idsPlaceholder = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($idsPlaceholder)");
    foreach ($productIds as $index => $id) {
        $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoryName($categoryId, $pdo) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = :category_id");
    $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    return $category ? $category['name'] : 'Неизвестная категория';
}

// Функция форматирования цены
function format_price($price) {
    return number_format($price, 0, ',', ' ');
}

function format_discount($discount) {
    return round($discount);
}

// Указание товаров для каждой категории
$categoryProducts = [
    1 => [29, 30, 33, 34, 35], // ID товаров для категории 1
    2 => [31, 32, 43, 44, 45], // ID товаров для категории 2
    3 => [36, 37, 38, 41, 42],  // ID товаров для категории 3
    41 => [83, 88, 85, 87, 89]  // ID товаров для категории 3
];

// Основная логика
$categoriesData = [];
foreach ($categoryProducts as $categoryId => $productIds) {
    $categoriesData[] = [
        'category_id' => $categoryId,
        'category' => getCategoryName($categoryId, $pdo),
        'products' => getProductsByIds($productIds, $pdo)
    ];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.0/dist/tailwind.min.css" rel="stylesheet">
    <title>Ryuo Store</title>
    <style>
        .notification {
            opacity: 1;
            transition: opacity 0.5s ease;
        }
        .underline-container {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .underline {
            height: 2px;
            background-color: gray;
            flex: 1;
            margin: 0 2px;
            cursor: pointer;
        }
        .underline.active {
            background-color: black;
        }
        .product-image-container {
            position: relative;
            padding-top: 40px; /* Высота для иконок и надписей */
            margin: 0;
            width: 100%;
            overflow: hidden; /* Обрезка краёв изображения */
            border-radius: 12px; /* Радиус скругления */
        }


        .product-image {
            margin: 0;
            width: 100%;
            height: 16rem;
            object-fit: cover;
            display: block;
            border-radius: 12px; /* Наследование скругления от контейнера */
        }


        .product-image-overlay {
            position: absolute;
            top: 40px; /* Соответствует padding-top контейнера */
            left: 0;
            width: 100%;
            height: calc(100% - 40px); /* Вычитаем высоту padding-top */
            display: flex;
            justify-content: space-between;
            margin: 0;
            padding: 0;
        }
        .product-image-overlay div {
            flex: 1;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="px-[20%] pt-4">
        <?php foreach ($categoriesData as $categoryData): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold mb-4 flex items-center">
                    <a href="/pages/category.php?category=<?= htmlspecialchars($categoryData['category_id']) ?>" class="flex items-center">
                        <span class="leading-none"><?= htmlspecialchars($categoryData['category']) ?></span>
                        <img src="/images/icons/arrow-right-outline.svg" alt="->" class="w-6 h-6 ml-2 relative" style="top: 1px;">
                    </a>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <?php
                    $count = 0;
                    foreach ($categoryData['products'] as $product):
                        if ($count < 4):
                    ?>
                    <div class="relative bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow" onmouseleave="resetImage(this)">
                        <a href="product.php?id=<?= htmlspecialchars($product['id']) ?>" class="block">
                            <div class="overflow-hidden relative product-image-container">
                                <?php
                                $additional_images = json_decode($product['additional_images'], true);
                                array_unshift($additional_images, $product['image']);
                                foreach ($additional_images as $index => $image): ?>
                                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-80 object-contain product-image rounded-md" style="display: <?= $index === 0 ? 'block' : 'none' ?>;">
                                <?php endforeach; ?>
                                <div class="product-image-overlay">
                                    <?php foreach ($additional_images as $index => $image): ?>
                                        <div onmouseover="showImage(this, <?= $index ?>, '<?= htmlspecialchars($product['id']) ?>')"></div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="underline-container">
                                    <?php foreach ($additional_images as $index => $image): ?>
                                        <div class="underline <?= $index === 0 ? 'active' : '' ?>" onmouseover="showImage(this, <?= $index ?>, '<?= htmlspecialchars($product['id']) ?>')"></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2 truncate">
                                    <?= htmlspecialchars($product['name']) ?>
                                </h3>
                                <div class="flex items-center space-x-2">
                                    <?php if ((float)$product['discount'] > 0): ?>
                                    <span class="text-sm text-gray-500 line-through">
                                        <?= htmlspecialchars(format_price($product['price'])) ?>₽
                                    </span>
                                    <span class="text-l font-bold text-gray-800">
                                        <?= htmlspecialchars(format_price($product['price'] - ($product['price'] * ($product['discount'] / 100)))) ?>₽
                                    </span>
                                    <?php else: ?>
                                    <span class="text-l font-bold text-gray-800">
                                        <?= htmlspecialchars(format_price($product['price'])) ?>₽
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php if ((float)$product['discount'] > 0): ?>
                        <div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold py-1 px-2 rounded">
                            SALE <?= htmlspecialchars(format_discount($product['discount'])) ?>%
                         </div>
                         <?php endif; ?>
                         <div class="absolute top-2 right-2 flex items-center space-x-2">
                             <?php if ($categoryData['category_id'] == 2): // Check if the product's category ID is 2 ?>
                             <div class="bg-green-500 text-white text-xs font-bold py-1 px-2 rounded">
                                 NEW
                             </div>
                             <?php endif; ?>
                             <button aria-label="toggle favorite">
                                 <img src="<?= is_favorite($product['id']) ? '/images/icons/heart-fav.svg' : '/images/icons/heart.svg' ?>"
                                      data-product-id="<?= $product['id'] ?>"
                                      data-fav="<?= is_favorite($product['id']) ? '1' : '0' ?>"
                                      class="heart-icon w-6 h-6"
                                      onclick="toggleFavorite(this, <?= $product['id'] ?>)">
                             </button>
                         </div>
                     </div>
                     <?php
                         $count++;
                         endif;
                     endforeach;
                     ?>
                 </div>
             </div>
         <?php endforeach; ?>
     </div>


 <script src="../backend/notifications.js"></script> <script>
      function toggleFavorite(element, productId) {
            fetch('../backend/favorites_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, action: element.dataset.fav === '1' ? 'remove' : 'add' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const isFavorite = element.dataset.fav === '1';
                    element.dataset.fav = isFavorite ? '0' : '1';
                    element.src = isFavorite ? '/images/icons/heart.svg' : '/images/icons/heart-fav.svg';
                    const actionMessage = isFavorite ? 'удален из избранного' : 'добавлен в избранное';
                    // Optional: Implement showNotification if you have a notification system
                    showNotification(`Товар успешно ${actionMessage}.`, 'green');
                    console.log(`Товар успешно ${actionMessage}.`); // Replace with your notification system
                } else {
                    // Optional: Implement showNotification if you have a notification system
                    showNotification(data.message || 'Произошла ошибка.', 'red');
                    console.error(data.message || 'Произошла ошибка.'); // Replace with your notification system
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                // Optional: Implement showNotification if you have a notification system
                showNotification('Не удалось обновить состояние избранного.', 'red');
                console.error('Не удалось обновить состояние избранного.'); // Replace with your notification system
            });
        }

     function showImage(element, index, productId) {
         const container = element.closest('.relative');
         const images = container.querySelectorAll('.product-image');
         const underlineElements = container.querySelectorAll('.underline');

         images.forEach((img, imgIndex) => {
             img.style.display = imgIndex === index ? 'block' : 'none';
         });

         underlineElements.forEach((underline, underlineIndex) => {
             underline.classList.toggle('active', underlineIndex === index);
         });
     }

     function resetImage(element) {
         const container = element.querySelector('.product-image-container');
         const images = container.querySelectorAll('.product-image');
         const underlineElements = container.querySelectorAll('.underline');

         images.forEach((img, imgIndex) => {
             img.style.display = imgIndex === 0 ? 'block' : 'none';
         });

         underlineElements.forEach((underline, underlineIndex) => {
             underline.classList.toggle('active', underlineIndex === 0);
         });
     }
     
     

 </script>
 </body>
 </html>