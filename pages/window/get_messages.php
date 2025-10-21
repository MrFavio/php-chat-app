<?php
session_start();

require_once '../../php/connect.php';

$user = null;
$person = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
}

if (!$user) {
    header("Location: index.php");
    exit();
}

$stmt = $db->prepare("SELECT * FROM messages WHERE (user_id_from = ? AND user_id_to = ?) OR (user_id_from = ? AND user_id_to = ?) ORDER BY created_at ASC LIMIT 50");
$stmt->bind_param("iiii", $user_id, $_SESSION['user_two'], $_SESSION['user_two'], $user_id);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    if ($row['user_id_from'] == $user_id) {
        echo '<div class="text-right mb-[2px]">';
        if ($person != 1) {
            echo '<strong class="block text-sm text-gray-400 mb-1">You</strong>';
        }
        echo '<p class="inline-block bg-primary-light hover:bg-primary-dark text-white px-4 py-2 rounded-xl max-w-[75%] break-words text-left">';
        echo htmlspecialchars($row['content']);
        echo '</p>';
        echo '</div>';
        $person = 1;
    } else {
        $stmt_user = $db->prepare("SELECT username FROM users WHERE user_id = ?");
        $stmt_user->bind_param("i", $row['user_id_from']);
        $stmt_user->execute();
        $user_result = $stmt_user->get_result()->fetch_assoc()['username'];

        echo '<div class="text-left mb-[2px]">';
        if ($person != 2) {
            echo '<strong class="block text-sm text-gray-400 mb-1">' . htmlspecialchars($user_result) . '</strong>';
        }
        echo '<p class="inline-block bg-neutral-700 hover:bg-zinc-800 text-white px-4 py-2 rounded-xl max-w-[75%] break-words text-left">';
        echo htmlspecialchars($row['content']);
        echo '</p>';
        echo '</div>';
        $person = 2;
    }
}
?>