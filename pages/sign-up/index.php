<?php
session_start();

require_once '../../php/connect.php';

$user = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $theme = $user['theme'];
        if ($theme === 'blue') {
            $light = "#2563eb";
            $dark = "#1d4ed8";
        } elseif ($theme === 'orange') {
            $light = "#f97316";
            $dark = "#ea580c";
        } elseif ($theme === 'yellow') {
            $light = "#eab308";
            $dark = "#ca8a04";
        } elseif ($theme === 'violet') {
            $light = "#8b5cf6";
            $dark = "#7c3aed";
        } elseif ($theme === 'green') {
            $light = "#22c55e";
            $dark = "#16a34a";
        } else {
            $light = "#2563eb";
            $dark = "#1d4ed8";
        }
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($_POST['email']) || empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'All fields are required.';
    } else {
        $email = $_POST['email'];
        $username = $_POST['username'];

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (strlen($_POST['password']) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($_POST['password'] !== $_POST['password_reapet']) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = 'Email already exists.';
            }
        }

        if(!$error) {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

            while (true) {
                $code = rand(100000, 999999);
                $stmt = $db->prepare("SELECT * FROM users WHERE code = ?");
                $stmt->bind_param("i", $code);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    break;
                }
            }
            
            $stmt = $db->prepare("INSERT INTO users (email, username, password, code) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $email, $username, $password, $code);
            if ($stmt->execute()) {
                header("Location: ../window/index.php");
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-zinc-900 text-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            light: <?php echo isset($light) ? "'$light'" : "'#2563eb'"; ?>,
                            dark: <?php echo isset($dark) ? "'$dark'" : "'#1d4ed8'"; ?>,
                        }
                    }
                }
            }
        }
    </script>
    <link rel="shortcut icon" href="../../materials/nexus.png" type="image/x-icon">
</head>
<body class="min-h-screen flex flex-col">
<header class="bg-zinc-800 p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
        <a href="../home/index.php"><h1 class="text-2xl font-bold text-primary-light">Nexus Chat</h1></a>
        <nav>
            <ul class="flex gap-4 text-sm">
                <li><a href="../home/index.php" class="hover:text-primary-light">Home</a></li>
                <li><a href="../sign-up/index.php" class="hover:text-primary-light">Register</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="flex-grow flex items-center justify-center">
    <div class="bg-zinc-800 p-8 pb-5 rounded-xl shadow-lg w-full max-w-md">
        <h2 class="text-xl font-semibold mb-6 text-center">Sign Up</h2>
        <form method="post" action="">
            <div class="mb-4">
                <label for="email" class="block text-sm mb-1">Email</label>
                <input type="email" name="email" id="email" class="w-full p-2 rounded-md bg-zinc-700 text-white focus:outline-none focus:ring-2 focus:ring-primary-light" required>
            </div>
            <div class="mb-4">
                <label for="username" class="block text-sm mb-1">Username</label>
                <input type="text" name="username" id="username" class="w-full p-2 rounded-md bg-zinc-700 text-white focus:outline-none focus:ring-2 focus:ring-primary-light" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm mb-1">Password</label>
                <input type="password" name="password" id="password" class="w-full p-2 rounded-md bg-zinc-700 text-white focus:outline-none focus:ring-2 focus:ring-primary-light" required>
            </div>
            <div class="mb-4">
                <label for="password_reapet" class="block text-sm mb-1">Password Repeat</label>
                <input type="password" name="password_reapet" id="password_reapet" class="w-full p-2 rounded-md bg-zinc-700 text-white focus:outline-none focus:ring-2 focus:ring-primary-light" required>
            </div>
            <button type="submit" class="w-full bg-primary-light hover:bg-primary-dark text-white py-2 rounded-md font-semibold transition mt-2">Sign Up</button>
            <?php if (!empty($error)): ?>
                <p class="text-red-500 text-sm mt-5"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        </form>
    </div>
</main>

<footer class="bg-zinc-800 text-sm text-center py-4 text-zinc-400">
    &copy; 2025 Nexus Chat
</footer>
</body>
</html>