<?php
$host = 'sql105.infinityfree.com';
$db = 'if0_37280528_ryuo_store';
$user = 'if0_37280528';
$pass = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : null;
    $email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : null;

    if ($username) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $exists = $stmt->fetchColumn() > 0;
        if ($exists) {
            echo "Это имя пользователя уже занято.";
        }
    } elseif ($email) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $exists = $stmt->fetchColumn() > 0;
        if ($exists) {
            echo "Этот email уже используется.";
        }
    }
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>