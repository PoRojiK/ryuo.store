<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include '../components/navbar.php';

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
        SELECT products.id AS product_id, products.name, products.image, cart.quantity, products.price, products.discount, cart.size 
        FROM cart 
        JOIN products ON cart.product_id = products.id 
        WHERE cart.user_id = ?
    ");

    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Вычисление подытога
    $total = 0;
    foreach ($cart_items as $item) {
        $discounted_price = $item['price'] * (1 - ($item['discount'] / 100));
        $total += $discounted_price * $item['quantity'];
    }

    // Сохранение данных при отправке формы
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = $_POST['first_name'] ?? $user['first_name'];
        $last_name = $_POST['last_name'] ?? $user['last_name'];
        $country = $_POST['country'] ?? '';
        $city = $_POST['city'] ?? '';
        $zip_code = $_POST['zip_code'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? $user['email'];
        $order_note = $_POST['order_note'] ?? '';

        // Проверка обязательных полей
        if (empty($first_name) || empty($last_name) || empty($city) || empty($zip_code) || empty($phone)) {
            die('Все поля обязательны для заполнения.');
        }

        $pdo->beginTransaction();
        try {
            // Обновление данных пользователя
            $stmt = $pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ? 
                WHERE id = ?
            ");
            $stmt->execute([$first_name, $last_name, $email, $_SESSION['user_id']]);

            // Сохранение заказа
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total, note, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $total, $order_note]);
            $order_id = $pdo->lastInsertId();

            // Сохранение товаров из корзины
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, size, price) 
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                $discounted_price = $item['price'] * (1 - ($item['discount'] / 100));
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['size'],
                    $discounted_price
                ]);
            }

            // Очистка корзины
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            $pdo->commit();

            // Перенаправление на главную страницу с параметром успешного заказа
            header('Location: ../pages/main.php?order_success=1');
            exit;


        } catch (PDOException $e) {
            $pdo->rollBack();
            die('Ошибка при оформлении заказа: ' . $e->getMessage());
        }
    }
} catch (PDOException $e) {
    die('Ошибка базы данных: ' . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.0/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #ece9e6, #ffffff);
    }
    .fade-enter {
      opacity: 0;
      transform: translateY(20px);
    }
    .fade-enter-active {
      opacity: 1;
      transform: translateY(0);
      transition: opacity 0.5s, transform 0.5s;
    }
  </style>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('delivery-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Отменяет стандартное поведение формы (перезагрузку страницы)

        // Создаем новый объект FormData для отправки данных формы
        const formData = new FormData(form);

        // Создаем новый запрос для отправки данных на сервер
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Проверка на успешное оформление заказа
            if (data.includes('Ошибка')) {
                alert('Ошибка при оформлении заказа: ' + data);
            } else {
                // Перенаправление на главную страницу
                window.location.href = 'http://ryuo.store/pages/main.php?order_success=1';
            }
        })
        .catch(error => {
            console.error('Ошибка при отправке формы:', error);
            alert('Ошибка при оформлении заказа. Попробуйте еще раз.');
        });
    });
  });
</script>



  <script>
    document.addEventListener('DOMContentLoaded', () => {
        const formContainer = document.querySelector('.form-container');
        formContainer.classList.add('fade-enter');
        setTimeout(() => formContainer.classList.add('fade-enter-active'), 0);
        updateDeliveryCost();
    });

    function updateDeliveryCost() {
        const city = document.getElementById('city').value;
        const deliveryOption = document.querySelector('input[name="delivery_option"]:checked').value;
        let deliveryCost = 0;

        if (deliveryOption === 'cdek') {
            if (city.toLowerCase() === 'санкт-петербург') {
                deliveryCost = 300;
            } else {
                deliveryCost = 500;
            }
        } else if (deliveryOption === 'post') {
            deliveryCost = 200;
        }

        document.getElementById('delivery-cost').textContent = formatPrice(deliveryCost);
        updateFinalTotal();
    }

    function updateFinalTotal() {
        const deliveryCost = parseInt(document.getElementById('delivery-cost').textContent.replace(/\s/g, ''), 10);
        const subtotal = parseInt(document.getElementById('subtotal').dataset.total.replace(/\s/g, ''), 10);
        const finalTotal = subtotal + deliveryCost;
        document.getElementById('final-total').textContent = formatPrice(finalTotal);
    }

    function formatPrice(price) {
        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
    }
  </script>
</head>
<body class="bg-gray-100">

