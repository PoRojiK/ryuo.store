<?php
// Подключение к базе данных
session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$host = 'sql105.infinityfree.com'; // Хост
$dbname = 'if0_37280528_ryuo_store'; // Имя базы данных
$username = 'if0_37280528'; // Имя пользователя
$password = 'm9RLB5iHMPr'; // Пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

include '../backend/notifications.php';

function getCategoryId($pdo, $eng_name, $parent_id = null) {
    // Normalize: convert dashes to spaces for comparison
    $eng_name = str_replace('-', ' ', $eng_name);
    
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE LOWER(eng_name) = LOWER(:eng_name) AND parent_id <=> :parent_id");
    $stmt->execute(['eng_name' => $eng_name, 'parent_id' => $parent_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : null;
}

function getCategoryBreadcrumb($pdo, $category_id) {
    $breadcrumb = [];
    
    // Get current category
    $current_id = $category_id;
    while ($current_id) {
        $stmt = $pdo->prepare("SELECT id, name, eng_name, parent_id FROM categories WHERE id = :id");
        $stmt->execute(['id' => $current_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) break;
        
        // Add category to beginning of breadcrumb
        array_unshift($breadcrumb, [
            'id' => $category['id'],
            'name' => $category['name'],
            'eng_name' => $category['eng_name'],
            'parent_id' => $category['parent_id']
        ]);
        
        // Move to parent
        $current_id = $category['parent_id'];
    }
    
    return $breadcrumb;
}

function getCategoryUrl($breadcrumb) {
    $url_parts = [];
    foreach ($breadcrumb as $category) {
        // Convert spaces to dashes and lowercase
        $url_parts[] = strtolower(str_replace(' ', '-', $category['eng_name']));
    }
    return '/category/' . implode('/', $url_parts);
}

// Получение eng_name категорий из URL
$category1 = isset($_GET['category1']) ? $_GET['category1'] : null;
$category2 = isset($_GET['category2']) ? $_GET['category2'] : null;
$category3 = isset($_GET['category3']) ? $_GET['category3'] : null;

// Определение ID категории с учетом parent_id
$category_id = null;
if ($category1) {
    $category_id = getCategoryId($pdo, $category1);
    if ($category2 && $category_id) {
        $category_id = getCategoryId($pdo, $category2, $category_id);
        if ($category3 && $category_id) {
            $category_id = getCategoryId($pdo, $category3, $category_id);
        }
    }
}

$category = null;
$breadcrumb = [];

if ($category_id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :category_id");
    $stmt->execute(['category_id' => $category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get breadcrumb for this category
    $breadcrumb = getCategoryBreadcrumb($pdo, $category_id);
}

if (!$category) {
    $category = ['name' => 'Все категории'];
}

// Инициализация переменных
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : '';
$color = isset($_GET['color']) ? (int)$_GET['color'] : 0;
$category_filter = isset($_GET['categories']) ? $_GET['categories'] : [];

// Формирование заголовка страницы
$page_title = !empty($search)
    ? "Результаты поиска: " . htmlspecialchars($search)
    : ($category_id ? "Товары категории: " . htmlspecialchars($category['name']) : "Все товары");

// Подготовка фильтров
$where_clauses = [];
$params = [];

// Поиск по названию товара или категории
if (!empty($search)) {
    $where_clauses[] = '(p.name LIKE :search OR cat.name LIKE :search)';
    $params['search'] = "%$search%";
}

// Фильтр по категориям
if (!empty($category_filter)) {
    $placeholders = [];
    foreach ($category_filter as $index => $cat_id) {
        $placeholders[] = ":cat_$index";
        $params["cat_$index"] = (int)$cat_id;
    }
    $where_clauses[] = "pc.category_id IN (" . implode(',', $placeholders) . ")";
} elseif ($category_id) {
    $where_clauses[] = "pc.category_id = :category_id";
    $params['category_id'] = $category_id;
}

// Фильтр по цвету
if ($color > 0) {
    $where_clauses[] = "p.color_id = :color_id";
    $params['color_id'] = $color;
}

// Сортировка
$order = '';
switch ($sort_by) {
    case 'price_asc':
        $order = 'ORDER BY IF(p.discount > 0, p.price - (p.price * p.discount / 100), p.price) ASC';
        break;
    case 'price_desc':
        $order = 'ORDER BY IF(p.discount > 0, p.price - (p.price * p.discount / 100), p.price) DESC';
        break;
    case 'name':
        $order = 'ORDER BY p.name ASC';
        break;
    default:
        $order = '';
}

// Построение SQL-запроса
$where_sql = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : '';
$query = "
    SELECT DISTINCT p.*, c.name AS color_name
    FROM products p
    INNER JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN colors c ON p.color_id = c.id
    LEFT JOIN categories cat ON pc.category_id = cat.id
    $where_sql
    $order
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение списка категорий и цветов
$categories = $pdo->query("SELECT id, name FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$colors = $pdo->query("SELECT DISTINCT c.id, c.name FROM colors c
                      INNER JOIN products p ON p.color_id = c.id
                      ORDER BY c.name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Функция форматирования суммы
function format_price($price) {
    return number_format($price, 0, ',', ' ');
}

// Функция форматирования скидки
function format_discount($discount) {
    return number_format($discount, 0);
}

// ID категорий для отдельных кнопок
$button_categories = [1, 2, 3, 11]; // Укажите нужные ID категорий
// ID категорий, которые нужно скрыть из фильтра
$hidden_categories = [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 28, 26]; // Укажите ID категорий, которые нужно скрыть
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Категория - <?= htmlspecialchars($category['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">




    <style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');
      
        body {
            overflow-y: scroll; /* Всегда показывать вертикальную полоску прокрутки */
            font-family: 'Montserrat', sans-serif;
        }

        input:checked + span {
            background-color: transparent; /* Убираем цвет фона */
        }
        input:checked + span:before {
            transform: translateX(24px);
            background-color: #ffffff; /* Оставляем цвет только для внутреннего круга переключателя */
        }

        label {
            border: none; /* Remove border */
        }
        img {
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }
        .discount-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background-color: rgba(107, 114, 128, 0.9);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: bold;
        }
        .price {
            position: absolute;
            bottom: 1rem;
            left: 1rem;
            font-weight: bold;
            margin-right: 2rem;
        }
        .price-old {
            color: gray;
            text-decoration: line-through;
            margin-right: 0.5rem;
        }
        .price-new {
            color: red;
            font-weight: bold;
        }
        .cart-icon {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            width: 2rem;
            height: 2rem;
            fill: #3182ce;
        }
        .heart-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 1.5rem;
            height: 1.5rem;
        }
        .product-name {
            margin-bottom: 2rem;
        }
        .product-card {
            height: 100%;
        }
        .category-scroll {
            max-height: 200px; /* Ограничение высоты блока */
            overflow-y: auto;  /* Добавляем вертикальный скролл */
            border: 1px solid #e5e7eb; /* Легкая граница */
            padding: 0.5rem;
            border-radius: 0.5rem;
         }
        .notification-success {
            background-color: #38a169; /* Зелёный цвет */
            color: #fff;
        }

        .notification-error {
            background-color: #e53e3e; /* Красный цвет */
            color: #fff;
        }

        .notification {
            transition: opacity 0.3s ease;
        }

    </style>
</head>

<script>
let filterTimeout;

    // Функция для автоматической отправки формы с задержкой
    function applyFiltersWithDelay() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
            document.querySelector('#filter-form').submit();
        }, 500); // Задержка в 500 мс
    }

    // Привязываем событие к элементам формы
    document.addEventListener('DOMContentLoaded', () => {
        const filterForm = document.querySelector('#filter-form');
        if (filterForm) {
            filterForm.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('input', applyFiltersWithDelay);
                input.addEventListener('change', applyFiltersWithDelay);
            });
        }
    });
</script>


<body class="bg-gray-100">
    <?php include '../components/navbar.php'; ?>


    <!-- Навигация и текущая категория -->
    <div class="px-[10%] mt-8 pt-20 py-4 mb-4">
        <nav class="uppercase text-gray-500 font-bold">
            <a href="/" class="hover:underline">RYUO</a>
            <?php if (!empty($breadcrumb)): ?>
                <?php
                $path = [];
                foreach ($breadcrumb as $index => $crumb):
                    $path[] = $crumb;
                    $url = getCategoryUrl($path);
                    $is_last = ($index === count($breadcrumb) - 1);
                ?>
                    <span class="font-bold"> » </span>
                    <?php if ($is_last): ?>
                        <span class="font-bold"><?= htmlspecialchars($crumb['name']) ?></span>
                    <?php else: ?>
                        <a href="<?= $url ?>" class="hover:underline"><?= htmlspecialchars($crumb['name']) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="font-bold"> » </span>
                <span class="font-bold">Все товары</span>
            <?php endif; ?>
        </nav>
        <h1 class="uppercase text-2xl font-bold text-gray-800"><?= $page_title ?></h1>
    </div>

    <!-- Основной контент -->
    <div class="flex pl-[10%] pr-[10%]">
        <!-- Левая панель сортировки -->
        <aside class="w-1/6 bg-white shadow-md p-4 rounded-lg">
            <style>
                aside {
                    align-self: flex-start; /* Aligns the element to the start of the container */
                    height: auto;          /* Ensures height adapts to content */
                    max-height: 100%;      /* Prevents excessive stretching */
                }
            </style>
        <h2 class="text-xl font-bold mb-4">Сортировка</h2>
        <form id="filter-form" action="" method="GET" class="flex flex-col gap-4">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category_id) ?>">

            <!-- Сортировка по цене -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">Сортировка по цене:</label>
                <select name="sort_by" class="w-full border rounded-lg p-2">
                    <option value="">Без сортировки</option>
                    <option value="price_asc" <?= $sort_by === 'price_asc' ? 'selected' : '' ?>>По возрастанию</option>
                    <option value="price_desc" <?= $sort_by === 'price_desc' ? 'selected' : '' ?>>По убыванию</option>
                </select>
            </div>

            <!-- Фильтр по цвету -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">Цвет:</label>
                <select name="color" class="w-full border rounded-lg p-2">
                    <option value="">Все</option>
                    <?php foreach ($colors as $c): ?>
                        <option value="<?= htmlspecialchars($c['id']) ?>" 
                                <?= $color == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Фильтр по категориям -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">Категории:</label>
                <div class="category-scroll flex flex-col gap-2">
                    <?php foreach ($categories as $cat): ?>
                        <?php if (!in_array($cat['id'], $hidden_categories)): ?> <!-- Проверка на скрытые категории -->
                            <label class="flex items-center">
                                <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat['id']) ?>"
                                    <?= in_array($cat['id'], $category_filter) ? 'checked' : '' ?> class="rounded">
                                <span class="ml-2"><?= htmlspecialchars($cat['name']) ?></span>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="text-lg font-bold mb-2">Популярные категории</h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($categories as $cat): ?>
                        <?php if (in_array($cat['id'], $button_categories)): ?>
                            <?php 
                                $is_checked = in_array($cat['id'], $category_filter); // Проверяем, активен ли фильтр
                            ?>
                            <label class="relative flex items-center cursor-pointer w-full px-4 py-2 transition duration-300 hover:bg-gray-100">
                                <span class="text-black mr-auto">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </span>
                                <input type="checkbox" class="hidden peer" name="categories[]" value="<?= htmlspecialchars($cat['id']) ?>" 
                                    <?= $is_checked ? 'checked' : '' ?>>
                                <span class="bg-gray-200 relative w-10 h-6 rounded-full transition duration-300 peer-checked:bg-blue-500 before:bg-white before:content-[''] before:absolute before:top-1 before:left-1 before:w-4 before:h-4 before:rounded-full peer-checked:before:transform peer-checked:before:translate-x-4">
                                </span>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

        </form>
    </aside>

