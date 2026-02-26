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

if (!$user) {
    header("Location: ../home/index.php");
    exit();
}

if(isset($_GET['person']) && is_numeric($_GET['person'])) {
    $stmt = $db->prepare("SELECT * FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_two = ? AND user_one = ?)");
    $stmt->bind_param("iiii", $user_id, $_GET['person'], $user_id, $_GET['person']);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        header("Location: index.php");
        exit();
    }
    $user_two = $_GET['person'];
    $_SESSION['user_two'] = $user_two;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['message']) && !empty($_POST['message']) && isset($user_two) && is_numeric($user_two)) {
        $message = $_POST['message'];
    
        $stmt = $db->prepare("SELECT * FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_one = ? AND user_two = ?)");
        $stmt->bind_param("iiii", $user_id, $user_two, $user_two, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            $stmt = $db->prepare("INSERT INTO messages (content, created_at, user_id_from, user_id_to) VALUES (?, NOW(), ?, ?)");
            $stmt->bind_param("sii", $message, $user_id, $user_two);
            $stmt->execute();
            header("Location: index.php?person=$user_two");
            exit();
        }
    }

    if (isset($_POST['code'])) {
        if(!empty($_POST['code'])) {
            $code = $_POST['code'];
    
            $stmt = $db->prepare("SELECT * FROM users WHERE code = ?");
            $stmt->bind_param("i", $code);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows === 0) {
                $_SESSION['badAlert'] = "No user found with this code.";
            } else {
                if ($code == $user['code']) {
                    $_SESSION['badAlert'] = "You cannot add yourself as a friend.";
                    header("Location: index.php");
                    exit();
                }
                
                $friend = $result->fetch_assoc();
                $friend_id = $friend['user_id'];
    
                $stmt = $db->prepare("SELECT * FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_one = ? AND user_two = ?)");
                $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
    
                if ($result->num_rows > 0) {
                    $_SESSION['badAlert'] = "You are already friends with this user.";
                } else {
                    $stmt = $db->prepare("INSERT INTO friends (user_one, user_two) VALUES (?, ?)");
                    $stmt->bind_param("ii", $user_id, $friend_id);
                    if ($stmt->execute()) {
                        header("Location: index.php");
                        $_SESSION['goodAlert'] = "Friend added successfully!";
                        exit();
                    } else {
                        $_SESSION['badAlert'] = "Failed to add friend. Please try again.";
                        header("Location: index.php");
                        exit();
                    }
                }
            }
        } else {
            $_SESSION['badAlert'] = "Please enter the friend code.";
            header("Location: index.php");
            exit();
        }
    }

    if (isset($_POST['unfriend_id']) && is_numeric($_POST['unfriend_id'])) {
        $unfriend_id = $_POST['unfriend_id'];

        $stmt = $db->prepare("SELECT * FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_one = ? AND user_two = ?)");
        $stmt->bind_param("iiii", $user_id, $unfriend_id, $unfriend_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt = $db->prepare("DELETE FROM messages WHERE (user_id_from = ? AND user_id_to = ?) OR (user_id_from = ? AND user_id_to = ?)");
            $stmt->bind_param("iiii", $user_id, $unfriend_id, $unfriend_id, $user_id);

            $stmt2 = $db->prepare("DELETE FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_one = ? AND user_two = ?)");
            $stmt2->bind_param("iiii", $user_id, $unfriend_id, $unfriend_id, $user_id);
            if ($stmt2->execute() && $stmt->execute()) {
                $_SESSION['goodAlert'] = "Friend removed successfully.";
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['badAlert'] = "Failed to remove friend. Please try again.";
                header("Location: index.php");
                exit();
            }
        } else {
            $_SESSION['badAlert'] = "You are not friends with this user.";
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['badAlert'] = "Invalid friend ID.";
        header("Location: index.php");
        exit();
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
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="shortcut icon" href="../../materials/nexus.png" type="image/x-icon">
</head>
<body class="min-h-screen flex flex-col">
    <?php if (isset($_SESSION['goodAlert'])): ?>
        <div class="fixed top-2.5 left-1/2 -translate-x-1/2 bg-green-500 text-white px-5 py-3 rounded-md shadow-md hidden z-[1000]" id="alert-box"><?php echo $_SESSION['goodAlert']; unset($_SESSION['goodAlert']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['badAlert'])): ?>
        <div class="fixed top-2.5 left-1/2 -translate-x-1/2 bg-red-600 text-white px-5 py-3 rounded-md shadow-md hidden z-[1000]" id="alert-box"><?php echo $_SESSION['badAlert']; unset($_SESSION['badAlert']); ?></div>
    <?php endif; ?>
    <div class="absolute inset-0 backdrop-blur-md z-10 hidden" id="blur_bg"></div>
    <div class="flex flex-col gap-4 bg-zinc-800 p-6 rounded-lg shadow-lg absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-20 hidden" id="friends_menu">
        <button class="text-red-600 text-lg fixed self-end font-bold mr-[-10px] mt-[-10px]" onclick="open_friend_menu(); clear_input();">X</button>
        <form method="post" class="flex flex-col gap-4">
            <label>Podaj kod znajomego</label>
            <input type="text" id="friend_code_input" name="code" placeholder="XXXXXX" max="6" maxlength="6" class="px-4 py-2 rounded-lg bg-zinc-700 text-white focus:outline-none focus:ring-2 focus:ring-primary placeholder:text-zinc-400">
            <button type="submit" class="bg-primary-light hover:bg-primary-dark text-white px-4 py-2 rounded-lg transition font-semibold">Add Friend</button>
        </form>
    </div>
    <header class="bg-zinc-800 p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="../home/index.php"><h1 class="text-2xl font-bold text-primary-light">Nexus Chat</h1></a>
            <nav>
                <ul class="flex gap-4 text-sm">
                    <li><a href="../home/index.php" class="hover:text-primary-light">Home</a></li>
                    <li><a href="../sign-up/index.php" class="hover:text-primary-light">Register</a></li>
                    <li><button class="hover:text-primary-light flex transition-transform duration-300 hover:rotate-180" onclick="open_settings_menu()"><i data-lucide="settings" class="h-5"></i></button></li>
                </ul>
                <div id="settings_menu" class=" absolute right-[15vh] mt-4 w-28 bg-zinc-800 rounded-lg shadow-lg p-4 bg-opacity-50 text-center hidden">
                    <a href="../settings/index.php" class="hover:text-primary-light">Account</a><br>
                    <a href="logout.php" class="hover:text-primary-light">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="flex-grow flex">
        <section class="max-w-[15%] w-full p-6 bg-neutral-700 shadow-md lg:flex flex-col justify-between h-full min-h-[87.8vh] hidden">
            <div>
                <h2 class="text-xl font-semibold mb-4">
                    Welcome, <?php echo htmlspecialchars($user['username']); ?>! &nbsp;&nbsp; #<?php echo htmlspecialchars($user['code']); ?>
                </h2>
                <div class="space-y-4">
                    <?php
                    $stmt = $db->prepare("SELECT u.user_id AS friend_id, u.username, u.img_id FROM friends f JOIN users u ON u.user_id = CASE WHEN f.user_one = ? THEN f.user_two ELSE f.user_one END WHERE f.user_one = ? OR f.user_two = ?");
                    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while($row = $result->fetch_assoc()) {
                        $friend_id = htmlspecialchars($row['friend_id']);
                        $friend_username = htmlspecialchars($row['username']);
                        $initials = strtoupper(substr($friend_username, 0, 2));
                        $img_id = htmlspecialchars($row['img_id']);
                        ?>
                        <div class="relative flex flex-col gap-4">
                            <a href="?person=<?php echo $friend_id; ?>" class="friend-link flex items-center gap-2 p-2 rounded-lg hover:bg-zinc-600 transition-colors">
                                <?php if (empty($img_id)): ?>
                                    <div class="w-8 h-8 flex items-center justify-center rounded-full bg-primary-light text-white font-medium"><?php echo $initials; ?></div>
                                <?php else: ?>
                                    <img src="../../materials/avatars/<?php echo $img_id; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover">
                                <?php endif; ?>
                                <span><?php echo $friend_username; ?></span>
                            </a>
                            <form method="post" class="fixed self-end mt-[9px] mr-4">
                                <input type="hidden" name="unfriend_id" value="<?php echo $friend_id; ?>">
                                <button class="delete-btn text-red-600 text-lg font-bold hidden" type="submit" name="">X</button>
                            </form>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <button class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-light rounded-lg hover:bg-primary-dark transition-colors" onclick="open_friend_menu()"><i data-lucide="user-round-plus"></i> Add Friend</button>
        </section>
        <section class="flex-grow flex flex-col justify-between w-full">
            <?php if(isset($_GET['person'])): ?>
            <div id="chat_box" class="p-6">

            </div>
            <form method="post" class="flex items-center gap-3 bg-zinc-800 p-3 shadow-md" id="chat_form">
                <textarea name="message" id="message_input" rows="1" placeholder="Type your message..." class="flex-grow px-4 py-2 rounded-lg bg-zinc-700 text-white focus:outline-none focus:ring-2 focus:ring-primary-light placeholder:text-zinc-400 resize-none overflow-hidden min-h-[40px] max-h-48 leading-relaxed" autofocus></textarea>
                <button type="submit" class="bg-primary-light hover:bg-primary-dark text-white px-4 py-2 rounded-lg transition font-semibold" title="Send">➤</button>
            </form>
            <?php endif; ?>
        </section>
    </main>

    <footer class="bg-zinc-800 text-sm text-center py-4 text-zinc-400">
        &copy; 2025 Nexus Chat
    </footer>

    <script>
        <?php if(isset($_GET['person'])): ?>
        function loadMessages() {
            fetch("get_messages.php")
                .then(response => response.text())
                .then(data => {
                    document.getElementById("chat_box").innerHTML = data;
                })
                .catch(error => console.error("Błąd:", error));
        }
        loadMessages();
        setInterval(loadMessages, 1000);

        const textarea = document.getElementById('message_input');

        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        });

        const form = document.getElementById('chat_form');

        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.submit();
            }
        });
        <?php endif; ?>

        function open_settings_menu() {
            const menu = document.getElementById('settings_menu');
            menu.classList.toggle('hidden');
        }

        function open_friend_menu() {
            const friends_menu = document.getElementById('friends_menu');
            const blur_bg = document.getElementById('blur_bg');
            blur_bg.classList.toggle('hidden');
            friends_menu.classList.toggle('hidden');
        }

        function clear_input() {
            const input = document.getElementById('friend_code_input');
            input.value = '';
        }

        document.addEventListener("DOMContentLoaded", function () {
            let alertBox = document.getElementById("alert-box");

            if (alertBox && alertBox.innerText.trim() !== "") {
                alertBox.style.display = "block";

                setTimeout(function () {
                    alertBox.style.opacity = "1";
                    alertBox.style.transition = "opacity 0.5s";
                    alertBox.style.opacity = "0";
                    setTimeout(() => alertBox.style.display = "none", 500);
                }, 2000);
            }
        });

        const friendLinks = document.querySelectorAll('.friend-link');
        friendLinks.forEach(link => {
            const form = link.nextElementSibling;
            const button = form.querySelector('.delete-btn');

            const showButton = () => button.classList.remove('hidden');
            const hideButton = () => button.classList.add('hidden');

            link.addEventListener('mouseenter', showButton);
            button.addEventListener('mouseenter', showButton);

            link.addEventListener('mouseleave', (e) => {
                if (!button.contains(e.relatedTarget)) hideButton();
            });
            button.addEventListener('mouseleave', (e) => {
                if (!link.contains(e.relatedTarget)) hideButton();
            });
        });

        lucide.createIcons();
    </script>
</body>
</html>