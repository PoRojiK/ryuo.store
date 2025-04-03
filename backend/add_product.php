<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
$host = 'sql105.infinityfree.com';
$db = 'if0_37280528_ryuo_store';
$user = 'if0_37280528';
$pass = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Настроим обработку ошибок
} catch (PDOException $e) {
    echo 'Ошибка подключения к базе данных: ' . $e->getMessage();
    exit; // Завершаем выполнение, если подключение не удалось
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из формы
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $discount = $_POST['discount'];
    $feature1 = $_POST['feature1'];
    $feature2 = $_POST['feature2'];
    $feature3 = $_POST['feature3'];
    $color_id = $_POST['color'];
    $categories = $_POST['categories']; // Массив категорий

// Папка для сохранения изображений
$base_folder = realpath(__DIR__ . '/../images/products/');
$product_folder = $base_folder . '/' . uniqid() . '_' . date('Y-m-d_H-i-s');

if (!is_dir($product_folder)) {
    if (!mkdir($product_folder, 0777, true)) {
        echo 'Ошибка создания папки для продукта!';
        exit;
    }
}

// Загрузка главного изображения
$image = $product_folder . '/main_' . basename($_FILES['image']['name']);
if (!move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
    echo 'Ошибка загрузки главного изображения!<br>';
    echo 'Временный путь файла: ' . $_FILES['image']['tmp_name'] . '<br>';
    echo 'Папка для загрузки: ' . $product_folder . '<br>';
    if (!file_exists($_FILES['image']['tmp_name'])) {
        echo 'Файл не существует во временной директории!<br>';
    }
    if (!is_writable(dirname($image))) {
        echo 'Папка для загрузки недоступна для записи!<br>';
    }
    exit;
}

// Преобразуем путь главного изображения в относительный для веб-доступа
$image_relative = '/images/products/' . basename($product_folder) . '/main_' . basename($_FILES['image']['name']);

echo 'Главное изображение успешно загружено!<br>';
echo 'Путь к изображению: ' . $image_relative . '<br>';

// Загрузка дополнительных изображений
$additional_images = [];
foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
    if ($tmp_name) { // Проверяем, передан ли файл
        $additional_image_path = $product_folder . '/additional_' . basename($_FILES['additional_images']['name'][$key]);
        if (!move_uploaded_file($tmp_name, $additional_image_path)) {
            echo 'Ошибка загрузки дополнительного изображения с индексом ' . $key . '!<br>';
            echo 'Временный путь файла: ' . $tmp_name . '<br>';
            echo 'Путь для сохранения: ' . $additional_image_path . '<br>';
            if (!file_exists($tmp_name)) {
                echo 'Файл не существует во временной директории!<br>';
            }
            if (!is_writable(dirname($additional_image_path))) {
                echo 'Папка для загрузки недоступна для записи!<br>';
            }
            exit;
        }
        // Преобразуем путь дополнительных изображений в относительный для веб-доступа
        $additional_images[] = '/images/products/' . basename($product_folder) . '/additional_' . basename($_FILES['additional_images']['name'][$key]);
    }
}

$additional_images_json = json_encode($additional_images, JSON_UNESCAPED_SLASHES);

echo 'Дополнительные изображения успешно загружены!<br>';
echo 'Список дополнительных изображений: ' . $additional_images_json . '<br>';


    // Вставка данных в таблицу products
    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, additional_images, discount, feature1, feature2, feature3, color_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $image_relative, $additional_images_json, $discount, $feature1, $feature2, $feature3, $color_id]);
        $product_id = $pdo->lastInsertId();

        // Вставка связи с категориями (многие ко многим)
        foreach ($categories as $category_id) {
            $stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            $stmt->execute([$product_id, $category_id]);
        }

        echo "Товар успешно добавлен!";
    } catch (PDOException $e) {
        echo 'Ошибка при добавлении товара: ' . $e->getMessage();
    }
}
?>