<div class="container mx-auto mt-10 pt-24">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Левая часть: Данные для оплаты и доставки -->
    <div class="col-span-2 bg-white p-10 rounded-xl shadow-2xl">
      <h2 class="text-3xl font-bold mb-6 text-center text-black">Информация для оплаты и доставки</h2>
      <form action="" method="POST" id="delivery-form">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="mb-4">
            <label for="first_name" class="block text-gray-700 font-medium mb-2">Имя</label>
            <input type="text" id="first_name" name="first_name" class="w-full px-5 py-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($user['first_name'] ?? '') ?>"  required>
          </div>
          <div class="mb-4">
            <label for="last_name" class="block text-gray-700 font-medium mb-2">Фамилия</label>
            <input type="text" id="last_name" name="last_name" class="w-full px-5 py-3 border border-gray-300 rounded-lg" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
          </div>
          <div class="mb-4 col-span-2 grid grid-cols-2 gap-6">
            <div>
              <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
              <input type="email" id="email" name="email" class="w-full px-5 py-3 border border-gray-300 rounded-lg" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            <div>
              <label for="phone" class="block text-gray-700 font-medium mb-2">Телефон</label>
              <input type="tel" id="phone" name="phone" class="w-full px-5 py-3 border border-gray-300 rounded-lg" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
            </div>
          </div>
          <div class="mb-4">
            <label for="zip_code" class="block text-gray-700 font-medium mb-2">Почтовый индекс</label>
            <input type="text" id="zip_code" name="zip_code" class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black" required>
          </div>
          <div class="mb-4">
            <label for="city" class="block text-gray-700 font-medium mb-2">Населённый пункт</label>
            <input type="text" id="city" name="city" class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black" required onchange="updateDeliveryCost()">
          </div>
        </div>
        <div class="mb-4">
          <label for="order_note" class="block text-gray-700 font-medium mb-2">Примечание к заказу</label>
          <textarea id="order_note" name="order_note" rows="4" class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black" placeholder="Примечания к вашему заказу, например, особые пожелания отделу доставки."></textarea>
        </div>
      </form>
    </div>

    <!-- Правая часть: Список товаров -->
    <div class="bg-white p-10 rounded-xl shadow-2xl">
      <h2 class="text-3xl font-bold mb-6 text-center text-black">Ваш заказ</h2>
      <div class="space-y-4">
        <?php if (!empty($cart_items)): ?>
          <?php foreach ($cart_items as $item): ?>
            <div class="flex items-center gap-4">
              <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-16 h-16 object-cover rounded-lg">
              <div>
                <h3 class="text-lg font-semibold"><?= htmlspecialchars($item['name']) ?></h3>
                <p class="text-sm text-gray-600">Размер: <?= htmlspecialchars($item['size']) ?></p>
                <p class="text-sm text-gray-600">Количество: <?= htmlspecialchars($item['quantity']) ?></p>
                <p class="text-sm text-black font-bold">
                    <?php
                    $discounted_price = $item['price'] * (1 - ($item['discount'] / 100));
                    echo number_format($discounted_price * $item['quantity'], 0, '', ' ') . ' ₽';
                    ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="border-t border-gray-300 pt-4 mt-4">
            <h3 class="text-2xl font-bold mb-4 text-center text-black">Доставка</h3>
            <div class="mb-4">
              <label class="block text-gray-700 font-medium mb-2">
                <input type="radio" name="delivery_option" value="post" checked onchange="updateDeliveryCost()"> Почта России<span id="post-cost"></span>
              </label>
            </div>
            <div class="mb-4">
              <label class="block text-gray-700 font-medium mb-2">
                <input type="radio" name="delivery_option" value="cdek" onchange="updateDeliveryCost()"> СДЭК - до пункта<span id="cdek-cost"></span>
              </label>
            </div>
            <div class="flex justify-between text-gray-700 text-lg font-semibold">
              <span>Подытог:</span>
              <span id="subtotal" data-total="<?= number_format($total, 0, '', '') ?>">
                <?= number_format($total, 0, '', ' ') ?> ₽
              </span>
            </div>
            <div class="flex justify-between text-gray-700 text-lg font-semibold mt-2">
              <span>Стоимость доставки:</span>
              <span id="delivery-cost">200 ₽</span>
            </div>
            <div class="flex justify-between text-gray-700 text-2xl font-bold mt-4">
              <span>Итого:</span>
              <span id="final-total"><?= number_format($total + 200, 0, '', ' ') ?> ₽</span>
            </div>
            <div class="mt-6">
              <button type="submit" form="delivery-form" class="w-full bg-black text-white py-3 rounded-lg hover:bg-gray-800 transition-colors duration-300">Оформить заказ</button>
            </div>
          </div>
        <?php else: ?>
          <p class="text-gray-600">Ваша корзина пуста.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function openPickupPointSelector() {
  alert("Функция выбора пункта выдачи на карте еще не реализована.");
}

document.addEventListener('DOMContentLoaded', () => {
  updateDeliveryCost();
});
</script>
</body>
<?php include '../components/footer.php'; ?>
</html>
