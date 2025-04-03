<?php
session_start();
$host = 'sql105.infinityfree.com';
$db = 'if0_37280528_ryuo_store';
$user = 'if0_37280528';
$pass = 'm9RLB5iHMPr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = htmlspecialchars($_POST['username']);
        $email = htmlspecialchars($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'redirect' => '/pages/profile.php']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'error' => 'Пользователь с таким email уже существует.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()]);
            }
        }
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()]);
}
?>