<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$host = 'sql105.infinityfree.com';
$db = 'if0_37280528_ryuo_store';
$user = 'if0_37280528';
$pass = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Shared category functions
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
        // Convert spaces to dashes and lowercase for SEO friendliness
        $url_parts[] = strtolower(str_replace(' ', '-', $category['eng_name']));
    }
    return '/category/' . implode('/', $url_parts);
}

function getCategoryUrlFromId($pdo, $category_id) {
    $breadcrumb = getCategoryBreadcrumb($pdo, $category_id);
    return getCategoryUrl($breadcrumb);
}

// Render categories for navbar
function renderSubCategories($pdo, $parentId) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE parent_id = ?');
    $stmt->execute([$parentId]);
    $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($subCategories) {
        $html = '<div class="sub-menu-container sticky top-[var(--header-height)] z-40 opacity-0 group-hover:opacity-100 transition-opacity duration-300">';
        $html .= '<div class="w-full mx-auto px-[20%] fixed hidden group-hover:block w-full left-0 top-[var(--header-height)] bg-white shadow-lg py-6 z-50 ">';
        $html .= '<div class="grid grid-cols-4 gap-8">';

        foreach ($subCategories as $subCategory) {
            // Get complete breadcrumb for this subcategory
            $subcategoryBreadcrumb = getCategoryBreadcrumb($pdo, $subCategory['id']);
            $subcategoryUrl = getCategoryUrl($subcategoryBreadcrumb);
            
            $html .= '<div>';
            $html .= '<a href="' . $subcategoryUrl . '" 
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
                    // Get complete breadcrumb for this sub-subcategory
                    $subSubcategoryBreadcrumb = getCategoryBreadcrumb($pdo, $subSubCategory['id']);
                    $subSubcategoryUrl = getCategoryUrl($subSubcategoryBreadcrumb);
                    
                    $html .= '<li>';
                    $html .= '<a href="' . $subSubcategoryUrl . '" 
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

        return $html;
    }
    return '';
}

function renderCategories($pdo) {
    $html = '';
    try {
        $stmt = $pdo->query('SELECT * FROM categories WHERE parent_id IS NULL');
        $html .= '<nav class="fixed top-14 w-full z-40 bg-white shadow-md">';
        $html .= '<ul class="flex justify-center space-x-6 text-sm font-medium text-gray-700" style="padding-left: 20%; padding-right: 20%;">';

        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get complete breadcrumb for this main category
            $categoryBreadcrumb = getCategoryBreadcrumb($pdo, $category['id']);
            $categoryUrl = getCategoryUrl($categoryBreadcrumb);
            
            $html .= '<li class="group relative px-4">';
            $html .= '<a href="' . $categoryUrl . '" 
                class="text-gray-500 dark:text-gray-400 hover:text-blue-500 font-medium px-4 py-2 inline-block text-sm">';
            $html .= htmlspecialchars($category['name']);
            $html .= '</a>';
            $html .= renderSubCategories($pdo, $category['id']);
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';
    } catch (PDOException $e) {
        $html = 'Error: ' . $e->getMessage();
    }
    return $html;
}
?>