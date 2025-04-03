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

// Проверяем, авторизован ли пользователь
$is_logged_in = isset($_SESSION['user_id']);
if (!isset($_SESSION['user_id'])) {
    $_SESSION['notification'] = [
        'type' => 'red', // Тип уведомления (цвет Tailwind)
        'message' => 'Только авторизованные пользователи могут добавлять товары в избранное.'
    ];
}

include '../backend/notifications.php';

// Получение ID товара из URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id === 0) {
    die("Товар не указан.");
}

// Получение информации о товаре из базы данных
$query = "SELECT * FROM products WHERE id = :product_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['product_id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Товар не найден.");
}

// Преобразование JSON строки с дополнительными изображениями в массив
$additional_images = json_decode($product['additional_images'], true);

// Получение информации о том, является ли товар избранным
$is_favorite = false;
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute(['user_id' => $_SESSION['user_id'], 'product_id' => $product_id]);
    $is_favorite = $stmt->fetchColumn() > 0;
}

// Функция форматирования суммы в нужный формат
function format_price($price) {
    return number_format($price, 0, ',', ' ');
}

// Функция форматирования скидки в целые числа
function format_discount($discount) {
    return number_format($discount, 0);
}

// Рассчет цены со скидкой
$discount_price = $product['price'] - ($product['price'] * ($product['discount'] / 100));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товар - <?= htmlspecialchars($product['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .thumbnail {
            cursor: pointer;
            max-width: 60px;
            margin-bottom: 1rem;
            border: 2px solid transparent;
            transition: border-color 0.2s ease;
        }
        .thumbnail.active {
            border-color: #000;
        }
        .main-image-wrapper {
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            height: auto;
        }
        .main-image {
            display: block;
            width: 100%;
            height: auto;
        }
        .zoom-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: auto;
            transform-origin: center center;
            pointer-events: none;
            transition: transform 0.1s ease-out;
            visibility: hidden;
        }
        .main-image-wrapper:hover .zoom-image {
            visibility: visible;
        }
        .cart-icon, .heart-icon {
            cursor: pointer;
        }
        .cart-icon {
            width: 1.5rem;
            height: 1.5rem;
        }
        .heart-icon {
            width: 1.5rem;
            height: 1.5rem;
            margin-left: 0.5rem;
        }
        .discount-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background-color: rgba(107, 114, 128, 0.9);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .content-wrapper {
            display: flex;
            flex-direction: row;
            padding-left: 15%;
            padding-right: 20%;
            gap: 1rem;
        }
        .text-content {
            font-size: 0.75rem;
            width: 40%;
            flex: 1;
        }
        .price-old {
            color: gray;
            text-decoration: line-through;
            margin-right: 0.5rem;
        }
        .price-new {
            color: red;
            font-size: 1.25rem;
        }
        .image-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .thumbnail-wrapper {
            display: flex;
            flex-direction: column;
            margin-right: 0.5rem;
        }

        .size-option {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 2rem; /* Делаем квадратными */
            height: 2rem; /* Делаем квадратными */
            padding: 0;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem; /* Закругленные углы */
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        .size-option:hover {
            background-color: #d1d5db;
        }
        .size-option-selected {
            background-color: #000;
            color: #fff;
            border-color: #000;
        }
        .hidden {
            display: none;
        }

    </style>
    <script src="../backend/notifications.js"></script> <!-- Подключение файла с функцией уведомлений -->

    <script>
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


        // Функция для изменения главного изображения и обновления активного класса на миниатюрах
        function changeImage(src, thumbnailElement) {
            const mainImage = document.getElementById('mainImage');
            const zoomImage = document.getElementById('zoomImage');
            mainImage.src = src;
            zoomImage.src = src;
            
            // Удаляем класс active у всех миниатюр
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            
            // Добавляем класс active к выбранной миниатюре
            thumbnailElement.classList.add('active');
        }

        // Функция для увеличения изображения
        document.addEventListener('DOMContentLoaded', () => {
            const mainImageWrapper = document.querySelector('.main-image-wrapper');
            const zoomImage = document.getElementById('zoomImage');
            
            let zoomed = false; // Флаг состояния зума

            mainImageWrapper.addEventListener('mousemove', (e) => {
                const rect = mainImageWrapper.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const zoomFactor = 2; // Коэффициент увеличения

                zoomImage.style.transform = `scale(${zoomFactor})`;
                zoomImage.style.transformOrigin = `${(x / rect.width) * 100}% ${(y / rect.height) * 100}%`;
            });

            mainImageWrapper.addEventListener('mouseenter', () => {
                zoomImage.style.visibility = 'visible';
            });

            mainImageWrapper.addEventListener('mouseleave', () => {
                zoomImage.style.visibility = 'hidden';
                zoomImage.style.transform = 'scale(1)';
            });
            
            // Устанавливаем первую миниатюру как активную при загрузке страницы
            const firstThumbnail = document.querySelector('.thumbnail');
            if (firstThumbnail) {
                firstThumbnail.classList.add('active');
            }
        });

        //обработка текущего размера
        document.addEventListener('DOMContentLoaded', () => {
            const sizeOptions = document.querySelectorAll('.size-option');

            // Нет изначально установленного размера
            let currentSize = null;

            // Добавляем обработчики для выбора размера
            sizeOptions.forEach(option => {
                option.addEventListener('click', function () {
                    // Сбрасываем выделение со всех опций
                    sizeOptions.forEach(opt => opt.classList.remove('size-option-selected'));

                    // Устанавливаем стиль для текущей выбранной кнопки
                    this.classList.add('size-option-selected');

                    // Сохраняем текущий выбранный размер
                    currentSize = this.previousElementSibling.value;

                    // Ставим радио кнопку в состояние checked
                    this.previousElementSibling.checked = true;

                    console.log(`Текущий размер: ${currentSize}`);
                });
            });
        });

document.addEventListener('DOMContentLoaded', () => {
    // Обработчик клика на кнопку "Добавить в корзину"
    document.getElementById('addToCartButton').addEventListener('click', function () {
        // Проверка: авторизован ли пользователь
        const isLoggedIn = <?= json_encode($is_logged_in) ?>;
        if (!isLoggedIn) {
            showNotification('Пожалуйста, авторизуйтесь, чтобы добавить товар в корзину.', 'red');
            return;
        }

        // Проверка: выбран ли размер
        const selectedSize = document.querySelector('input[name="size"]:checked');
        if (!selectedSize) {
            showNotification('Пожалуйста, выберите размер перед добавлением в корзину.', 'red');
            return;
        }

        // Если все проверки пройдены, отправляем данные на сервер
        const size = selectedSize.value;
        const productId = <?= $product_id ?>;

        fetch('../backend/cart_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, size: size, action: 'add' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Товар успешно добавлен в корзину.', 'green');
            } else {
                showNotification(data.message || 'Произошла ошибка при добавлении товара.', 'red');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            showNotification('Не удалось добавить товар в корзину.', 'red');
        });
    });
});

