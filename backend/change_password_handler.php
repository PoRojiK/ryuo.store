<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/register.php");
    exit();
}

$host = 'sql105.infinityfree.com';
$db = 'if0_37280528_ryuo_store';
$user = 'if0_37280528';
$pass = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $current_password = $_POST['current_password'];
        $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

        // Проверка текущего пароля
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($current_password, $user['password'])) {
            // Обновление пароля
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $_SESSION['user_id']]);

            header("Location: /pages/profile.php?tab=change_password&success=1");
            exit();
        } else {
            header("Location: /pages/profile.php?tab=change_password&error=1");
            exit();
        }
    }
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
