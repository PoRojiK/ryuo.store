<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.0/dist/tailwind.min.css" rel="stylesheet">
    <title>Главная страница</title>
</head>

<body class="bg-white text-black fixed">
    <!-- Navbar -->
    <div class="fixed top-0 left-0 w-full z-50 shadow-md bg-white">
        <nav class="flex justify-between items-center py-2" style="padding-left: 20%; padding-right: 20%;">
            <!-- Logo Section -->
            <div>
                <img src="/images/icons/logo.svg" alt="ryuo" class="w-10 h-10 cursor-pointer"
                    onclick="window.location.href='https://ryuo.store'" />
            </div>

            <!-- Search Bar Section -->
            <div class="relative w-1/2">
                <input type="text" id="search-input" placeholder="Search"
                    class="w-full py-2 pl-10 text-sm bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-gray-800"
                    onkeydown="if (event.keyCode === 13) { window.location.href='/category/' + encodeURIComponent(this.value); }" />

                <span class="absolute top-1/2 transform -translate-y-1/2 left-3 text-lg text-gray-500">
                    <img src="/images/icons/search.svg" alt="Search Icon" class="w-4 h-4" />
                </span>
            </div>

            <!-- Icons Section -->
            <div class="flex items-center space-x-6">
                <!-- Favorites Icon -->
                <button class="relative" onclick="window.location.href='/favorites'">
                    <img src="/images/icons/heart.svg" alt="Favorites Icon" class="w-8 h-8 text-gray-700" />
                    <span id="favorites-count"
                        class="absolute -top-1 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center hidden">
                        0
                    </span>
                </button>

                <!-- Cart Icon -->
                <button class="relative" onclick="window.location.href='/cart'">
                    <img src="/images/icons/cart.svg" alt="Cart Icon" class="w-8 h-8 text-gray-700" />
                    <span id="cart-count"
                        class="absolute -top-1 -right-2 bg-green-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center hidden">
                        0
                    </span>
                </button>

                <!-- Profile Icon -->
                <div class="relative group flex items-center justify-center">
                    <button onclick="window.location.href='/profile'">
                        <img src="/images/icons/Profile-Icon.svg" alt="Profile Icon" class="w-8 h-8 text-gray-700" />
                        <span id="notifications-count"
                            class="absolute -top-1 -right-2 bg-blue-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center hidden">
                            0
                        </span>
                    </button>
                </div>

            </div>
        </nav>
    </div>

<?php
  function renderSubCategories($pdo, $parentId) {
      $stmt = $pdo->prepare('SELECT * FROM categories WHERE parent_id = ?');
      $stmt->execute([$parentId]);
      $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if ($subCategories) {
          $html = '<div class="sub-menu-container sticky top-[var(--header-height)] z-40 opacity-0 group-hover:opacity-100 transition-opacity duration-300">';
          $html .= '<div class="w-full mx-auto px-[20%] fixed hidden group-hover:block w-full left-0 top-[var(--header-height)] bg-white shadow-lg py-6 z-50 ">';
          $html .= '<div class="grid grid-cols-4 gap-8">';

          foreach ($subCategories as $subCategory) {
              // Get category path for link
              $categoryPath = getCategoryPath($pdo, $subCategory['id']);
              $url = '/category/' . $categoryPath;
              
              $html .= '<div>';
              $html .= '<a href="' . $url . '"
                  class="text-gray-500 dark:text-gray-400 hover:text-blue-500 font-semibold block mb-2 text-sm">';
              $html .= htmlspecialchars($subCategory['name']);
              $html .= '</a>';

              $stmtSub = $pdo->prepare('SELECT * FROM categories WHERE parent_id = ?');
              $stmtSub->execute([$subCategory['id']]);
              $subSubCategories = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

              if ($subSubCategories) {
                  $html .= '<div class="w-[5rem] h-px bg-gray-200 dark:bg-gray-700 my-2"></div>';

                  $html .= '<ul>';
                  foreach ($subSubCategories as $subSubCategory) {
                      // Get category path including all parents
                      $subSubPath = getCategoryPath($pdo, $subSubCategory['id']);
                      $subSubUrl = '/category/' . $subSubPath;
                      
                      $html .= '<li>';
                      $html .= '<a href="' . $subSubUrl . '"
                          class="text-gray-500 dark:text-gray-400 hover:text-blue-500 block py-1 text-xs">';
                      $html .= htmlspecialchars($subSubCategory['name']);
                      $html .= '</a>';
                      $html .= '</li>';
                  }
                  $html .= '</ul>';
              }

              $html .= '</div>';
          }

          $html .= '</div>';
          $html .= '</div>';
          $html .= '</div>';
          $html .= '</div>';

          return $html;
      }
      return '';
  }

  // New function to get the full category path
  function getCategoryPath($pdo, $categoryId) {
      $path = [];
      $current = $categoryId;
      
      // Collect all parent categories
      while ($current) {
          $stmt = $pdo->prepare('SELECT id, eng_name, parent_id FROM categories WHERE id = ?');
          $stmt->execute([$current]);
          $category = $stmt->fetch(PDO::FETCH_ASSOC);
          
          if (!$category) break;
          
          // Add to beginning of path
          array_unshift($path, strtolower(str_replace(' ', '-', $category['eng_name'])));
          
          // Move to parent
          $current = $category['parent_id'];
      }
      
      return implode('/', $path);
  }

  function renderCategories($pdo) {
      $html = '';
      try {
          $stmt = $pdo->query('SELECT * FROM categories WHERE parent_id IS NULL');
          $html .= '<nav class="fixed top-14 w-full z-40 bg-white shadow-md">';
          $html .= '<ul class="flex justify-center space-x-6 text-sm font-medium text-gray-700" style="padding-left: 20%; padding-right: 20%;">';

          while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
              // Generate URL with eng_name
              $categoryUrl = '/category/' . strtolower(str_replace(' ', '-', $category['eng_name']));
              
              $html .= '<li class="group relative px-4">';
              $html .= '<a href="' . $categoryUrl . '"
                  class="text-gray-500 dark:text-gray-400 hover:text-blue-500 font-medium px-4 py-2 inline-block text-sm">';
              $html .= htmlspecialchars($category['name']);
              $html .= '</a>';
              $html .= renderSubCategories($pdo, $category['id']);
              $html .= '</li>';
          }

          $html .= '</ul>';
          $html .= '</div>';
      } catch (PDOException $e) {
          $html = 'Error: ' . $e->getMessage();
      }
      return $html;
  }

    $host = 'sql105.infinityfree.com';
    $db = 'if0_37280528_ryuo_store';
    $user = 'if0_37280528';
    $pass = 'm9RLB5iHMPr';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo '<ul class="flex justify-center space-x-6 text-sm font-medium text-gray-700 relative bg-white" style="padding-left: 20%; padding-right: 20%;">';
        // Вызов функции для отображения всех категорий
        echo renderCategories($pdo);
        echo '</ul>';
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
    ?>



</body>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const subMenuContainer = document.querySelector('.sub-menu-container');
        const headerHeight = document.querySelector('header').offsetHeight;
        let lastScroll = 0;

        window.addEventListener('scroll', function () {
            const currentScroll = window.pageYOffset;

            if (currentScroll > headerHeight) {
                subMenuContainer.style.position = 'fixed';
                subMenuContainer.style.top = '0';
            } else {
                subMenuContainer.style.position = 'sticky';
                subMenuContainer.style.top = `${headerHeight}px`;
            }

            lastScroll = currentScroll;
        });
    });
</script>
</html>