<?php
$host = 'sql105.infinityfree.com';
$db = 'if0_37280528_ryuo_store';
$user = 'if0_37280528';
$pass = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Ошибка подключения к базе данных: ' . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить товар</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
<div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-bold mb-4">Добавить товар</h2>
    <form action="add_product.php" method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label for="name" class="block text-gray-700">Название товара:</label>
            <input type="text" id="name" name="name" class="w-full border rounded p-2" required>
        </div>
        <div class="mb-4">
            <label for="description" class="block text-gray-700">Описание:</label>
            <textarea id="description" name="description" class="w-full border rounded p-2"></textarea>
        </div>
        <div class="mb-4">
            <label for="price" class="block text-gray-700">Цена:</label>
            <input type="number" step="0.01" id="price" name="price" class="w-full border rounded p-2" required>
        </div>
        <div class="mb-4">
            <label for="discount" class="block text-gray-700">Скидка:</label>
            <input type="number" step="0.01" id="discount" name="discount" class="w-full border rounded p-2">
        </div>
        <div class="mb-4">
            <label for="categories" class="block text-gray-700">Категории:</label>
            <div id="categories" class="space-y-2">
                <?php
                try {
                    function renderCategories($parentId = null, $pdo, $level = 0) {
                        $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id " . ($parentId === null ? "IS NULL" : "= ?"));
                        $stmt->execute($parentId === null ? [] : [$parentId]);

                        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<div class="flex items-center pl-' . ($level * 4) . '">';
                            echo '<input type="checkbox" id="category_' . $category['id'] . '" name="categories[]" value="' . $category['id'] . '" class="mr-2 category-checkbox" data-parent="' . $category['parent_id'] . '">';
                            echo '<label for="category_' . $category['id'] . '" class="text-gray-700">' . ($level > 0 ? '— ' : '') . $category['name'] . '</label>';
                            echo '</div>';

                            renderCategories($category['id'], $pdo, $level + 1);
                        }
                    }

                    renderCategories(null, $pdo);

                } catch (PDOException $e) {
                    echo "Ошибка при извлечении категорий: " . $e->getMessage();
                }
                ?>
            </div>
        </div>
        <div class="mb-4">
            <label for="color" class="block text-gray-700">Цвет:</label>
            <select id="color" name="color" class="w-full border rounded p-2 h-12" required>
                <option value="">Выберите цвет</option>
                <?php
                try {
                    $stmt = $pdo->query("SELECT * FROM colors");
                    while ($color = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $color['id'] . '" data-hex="' . $color['hex_code'] . '">' . $color['name'] . '</option>';
                    }
                } catch (PDOException $e) {
                    echo "Ошибка при извлечении цветов: " . $e->getMessage();
                }
                ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="image" class="block text-gray-700">Главное изображение:</label>
            <input type="file" id="image" name="image" class="w-full border rounded p-2" required>
        </div>
        <div class="mb-4">
            <label for="additional_images" class="block text-gray-700">Дополнительные изображения:</label>
            <input type="file" id="additional_images" name="additional_images[]" multiple class="w-full border rounded p-2">
        </div>
        <div class="mb-4">
            <label for="feature1" class="block text-gray-700">Особенность 1:</label>
            <input type="text" id="feature1" name="feature1" class="w-full border rounded p-2">
        </div>
        <div class="mb-4">
            <label for="feature2" class="block text-gray-700">Особенность 2:</label>
            <input type="text" id="feature2" name="feature2" class="w-full border rounded p-2">
        </div>
        <div class="mb-4">
            <label for="feature3" class="block text-gray-700">Особенность 3:</label>
            <input type="text" id="feature3" name="feature3" class="w-full border rounded p-2">
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Добавить товар</button>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('#color').select2({
            placeholder: 'Выберите цвет',
            templateResult: formatColor,
            templateSelection: formatColor
        });

        function formatColor(color) {
            if (!color.id) {
                return color.text;
            }
            let $color = $(
                '<span>' + color.text + '<span class="ml-2 inline-block w-4 h-4 rounded-full border border-gray-400" style="background-color: ' + $(color.element).data('hex') + ';"></span></span>'
            );
            return $color;
        }

        $('.category-checkbox').change(function() {
            let categoryId = $(this).val();
            let parentId = $(this).data('parent');
            let isChecked = $(this).prop('checked');

            function updateParent(parentId, check) {
                if (parentId) {
                    $('#category_' + parentId).prop('checked', check);
                    let grandParentId = $('#category_' + parentId).data('parent');
                    updateParent(grandParentId, check);
                }
            }
            updateParent(parentId, isChecked);
        });
    });
</script>
</body>
</html>