<?php
session_start();

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
?>

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

</script>


<div class="flex-1 pl-8">
    <!-- Сетка с товарами -->
    <?php if (count($products) > 0): ?>
        <div class="flex flex-wrap -mx-4">
            <?php foreach ($products as $product): ?>
                <div class="w-full md:w-1/3 px-4 mb-6">
                    <div class="relative bg-white rounded-lg shadow-lg p-4 product-card">

                        <?php if ((float)$product['discount'] > 0): ?> <!-- Проверка скидки иконка слева сверху товара SALE -->
                            <div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold py-1 px-2 rounded">
                                SALE <?= htmlspecialchars(format_discount($product['discount'])) ?>%
                            </div>
                        <?php endif; ?>

                        <?php if ($categoryData['category_id'] == 2):?> <!-- Проверка категории товара если новый, то иконка справа сверху NEW -->
                            <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold py-1 px-2 rounded">
                                NEW
                            </div>
                        <?php endif; ?>

                        <a href="product.php?id=<?= htmlspecialchars($product['id']) ?>" class="block">
                            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="rounded-lg mb-4 ">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2 truncate">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                            <div class="price">
                                <?php if ((float)$product['discount'] > 0): ?>
                                    <span class="price-old"><?= htmlspecialchars(format_price($product['price'])) ?>₽</span>
                                    <span class="price-new"><?= htmlspecialchars(format_price($product['price'] - ($product['price'] * ($product['discount'] / 100)))) ?>₽</span>
                                <?php else: ?>
                                    <?= htmlspecialchars(format_price($product['price'])) ?>₽
                                <?php endif; ?>
                            </div>
                        </a>
                        <img src="/images/icons/cart.svg" alt="Корзина" class="cart-icon" onclick="addToCart(<?= htmlspecialchars($product['id']) ?>)">
                        <div>
                            <button>
                                <img src="<?= is_favorite($product['id']) ? '/images/icons/heart-fav.svg' : '/images/icons/heart.svg' ?>"
                                    data-product-id="<?= $product['id'] ?>"
                                    data-fav="<?= is_favorite($product['id']) ? '1' : '0' ?>"
                                    class="heart-icon"
                                    onclick="toggleFavorite(this, <?= $product['id'] ?>)">
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-700">В данной категории нет товаров.</p>
    <?php endif; ?>
</div>



</div>
<?php include '../components/footer.php'; ?>





</body>
</html>