// Функция для отображения уведомлений
function showNotification(message, color) {
    // Проверяем, существует ли уже уведомление
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Создаём новое уведомление
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 bg-${color}-500 text-white py-2 px-4 rounded shadow-md z-50`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Удаляем уведомление через 3 секунды
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
    </script>
</head>
<body class="bg-gray-100">
    <?php include '../components/navbar.php'; ?>
    <div class="flex py-8 pt-24 content-wrapper">
        <div class="thumbnail-wrapper">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="Главное изображение" class="thumbnail active" onclick="changeImage('<?= htmlspecialchars($product['image']) ?>', this)">
            <?php foreach ($additional_images as $image): ?>
                <img src="<?= htmlspecialchars($image) ?>" alt="Дополнительное изображение" class="thumbnail" onclick="changeImage('<?= htmlspecialchars($image) ?>', this)">
            <?php endforeach; ?>
        </div>

        
        <div class="flex flex-row">
            <div class="image-content relative main-image-wrapper">
                <img id="mainImage" src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="main-image mb-4">
                <img id="zoomImage" src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="zoom-image">
                <?php if ((float)$product['discount'] > 0): ?>
                    <div class="discount-badge">-<?= format_discount($product['discount']) ?>%</div>
                <?php endif; ?>
            </div>

            <div class="text-content ml-4">
                <div class="bg-white rounded-lg shadow-lg p-6 relative">
                    <div class="flex items-center mb-2">
                        <h1 class="text-lg font-bold"><?= htmlspecialchars($product['name']) ?></h1>
                        <img src="<?= $is_favorite ? '/images/icons/heart-fav.svg' : '/images/icons/heart.svg' ?>"
                             data-fav="<?= $is_favorite ? '1' : '0' ?>"
                             class="heart-icon"
                             onclick="toggleFavorite(this, <?= $product_id ?>)">
                    </div>
                    <div class="price text-md mb-2 flex items-center">
                        <?php if ((float)$product['discount'] > 0): ?>
                            <span class="price-old"><?= format_price($product['price']) ?>₽</span>
                            <span class="price-new"><?= format_price($discount_price) ?>₽</span>
                        <?php else: ?>
                            <span class="price-old"><?= format_price($product['price']) ?>₽</span>
                        <?php endif; ?>
                    </div>

                            <!-- ... существующий контент ... -->
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex space-x-2">
                            <label class="flex items-center">
                                <input type="radio" name="size" value="S" class="hidden">
                                <span class="size-option w-8 h-8 flex justify-center items-center border rounded-lg cursor-pointer">S</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="size" value="M" class="hidden">
                                <span class="size-option w-8 h-8 flex justify-center items-center border rounded-lg cursor-pointer">M</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="size" value="L" class="hidden">
                                <span class="size-option w-8 h-8 flex justify-center items-center border rounded-lg cursor-pointer">L</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="size" value="XL" class="hidden">
                                <span class="size-option w-8 h-8 flex justify-center items-center border rounded-lg cursor-pointer">XL</span>
                            </label>
                        </div>
                        <button class="bg-black text-white px-4 py-2 rounded" onclick="document.getElementById('sizeChartModal').classList.remove('hidden')">SIZE</button>
                    </div>
                            <!-- ... существующий контент ... -->


                    <p class="text-sm mb-4"><?= htmlspecialchars($product['description']) ?></p>
                    <div>
                        <h2 class="text-md font-bold mb-2">Характеристики</h2>
                        <ul class="list-disc list-inside">
                            <?php if (!empty($product['feature1'])): ?>
                                <li><?= htmlspecialchars($product['feature1']) ?></li>
                            <?php endif; ?>
                            <?php if (!empty($product['feature2'])): ?>
                                <li><?= htmlspecialchars($product['feature2']) ?></li>
                            <?php endif; ?>
                            <?php if (!empty($product['feature3'])): ?>
                                <li><?= htmlspecialchars($product['feature3']) ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="mt-6">
                        <button id="addToCartButton" class="bg-black text-white px-4 py-2 rounded w-full">
                            Добавить в корзину
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    <?php include '../components/footer.php'; ?>
    <!-- Всплывающее окно с размерной сеткой -->
<div id="sizeChartModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">Размерная сетка</h2>
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="px-4 py-2">Размер</th>
                    <th class="px-4 py-2">Грудь (см)</th>
                    <th class="px-4 py-2">Талия (см)</th>
                    <th class="px-4 py-2">Бедра (см)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border px-4 py-2">S</td>
                    <td class="border px-4 py-2">88-92</td>
                    <td class="border px-4 py-2">68-72</td>
                    <td class="border px-4 py-2">94-98</td>
                </tr>
                <tr>
                    <td class="border px-4 py-2">M</td>
                    <td class="border px-4 py-2">92-96</td>
                    <td class="border px-4 py-2">72-76</td>
                    <td class="border px-4 py-2">98-102</td>
                </tr>
                <tr>
                    <td class="border px-4 py-2">L</td>
                    <td class="border px-4 py-2">96-100</td>
                    <td class="border px-4 py-2">76-80</td>
                    <td class="border px-4 py-2">102-106</td>
                </tr>
                <tr>
                    <td class="border px-4 py-2">XL</td>
                    <td class="border px-4 py-2">100-104</td>
                    <td class="border px-4 py-2">80-84</td>
                    <td class="border px-4 py-2">106-110</td>
                </tr>
            </tbody>
        </table>
        <div class="flex justify-end mt-4">
            <button class="bg-gray-500 text-white px-4 py-2 rounded" onclick="document.getElementById('sizeChartModal').classList.add('hidden')">Закрыть</button>
        </div>
    </div>
</div>

</body>
</html>