<?php
// Database connection settings
$host = 'sql105.infinityfree.com';
$db = 'if0_37280528_ryuo_store';
$user = 'if0_37280528';
$pass = 'm9RLB5iHMPr';

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->exec("set names utf8mb4");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // For production, you may want to log the error instead of displaying it
    die("Database connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Authentication check function
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: ../pages/profile.php");
    exit();
}

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    $login_errors = [];
    
    // Get and sanitize input
    $email = sanitizeInput($_POST['login_email']);
    $password = $_POST['login_password']; // Don't sanitize password before verification
    
    // Validate email
    if (empty($email)) {
        $login_errors['email'] = "Email обязателен";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_errors['email'] = "Неверный формат email";
    }
    
    // Validate password
    if (empty($password)) {
        $login_errors['password'] = "Пароль обязателен";
    }
    
    // If no validation errors, process login
    if (empty($login_errors)) {
        // Check credentials against database
        try {
            $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect to profile page
                header("Location: ../pages/profile.php");
                exit();
            } else {
                $login_errors['auth'] = "Неверный email или пароль";
            }
        } catch (PDOException $e) {
            $login_errors['auth'] = "Ошибка авторизации. Попробуйте позже.";
            // Log error for administrator
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_submit'])) {
    $register_errors = [];
    
    // Get and sanitize input
    $firstname = sanitizeInput($_POST['register_firstname']);
    $email = sanitizeInput($_POST['register_email']);
    $password = $_POST['register_password'];
    $confirm_password = $_POST['register_confirm_password'];
    
    // Validate name
    if (empty($firstname)) {
        $register_errors['firstname'] = "Имя обязательно";
    } elseif (strlen($firstname) < 2) {
        $register_errors['firstname'] = "Имя должно содержать не менее 2 символов";
    }
    
    // Validate email
    if (empty($email)) {
        $register_errors['email'] = "Email обязателен";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_errors['email'] = "Неверный формат email";
    }
    
    // Check if email already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $register_errors['email'] = "Этот email уже зарегистрирован";
        }
    } catch (PDOException $e) {
        $register_errors['email'] = "Ошибка проверки email. Попробуйте позже.";
        // Log error for administrator
        error_log("Email check error: " . $e->getMessage());
    }
    
    // Validate password
    if (empty($password)) {
        $register_errors['password'] = "Пароль обязателен";
    } elseif (strlen($password) < 8) {
        $register_errors['password'] = "Пароль должен содержать не менее 8 символов";
    } elseif (!preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $register_errors['password'] = "Пароль должен содержать буквы и цифры";
    }
    
    // Validate password confirmation
    if (empty($confirm_password)) {
        $register_errors['confirm_password'] = "Подтверждение пароля обязательно";
    } elseif ($password !== $confirm_password) {
        $register_errors['confirm_password'] = "Пароли не совпадают";
    }
    
    // If no validation errors, process registration
    if (empty($register_errors)) {
        try {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $stmt = $pdo->prepare("INSERT INTO users (first_name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $result = $stmt->execute([$firstname, $email, $hashed_password]);
            
            if ($result) {
                // Get the newly created user ID
                $user_id = $pdo->lastInsertId();
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                
                // Redirect to profile page
                header("Location: ../pages/profile.php");
                exit();
            } else {
                $register_errors['general'] = "Ошибка регистрации. Попробуйте позже.";
            }
        } catch (PDOException $e) {
            $register_errors['general'] = "Ошибка регистрации. Попробуйте позже.";
            // Log error for administrator
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ПРОФИЛЬ - RYUO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Montserrat', sans-serif;
        }
        
        .pattern-bg {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cpath fill='%23333' d='M33.3,33.3 L66.6,33.3 L50,0 L33.3,33.3 Z M33.3,66.6 L66.6,66.6 L50,100 L33.3,66.6 Z M0,50 L33.3,33.3 L33.3,66.6 L0,50 Z M100,50 L66.6,33.3 L66.6,66.6 L100,50 Z'/%3E%3C/svg%3E");
            background-color: #333;
        }
        
        .modal {
            transition: opacity 0.3s ease;
        }
        
        .error-message {
            color: #e53e3e;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
      .reg-button {
        background-color: #4ECDC4;
      }
      .reg-button:hover {
        background-color: #44A9A2;
      }
    </style>
</head>

<body class="min-h-screen bg-gray-100">
<?php 
  include '../components/navbar.php'; // Подключение файла navbar
?>
  <div class="px-[20%] pt-20 py-4">
    <div class="h-full grid grid-cols-1 md:grid-cols-3">
        <!-- Left content section -->
        <div class="p-8 flex flex-col justify-between md:col-span-2">
            <div>
                <div class="uppercase text-gray-500 font-bold mb-4">
                    <a href="/" class="hover:underline">RYUO</a><span class="font-bold"> » </span><span class="font-bold">ПРОФИЛЬ</span>
                    <div class="text-2xl font-bold text-gray-800">ПРОФИЛЬ</div>   
                </div>
                
                <button onclick="goBack()" class="transition-all duration-300 ease-in-out bg-gray-300 hover:bg-gray-400 p-3 rounded-md mb-12">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                </button>
                
                <div class="uppercase text-gray-800 text-8xl font-bold mb-6">ИТАК</div>
                
                <div class="text-gray-400 mb-3 text-lg font-semibold">КАК Я ВИЖУ, В АККАУНТ ТЫ НЕ ВОШЕЛ</div>
                
                <div class="text-gray-700 mb-12 max-w-xl font-semibold">
                    НЕ ТАК ВАЖНО ЕСТЬ У ТЕБЯ АККАУНТ ИЛИ НЕТ, Я НАСТОЯТЕЛЬНО РЕКОМЕНДУЮ ВОЙТИ ИЛИ ЗАРЕГИСТРИРОВАТЬСЯ<br>
                    БАЛЛЫ ЛИШНИМИ НЕ БЫВАЮТ!
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 max-w-md ">
                    <button onclick="showModal('loginModal')" class="transition-all duration-300 ease-in-out bg-gray-600 hover:bg-gray-800 text-white py-3 px-6 uppercase font-medium rounded-md">
                        Войти
                    </button>
                    <button onclick="showModal('registerModal')" class="transition-all duration-300 ease-in-out reg-button text-black py-3 px-6 uppercase font-medium rounded-md">
                        Зарегистрироваться
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Right pattern section -->
        <div class="hidden md:block pattern-bg relative rounded-bl-2xl rounded-br-2xl">
          <div class="absolute inset-0 flex items-center justify-center h-full">
            <svg xmlns="http://www.w3.org/2000/svg" width="136" height="137" viewBox="0 0 136 137" fill="none">
              <path d="M115 1.5L1 1V46H72.5C68 78 43.8829 88.5331 1 92V136C32 136 49 132 73 119C98.5 136 114 136 135 136V92C119.8 92 107.5 89.5 103 84C111 73.6 115 54.6667 116 46H135V22C125 22 115 12 115 1.5Z" fill="white"/>
            </svg>
          </div>
      </div>
    </div>
</div>
    
<!-- Login Modal -->
<div id="loginModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
    
    <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50 overflow-y-auto">
        <div class="modal-content py-4 text-left px-6">
            <div class="flex justify-between items-center pb-3">
                <p class="text-2xl font-bold">Вход в аккаунт</p>
                <div class="modal-close cursor-pointer z-50" onclick="hideModal('loginModal')">
                    <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                        <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                    </svg>
                </div>
            </div>

            <?php if (isset($login_errors['auth'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $login_errors['auth']; ?></span>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="login-email">
                        Email
                    </label>
                    <input class="shadow appearance-none border <?php echo isset($login_errors['email']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="login-email" name="login_email" type="email" placeholder="Email" value="<?php echo isset($_POST['login_email']) ? htmlspecialchars($_POST['login_email']) : ''; ?>">
                    <?php if (isset($login_errors['email'])): ?>
                        <p class="error-message"><?php echo $login_errors['email']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="login-password">
                        Пароль
                    </label>
                    <input class="shadow appearance-none border <?php echo isset($login_errors['password']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="login-password" name="login_password" type="password" placeholder="******************">
                    <?php if (isset($login_errors['password'])): ?>
                        <p class="error-message"><?php echo $login_errors['password']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="login_submit">
                        Войти
                    </button>
                    <a class="inline-block align-baseline font-bold text-sm text-gray-500 hover:text-gray-800" href="#">
                        Забыли пароль?
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div id="registerModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
    
    <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50 overflow-y-auto">
        <div class="modal-content py-4 text-left px-6">
            <div class="flex justify-between items-center pb-3">
                <p class="text-2xl font-bold">Регистрация</p>
                <div class="modal-close cursor-pointer z-50" onclick="hideModal('registerModal')">
                    <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                        <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                    </svg>
                </div>
            </div>

            <?php if (isset($register_errors['general'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $register_errors['general']; ?></span>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="register-firstname">
                        Имя
                    </label>
                    <input class="shadow appearance-none border <?php echo isset($register_errors['firstname']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="register-firstname" name="register_firstname" type="text" placeholder="Имя" value="<?php echo isset($_POST['register_firstname']) ? htmlspecialchars($_POST['register_firstname']) : ''; ?>">
                    <?php if (isset($register_errors['firstname'])): ?>
                        <p class="error-message"><?php echo $register_errors['firstname']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="register-email">
                        Email
                    </label>
                    <input class="shadow appearance-none border <?php echo isset($register_errors['email']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="register-email" name="register_email" type="email" placeholder="Email" value="<?php echo isset($_POST['register_email']) ? htmlspecialchars($_POST['register_email']) : ''; ?>">
                    <?php if (isset($register_errors['email'])): ?>
                        <p class="error-message"><?php echo $register_errors['email']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="register-password">
                        Пароль
                    </label>
                    <input class="shadow appearance-none border <?php echo isset($register_errors['password']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="register-password" name="register_password" type="password" placeholder="******************">
                    <?php if (isset($register_errors['password'])): ?>
                        <p class="error-message"><?php echo $register_errors['password']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="register-confirm-password">
                        Подтвердите пароль
                    </label>
                    <input class="shadow appearance-none border <?php echo isset($register_errors['confirm_password']) ? 'border-red-500' : ''; ?> rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="register-confirm-password" name="register_confirm_password" type="password" placeholder="******************">
                    <?php if (isset($register_errors['confirm_password'])): ?>
                        <p class="error-message"><?php echo $register_errors['confirm_password']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between">
                    <button class="transition-all duration-300 ease-in-out reg-button text-black font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="register_submit">
                        Зарегистрироваться
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Modal handling functions
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('opacity-0');
        modal.classList.remove('pointer-events-none');
    }

    function hideModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('opacity-0');
        modal.classList.add('pointer-events-none');
    }

    function goBack() {
        window.history.back();
    }

    // Show the login modal if there were login errors
    <?php if (isset($login_errors) && !empty($login_errors)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showModal('loginModal');
        });
    <?php endif; ?>

    // Show the register modal if there were registration errors
    <?php if (isset($register_errors) && !empty($register_errors)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showModal('registerModal');
        });
    <?php endif; ?>

    // Client-side validation for login form
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        let valid = true;
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        
        // Clear previous error messages
        document.querySelectorAll('.error-message').forEach(e => e.remove());
        
        // Validate email
        if (!email) {
            valid = false;
            const emailField = document.getElementById('login-email');
            emailField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Email обязателен';
            emailField.parentNode.appendChild(errorMsg);
        } else if (!/^\S+@\S+\.\S+$/.test(email)) {
            valid = false;
            const emailField = document.getElementById('login-email');
            emailField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Неверный формат email';
            emailField.parentNode.appendChild(errorMsg);
        }
        
        // Validate password
        if (!password) {
            valid = false;
            const passwordField = document.getElementById('login-password');
            passwordField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Пароль обязателен';
            passwordField.parentNode.appendChild(errorMsg);
        }
        
        if (!valid) {
            event.preventDefault();
        }
    });

    // Client-side validation for register form
    document.getElementById('registerForm').addEventListener('submit', function(event) {
        let valid = true;
        const firstname = document.getElementById('register-firstname').value;
        const email = document.getElementById('register-email').value;
        const password = document.getElementById('register-password').value;
        const confirmPassword = document.getElementById('register-confirm-password').value;
        
        // Clear previous error messages
        document.querySelectorAll('.error-message').forEach(e => e.remove());
        
        // Validate name
        if (!firstname) {
            valid = false;
            const nameField = document.getElementById('register-firstname');
            nameField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Имя обязательно';
            nameField.parentNode.appendChild(errorMsg);
        } else if (firstname.length < 2) {
            valid = false;
            const nameField = document.getElementById('register-firstname');
            nameField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Имя должно содержать не менее 2 символов';
            nameField.parentNode.appendChild(errorMsg);
        }
        
        // Validate email
        if (!email) {
            valid = false;
            const emailField = document.getElementById('register-email');
            emailField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Email обязателен';
            emailField.parentNode.appendChild(errorMsg);
        } else if (!/^\S+@\S+\.\S+$/.test(email)) {
            valid = false;
            const emailField = document.getElementById('register-email');
            emailField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Неверный формат email';
            emailField.parentNode.appendChild(errorMsg);
        }
        
        // Validate password
        if (!password) {
            valid = false;
            const passwordField = document.getElementById('register-password');
            passwordField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Пароль обязателен';
            passwordField.parentNode.appendChild(errorMsg);
        } else if (password.length < 8) {
            valid = false;
            const passwordField = document.getElementById('register-password');
            passwordField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Пароль должен содержать не менее 8 символов';
            passwordField.parentNode.appendChild(errorMsg);
        } else if (!/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) {
            valid = false;
            const passwordField = document.getElementById('register-password');
            passwordField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Пароль должен содержать буквы и цифры';
            passwordField.parentNode.appendChild(errorMsg);
        }
        
        // Validate password confirmation
        if (!confirmPassword) {
            valid = false;
            const confirmField = document.getElementById('register-confirm-password');
            confirmField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Подтверждение пароля обязательно';
            confirmField.parentNode.appendChild(errorMsg);
        } else if (password !== confirmPassword) {
            valid = false;
            const confirmField = document.getElementById('register-confirm-password');
            confirmField.classList.add('border-red-500');
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Пароли не совпадают';
            confirmField.parentNode.appendChild(errorMsg);
        }
        
        if (!valid) {
            event.preventDefault();
        }
    });
</script>

<?php 
  include '../components/footer.php'; // Подключение файла footer
?>