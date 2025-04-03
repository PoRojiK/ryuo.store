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
        $first_name = htmlspecialchars($_POST['first_name']);
        $last_name = htmlspecialchars($_POST['last_name']);
        $email = htmlspecialchars($_POST['email']);
        $birthdate = $_POST['birthdate'];
        $gender = $_POST['gender'];

        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, birthdate = ?, gender = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $email, $birthdate, $gender, $_SESSION['user_id']]);

        header("Location: /pages/profile.php?tab=info");
        exit();
    }
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
