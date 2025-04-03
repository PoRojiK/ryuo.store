<?php
session_start();

// Удаление всех данных сессии
$_SESSION = array();

// Если используется cookie сессии, удалить его
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Наконец, уничтожить сессию
session_destroy();

// Перенаправление на главную страницу или страницу входа
header("Location: /pages/auth.php");
exit();
?>
