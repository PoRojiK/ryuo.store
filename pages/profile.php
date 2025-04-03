
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.0/dist/tailwind.min.css" rel="stylesheet">
  <title>Личный кабинет</title>
  <style>
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    input[type="radio"] { 
        appearance: none;
        background-color: #fff;
        border: 2px solid #000;
        width: 1rem;
        height: 1rem;
        border-radius: 50%; 
        display: inline-block; 
        position: relative; 
    } 
    input[type="radio"]:checked::before { 
        content: '';
        display: block;
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 50%;
        background-color: #000;
        position: absolute; top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    #deliveries {
        max-height: 700px;
        overflow-y: auto;
    }
  </style>


  
</head>
<body>
  <?php
  session_start();

  if (!isset($_SESSION['user_id'])) {
      header("Location: /auth");
      exit();
  }

  include '../components/navbar.php'; // Подключение файла navbar

  $host = 'sql105.infinityfree.com';
  $db = 'if0_37280528_ryuo_store';
  $user = 'if0_37280528';
  $pass = 'm9RLB5iHMPr';

  try {
      $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
      $stmt->execute([$_SESSION['user_id']]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
      echo 'Error: ' . $e->getMessage();
  }
  ?>

<div class="container mx-auto mt-8 pt-24">
    <div class="px-4 md:px-0">
        <nav class="uppercase text-gray-500 font-bold">
            <a href="/" class="hover:underline">RYUO</a><span class="font-bold"> » </span><span class="font-bold">Моя учетная запись</span>
        </nav>
        <h1 class="uppercase text-2xl font-bold text-gray-800">Профиль пользователя</h1>
    </div>
    <div class="flex mt-6">
        <!-- Sidebar -->
        <div class="w-1/4 p-4">
            <ul>
                <li class="py-2">
                    <a href="#info" class="tab-link text-gray-800 underline hover:text-blue-500" data-tab="info">Личная информация</a>
                </li>
                <li class="py-2">
                    <a href="#deliveries" class="tab-link text-gray-800 underline hover:text-blue-500" data-tab="deliveries">Доставки</a>
                </li>
                <li class="py-2">
                    <a href="#change_password" class="tab-link text-gray-800 underline hover:text-blue-500" data-tab="change_password">Изменить пароль</a>
                </li>
                <li class="py-2">
                    <a href="../backend/logout.php" class="text-gray-800 underline hover:text-blue-500">Выйти</a>
                </li>
            </ul>
        </div>

      <!-- Main content -->
      <div class="w-3/4 bg-white p-4">
        <div id="info" class="tab-content active">
          <h2 class="text-xl font-bold mb-4">Личная информация</h2>
          <form action="../backend/update_profile_info.php" method="POST">
            <div class="grid grid-cols-2 gap-4">
                <div>
                <div class="mb-4">
                    <label for="first_name" class="block text-gray-700">Имя</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div class="mb-4">
                    <label for="last_name" class="block text-gray-700">Фамилия</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-gray-700">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <button type="submit" class="w-full bg-black text-white py-2 mt-4 rounded-lg hover:bg-gray-600 transition-colors">Сохранить</button>
                </div>

                <div>
                <div class="mb-4">
                    <label for="birthdate" class="block text-gray-700">День рождения</label>
                    <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($user['birthdate']); ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700">Пол</label>
                    <div class="flex items-center">
                    <input type="radio" id="male" name="gender" value="Мужской" <?php if ($user['gender'] == 'Мужской') echo 'checked'; ?> class="mr-2">
                    <label for="male" class="mr-4">Мужской</label>
                    <input type="radio" id="female" name="gender" value="Женский" <?php if ($user['gender'] == 'Женский') echo 'checked'; ?> class="mr-2">
                    <label for="female">Женский</label>
                    </div>
                </div>
                </div>
            </div>
            </form>




        </div>

        <div id="deliveries" class="tab-content">
            <h2 class="text-xl font-bold mb-4">Доставки</h2>
            <?php
            try {
                // Получаем все заказы и товары для пользователя
                $stmt = $pdo->prepare("SELECT 
                                            o.id AS order_id, 
                                            o.created_at AS order_date, 
                                            o.total, 
                                            oi.quantity, 
                                            oi.size, 
                                            oi.price, 
                                            p.name AS product_name, 
                                            p.image AS product_image
                                        FROM orders o
                                        JOIN order_items oi ON o.id = oi.order_id
                                        JOIN products p ON oi.product_id = p.id
                                        WHERE o.user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($orders) {
                    $currentOrderId = null;
                    foreach ($orders as $order) {
                        // Начинаем новый заказ, если это другой заказ
                        if ($order['order_id'] !== $currentOrderId) {
                            if ($currentOrderId !== null) {
                                echo '</div>'; // Закрытие предыдущего заказа
                            }
                            $currentOrderId = $order['order_id'];
                            echo '<div class="mb-6 border-b pb-4">';
                            echo '<p><strong>Дата заказа:</strong> ' . htmlspecialchars($order['order_date']) . '</p>';
                            echo '<p><strong>Общая сумма:</strong> ' . htmlspecialchars($order['total']) . ' ₽</p>';
                        }

                        // Выводим информацию о товаре
                        echo '<div class="flex items-center mt-4">';
                        echo '<img src="' . htmlspecialchars($order['product_image']) . '" alt="' . htmlspecialchars($order['product_name']) . '" class="w-16 h-16 mr-4 rounded-lg border">';
                        echo '<div>';
                        echo '<p><strong>' . htmlspecialchars($order['product_name']) . '</strong> (Размер: ' . htmlspecialchars($order['size']) . ')</p>';
                        echo '<p>Количество: ' . htmlspecialchars($order['quantity']) . '</p>';
                        echo '<p><strong>Цена:</strong> ' . htmlspecialchars($order['price']) . ' ₽</p>';
                        echo '</div>';
                        echo '</div>'; // Закрытие товара
                    }
                    echo '</div>'; // Закрытие последнего заказа
                } else {
                    echo '<p>У вас нет доставок.</p>';
                }
            } catch (PDOException $e) {
                echo 'Ошибка: ' . $e->getMessage();
            }
            ?>
        </div>

        <div id="change_password" class="tab-content">
          <h2 class="text-xl font-bold mb-4">Изменить пароль</h2>
            <form action="../backend/change_password_handler.php" method="POST" class="max-w-sm mr-auto">
            <div class="mb-4">
                <label for="current_password" class="block text-gray-700">Текущий пароль</label>
                <input type="password" id="current_password" name="current_password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div class="mb-4">
                <label for="new_password" class="block text-gray-700">Новый пароль</label>
                <input type="password" id="new_password" name="new_password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <button type="submit" class="w-full bg-black text-white py-2 rounded-lg hover:bg-gray-600 transition-colors">Сохранить</button>
            </form>


        </div>
      </div>
    </div>
  </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', function(event) {
                event.preventDefault();

                // Убираем классы 'active' у всех вкладок
                tabs.forEach(t => {
                    t.classList.remove('text-black', 'no-underline');
                    t.classList.add('text-gray-800', 'underline');
                });

                // Убираем класс 'active' у всех контентов
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });

                // Добавляем стили для активной вкладки
                this.classList.add('text-black', 'no-underline');
                this.classList.remove('text-gray-800', 'underline');

                // Отображаем соответствующий контент
                const targetTab = this.getAttribute('data-tab');
                document.getElementById(targetTab).classList.add('active');
            });
        });

        // Устанавливаем начальный стиль для первой активной вкладки
        const activeTab = document.querySelector('.tab-link[data-tab="info"]');
        activeTab.classList.add('text-black', 'no-underline');
        activeTab.classList.remove('text-gray-800', 'underline');
    });
</script>

<?php include '../components/footer.php'; ?>
</body>
</html>
