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
        $theme = htmlspecialchars($user['theme']);
        if ($theme === 'blue') {
            $light = "#2563eb";
            $dark = "#1d4ed8";
            $theme_name = "Blue";
        } elseif ($theme === 'orange') {
            $light = "#f97316";
            $dark = "#ea580c";
            $theme_name = "Orange";
        } elseif ($theme === 'yellow') {
            $light = "#eab308";
            $dark = "#ca8a04";
            $theme_name = "Yellow";
        } elseif ($theme === 'violet') {
            $light = "#8b5cf6";
            $dark = "#7c3aed";
            $theme_name = "Violet";
        } elseif ($theme === 'green') {
            $light = "#22c55e";
            $dark = "#16a34a";
            $theme_name = "Green";
        } else {
            $light = "#2563eb";
            $dark = "#1d4ed8";
            $theme_name = "Blue";
        }
    }

    $initials = strtoupper(substr(htmlspecialchars($user['username']), 0, 2));

    if (isset($user['img_id'])) {
        $img_id = htmlspecialchars($user['img_id']);
    }
}

if (!$user) {
    header("Location: ../home/index.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $updated = false;

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK && isset($_FILES['avatar']['name']) && !empty($_FILES['avatar']['name'])) {
        $fileTmpPath = $_FILES['avatar']['tmp_name'];
        $fileName = $_FILES['avatar']['name'];
        $fileSize = $_FILES['avatar']['size'];
        $fileType = $_FILES['avatar']['type'];

        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = ['jpg', 'jpeg', 'png'];

        if (in_array($fileExtension, $allowedfileExtensions)) {
            if ($fileSize <= 1024 * 1024) {

                while (true) {
                    $file_id  = bin2hex(random_bytes(32));
                    $stmt = $db->prepare("SELECT * FROM users WHERE img_id = ?");
                    $stmt->bind_param("s", $file_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 0) {
                        break;
                    }
                }

                $newFileName = $file_id . '.' . $fileExtension;
                $uploadFileDir = '../../materials/avatars/';
                $dest_path = $uploadFileDir . $newFileName;

                if (!file_exists($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    unlink('../../materials/avatars/' . $img_id);

                    $stmt = $db->prepare("UPDATE users SET img_id = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $newFileName, $user_id);
                    if ($stmt->execute()) {
                        $updated = true;
                    } else {
                        $_SESSION['badAlert'] = "Error updating avatar. Please try again.";
                        header("Location: index.php");
                        exit();
                    }
                } else {
                    $_SESSION['badAlert'] = "There was an error moving the uploaded file.";
                    header("Location: index.php");
                    exit();
                }
            } else {
                $_SESSION['badAlert'] = "The file is too large. Maximum size is 1MB.";
                header("Location: index.php");
                exit();
            }
        } else {
            $_SESSION['badAlert'] = "Invalid file type. Only JPG and PNG are allowed.";
            header("Location: index.php");
            exit();
        }
    } elseif(isset($_POST['delete_avatar_button'])) {
        if (!empty($img_id)) {
            unlink('../../materials/avatars/' . $img_id);

            $stmt = $db->prepare("UPDATE users SET img_id = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $_SESSION['goodAlert'] = "Avatar deleted successfully.";
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['badAlert'] = "Error deleting avatar. Please try again.";
                header("Location: index.php");
                exit();
            }
        } else {
            $_SESSION['badAlert'] = "No avatar to delete.";
            header("Location: index.php");
            exit();
        }
    }
    if (isset($_POST['email']) && isset($_POST['username']) && isset($_POST['theme_input']) && isset($_POST['code']) && !isset($_POST['delete_avatar_button'])) {
        if(!empty($_POST['email']) && !empty($_POST['username']) && !empty($_POST['theme_input']) && !empty($_POST['code'])) {

            $email = $_POST['email'];
            $username = $_POST['username'];
            $theme = $_POST['theme_input'];
            $code = $_POST['code'];
    
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['badAlert'] = "Invalid email format.";
                header("Location: index.php");
                exit();
            } else {
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND user_id != ?");
                $stmt->bind_param("si", $email, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if($result->num_rows > 0) {
                    $_SESSION['badAlert'] = "Email is already in use.";
                    header("Location: index.php");
                    exit();
                } elseif (strlen($code) > 6) {
                    $_SESSION['badAlert'] = "Tag mustn't be longer than 6 digits.";
                    header("Location: index.php");
                    exit();
                } else {
                    $stmt = $db->prepare("SELECT * FROM users WHERE code = ? AND user_id != ?");
                    $stmt->bind_param("si", $code, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if($result->num_rows > 0) {
                        $_SESSION['badAlert'] = "Tag is already in use.";
                        header("Location: index.php");
                        exit();
                    }
                }

                $stmt = $db->prepare("UPDATE users SET email = ?, username = ?, theme = ?, code = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $email, $username, $theme, $code, $user_id);
                if($stmt->execute()) {
                    $updated = true;
                } else {
                    $_SESSION['badAlert'] = "Error updating profile. Please try again.";
                    header("Location: index.php");
                    exit();
                }
            }
        } else {
            $_SESSION['badAlert'] = "Please fill in all required fields.";
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['badAlert'] = "Please fill in all required fields.";
        header("Location: index.php");
        exit();
    }
    
    if($updated){
        $_SESSION['goodAlert'] = "Profile updated successfully.";
        header("Location: index.php");
        exit();
    }

    if(isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        if(!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if($new_password === $confirm_password) {

                if(strlen($new_password) < 6) {
                    $_SESSION['badAlert'] = "New password must be at least 6 characters long.";
                    header("Location: index.php");
                    exit();
                } elseif ($new_password === $current_password) {
                    $_SESSION['badAlert'] = "New password cannot be the same as the current password.";
                    header("Location: index.php");
                    exit();
                } else {
                    $old_hashed_password = htmlspecialchars($user['password']);
                    if(password_verify($current_password, $old_hashed_password)) {
                        $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    
                        $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                        $update_stmt->bind_param("si", $new_hashed_password, $user_id);
    
                        if($update_stmt->execute()) {
                            $_SESSION['goodAlert'] = "Password updated successfully.";
                            header("Location: index.php");
                            exit();
                        } else {
                            $_SESSION['badAlert'] = "Error updating password. Please try again.";
                            header("Location: index.php");
                            exit();
                        }
                    } else {
                        $_SESSION['badAlert'] = "Current password is incorrect.";
                        header("Location: index.php");
                        exit();
                    }
                }
            } else {
                $_SESSION['badAlert'] = "Passwords do not match.";
                header("Location: index.php");
                exit();
            }
        } else {
            $_SESSION['badAlert'] = "Please fill in all password fields.";
            header("Location: index.php");
            exit();
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
    <header class="bg-zinc-800 p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="../home/index.php"><h1 class="text-2xl font-bold text-primary-light">Nexus Chat</h1></a>
            <nav>
                <ul class="flex gap-4 text-sm">
                    <li><a href="../home/index.php" class="hover:text-primary-light">Home</a></li>
                    <li><a href="../sign-up/index.php" class="hover:text-primary-light">Register</a></li>
                    <li><a href="../window/index.php" class="flex hover:text-primary-light">Return<i data-lucide="corner-down-left" class="h-5 ml-[2px]"></i></a></li>
                    <li><button class="hover:text-primary-light flex transition-transform duration-300 hover:rotate-180" onclick="open_settings_menu()"><i data-lucide="settings" class="h-5"></i></button></li>
                </ul>
                <div id="settings_menu" class=" absolute right-[15vh] mt-4 w-28 bg-zinc-800 rounded-lg shadow-lg p-4 bg-opacity-50 text-center hidden">
                    <a href="../settings/index.php">Account</a><br>
                    <a href="../window/logout.php">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="flex-grow flex flex-col items-center py-10 px-4 space-y-10">

        <section class="bg-zinc-800 rounded-2xl p-8 shadow-lg w-full max-w-4xl mx-auto">
            <form method="post" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-8">
                
                <div class="md:w-1/3 space-y-">
                    <h2 class="text-lg font-semibold text-white">Personal Information</h2>
                    <p class="text-sm text-zinc-400">Use a permanent address where you can receive mail.</p>
                    
                    <div class="flex flex-col items-center gap-4 mt-6">
                        <?php if (empty($img_id)): ?>
                            <div class="w-24 h-24 rounded-full bg-primary-light flex items-center justify-center text-4xl font-bold text-white" id="avatar_container">
                                <label><?php echo $initials; ?></label>
                            </div>
                        <?php else: ?>
                            <img src="../../materials/avatars/<?php echo $img_id; ?>" alt="Avatar" class="w-24 h-24 rounded-full object-cover">
                        <?php endif; ?>
                        
                        <div class="flex flex-row items-center gap-2">
                            <label class="bg-zinc-700 hover:bg-zinc-600 text-white text-sm font-medium px-3 py-1 rounded-lg cursor-pointer">
                                Change avatar
                                <input type="file" name="avatar" id="avatar_input" accept="image/png, image/jpeg" class="hidden">
                            </label>
                            <button type="submit" name="delete_avatar_button" class="hover:text-red-600 "><i class="h-6" data-lucide="trash-2"></i></button>
                        </div>
                        <p class="text-xs text-zinc-400">JPG or PNG. 1MB max.</p>
                    </div>
                </div>

                <div class="md:w-2/3 space-y-5">
                    <div>
                        <label class="block text-sm font-medium mb-1 text-white">Email address</label>
                        <input name="email" value="<?php echo htmlspecialchars($user['email']); ?>" type="email" class="w-full bg-zinc-900 border border-zinc-700 rounded-md p-2 focus:border-primary-light focus:outline-none text-gray-200" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-white">Username</label>
                        <input name="username" value="<?php echo htmlspecialchars($user['username']); ?>" type="text" class="w-full bg-zinc-900 border border-zinc-700 rounded-md p-2 text-gray-200 focus:border-primary-light focus:outline-none" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-white">Tag</label>
                        <input name="code" value="<?php echo htmlspecialchars($user['code']); ?>" maxlength="6" max="6" type="text" class="w-full bg-zinc-900 border border-zinc-700 rounded-md p-2 text-gray-200 focus:border-primary-light focus:outline-none" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-white">Theme</label>
                        <button id="theme_button" type="button" class="w-full bg-zinc-900 border border-zinc-700 rounded-md p-2 focus:border-primary-light focus:outline-none text-left flex justify-between items-center text-gray-200"><?php echo $theme_name; ?><i data-lucide="arrow-up" class="h-5 transition-transform duration-300" id="theme_arrow"></i></button>

                        <div id="theme_menu" class="mt-2 mb-2 p-4 bg-zinc-900 rounded-lg shadow-lg hidden flex flex-row gap-4">
                            <div class="w-28 mt-3">
                                <button type="button" onclick="change_theme('blue')" class="mt-2 p-2 bg-zinc-700 rounded-md flex items-center gap-4 hover:bg-zinc-600 cursor-pointer w-full">
                                    <div class="w-6 h-6 bg-blue-600 rounded-full"></div>
                                    <span class="text-sm text-white">Blue</span>
                                </button>
                                <button type="button" onclick="change_theme('orange')" class="mt-2 p-2 bg-zinc-700 rounded-md flex items-center gap-4 hover:bg-zinc-600 cursor-pointer w-full">
                                    <div class="w-6 h-6 bg-orange-600 rounded-full"></div>
                                    <span class="text-sm text-white">Orange</span>
                                </button>
                                <button type="button" onclick="change_theme('yellow')" class="mt-2 p-2 bg-zinc-700 rounded-md flex items-center gap-4 hover:bg-zinc-600 cursor-pointer w-full">
                                    <div class="w-6 h-6 bg-yellow-500 rounded-full"></div>
                                    <span class="text-sm text-white">Yellow</span>
                                </button>
                                <button type="button" onclick="change_theme('violet')" class="mt-2 p-2 bg-zinc-700 rounded-md flex items-center gap-4 hover:bg-zinc-600 cursor-pointer w-full">
                                    <div class="w-6 h-6 bg-violet-600 rounded-full"></div>
                                    <span class="text-sm text-white">Violet</span>
                                </button>
                                <button type="button" onclick="change_theme('green')" class="mt-2 p-2 bg-zinc-700 rounded-md flex items-center gap-4 hover:bg-zinc-600 cursor-pointer w-full">
                                    <div class="w-6 h-6 bg-green-600 rounded-full"></div>
                                    <span class="text-sm text-white">Green</span>
                                </button>
                                
                                <input type="hidden" name="theme_input" id="theme_input" value="<?php echo $theme; ?>">
                            </div>

                            <div class="flex-grow flex flex-col justify-between border border-zinc-700 mt-4 rounded-md overflow-hidden">
                                <div class="p-2">
                                    <div class="text-left mb-[2px]">
                                        <strong class="block text-sm text-gray-400 mb-1">Friend</strong>
                                        <p class="inline-block bg-neutral-700 hover:bg-zinc-800 text-white px-4 py-2 rounded-xl max-w-[75%] break-words text-left"> This is a message preview.</p>
                                    </div>
                                    <div class="text-right mb-[2px]">
                                        <strong class="block text-sm text-gray-400 mb-1">You</strong>
                                        <p class="color_change_element inline-block bg-primary-light hover:bg-primary-dark text-white px-4 py-2 rounded-xl max-w-[75%] break-words text-left"> Here you can preview the theme colors.</p>
                                    </div>
                                    <div class="text-right mb-10">
                                        <p class="color_change_element inline-block bg-primary-light hover:bg-primary-dark text-white px-4 py-2 rounded-xl max-w-[75%] break-words text-left"> Choose your favorite.</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 bg-zinc-800 p-3 shadow-md">
                                    <input placeholder="Type your message..." id="color_change_input" class="flex-grow px-4 py-2 rounded-lg bg-zinc-700 text-white focus:outline-none focus:ring-2 focus:ring-primary-light placeholder:text-zinc-400 resize-none overflow-hidden min-h-[40px] max-h-48 leading-relaxed" />
                                    <button type="button" class="color_change_element bg-primary-light hover:bg-primary-dark text-white px-4 py-2 rounded-lg transition font-semibold" title="Send">➤</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="bg-primary-light hover:bg-primary-dark text-white font-semibold px-5 py-2 rounded-md">
                            Save
                        </button>
                    </div>
                </div>
            </form>

        </section>


        <section class="bg-zinc-800 rounded-2xl p-8 flex flex-col md:flex-row gap-8 shadow-lg w-full max-w-4xl">
            <div class="md:w-1/3 space-y-3">
                <h2 class="text-lg font-semibold">Change password</h2>
                <p class="text-sm text-zinc-400">Update your password associated with your account.</p>
            </div>

            <form method="post" class="md:w-2/3 space-y-5">
                <div>
                    <label class="block text-sm font-medium mb-1">Current password</label>
                    <input type="password" name="current_password" class="w-full bg-zinc-900 border border-zinc-700 rounded-md p-2 focus:border-primary-light focus:outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">New password</label>
                    <input type="password" name="new_password" class="w-full bg-zinc-900 border border-zinc-700 rounded-md p-2 focus:border-primary-light focus:outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Confirm password</label>
                    <input type="password" name="confirm_password" class="w-full bg-zinc-900 border border-zinc-700 rounded-md p-2 focus:border-primary-light focus:outline-none" required>
                </div>

                <button type="submit" class="bg-primary-light hover:bg-primary-dark text-white font-semibold px-5 py-2 rounded-md">
                    Save
                </button>
            </form>
        </section>

    </main>

    <footer class="bg-zinc-800 text-sm text-center py-4 text-zinc-400">
        &copy; 2025 Nexus Chat
    </footer>

    <script>

        const theme_button = document.getElementById('theme_button');

        function theme_example() {
            const theme_menu = document.getElementById('theme_menu');
            const theme_arrow = document.getElementById('theme_arrow');
            theme_arrow.classList.toggle('rotate-180');
            theme_menu.classList.toggle('hidden');
        }

        theme_button.addEventListener('click', () => {
            theme_example();
        });

        function change_theme(color) {
            const theme_input = document.getElementById('theme_input');
            const color_change_elements = document.querySelectorAll('.color_change_element');
            const color_change_input = document.getElementById('color_change_input');

            color_change_elements.forEach(element => {
                element.classList.remove('bg-primary-light', 'hover:bg-primary-dark', 'bg-[#2563eb]', 'hover:bg-[#1d4ed8]', 'bg-[#f97316]', 'hover:bg-[#ea580c]', 'bg-[#eab308]', 'hover:bg-[#ca8a04]', 'bg-[#8b5cf6]', 'hover:bg-[#7c3aed]', 'bg-[#22c55e]', 'hover:bg-[#16a34a]');
            });
            color_change_input.classList.remove('focus:ring-primary-light');

            if(color === 'blue') {
                theme_input.value = 'blue';
                color_change_elements.forEach(element => {
                    element.classList.add('bg-[#2563eb]', 'hover:bg-[#1d4ed8]');
                });
                color_change_input.classList.add('focus:ring-[#2563eb]');
                theme_button.innerHTML = 'Blue<i data-lucide="arrow-up" class="h-5 mt-[2px] transition-transform duration-300 rotate-180" id="theme_arrow"></i>';
            } else if(color === 'orange') {
                theme_input.value = 'orange';
                console.log(color);
                color_change_elements.forEach(element => {
                    element.classList.add('bg-[#f97316]', 'hover:bg-[#ea580c]');
                });
                color_change_input.classList.add('focus:ring-[#f97316]');
                theme_button.innerHTML = 'Orange<i data-lucide="arrow-up" class="h-5 mt-[2px] transition-transform duration-300 rotate-180" id="theme_arrow"></i>';
            }else if(color === 'yellow') {
                theme_input.value = 'yellow';
                color_change_elements.forEach(element => {
                    element.classList.add('bg-[#eab308]', 'hover:bg-[#ca8a04]');
                });
                color_change_input.classList.add('focus:ring-[#eab308]');
                theme_button.innerHTML = 'Yellow<i data-lucide="arrow-up" class="h-5 mt-[2px] transition-transform duration-300 rotate-180" id="theme_arrow"></i>';
            }else if(color === 'violet') {
                theme_input.value = 'violet';
                color_change_elements.forEach(element => {
                    element.classList.add('bg-[#8b5cf6]', 'hover:bg-[#7c3aed]');
                });
                color_change_input.classList.add('focus:ring-[#8b5cf6]');
                theme_button.innerHTML = 'Violet<i data-lucide="arrow-up" class="h-5 mt-[2px] transition-transform duration-300 rotate-180" id="theme_arrow"></i>';
            }else if(color === 'green') {
                theme_input.value = 'green';
                color_change_elements.forEach(element => {
                    element.classList.add('bg-[#22c55e]', 'hover:bg-[#16a34a]');
                });
                color_change_input.classList.add('focus:ring-[#22c55e]');
                theme_button.innerHTML = 'Green<i data-lucide="arrow-up" class="h-5 mt-[2px] transition-transform duration-300 rotate-180" id="theme_arrow"></i>';
            } else {
                theme_input.value = '<?php echo $theme; ?>';
                color_change_elements.forEach(element => {
                    element.classList.add('bg-primary-light', 'hover:bg-primary-dark');
                });
                color_change_input.classList.add('focus:ring-primary-light');
            }
            lucide.createIcons();
        }

        function open_settings_menu() {
            const menu = document.getElementById('settings_menu');
            menu.classList.toggle('hidden');
        }

        const avatarInput = document.getElementById('avatar_input');
        avatarInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (!file) return;

            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                alert('Wybierz poprawny plik graficzny (JPG lub PNG).');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                let avatarContainer = document.getElementById('avatar_container');
                
                if (avatarContainer.tagName.toLowerCase() !== 'img') {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Avatar preview';
                    img.className = 'w-24 h-24 rounded-full object-cover';
                    img.id = 'avatar_container';
                    avatarContainer.parentNode.replaceChild(img, avatarContainer);
                } else {
                    avatarContainer.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        });

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

        lucide.createIcons();
    </script>
</body>
</html>