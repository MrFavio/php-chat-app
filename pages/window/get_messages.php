<?php
session_start();
require_once '../../php/connect.php';

ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_two'])) {
    echo json_encode(['html' => '', 'last_time' => '0', 'first_time' => '0', 'last_sender' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_two = $_SESSION['user_two'];
$last_time = isset($_GET['last_time']) ? $_GET['last_time'] : '0';
$first_time = isset($_GET['first_time']) ? $_GET['first_time'] : '0';
$last_sender = isset($_GET['last_sender']) ? $_GET['last_sender'] : 0;

$stmt_check = $db->prepare("SELECT 1 FROM friends WHERE (user_one = ? AND user_two = ?) OR (user_one = ? AND user_two = ?)");
$stmt_check->bind_param("iiii", $user_id, $user_two, $user_two, $user_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    echo json_encode(['html' => 'Błąd uprawnień', 'last_time' => '0', 'first_time' => '0', 'last_sender' => 0]);
    exit();
}

$stmt_friend = $db->prepare("SELECT username FROM users WHERE user_id = ?");
$stmt_friend->bind_param("i", $user_two);
$stmt_friend->execute();
$friend_res = $stmt_friend->get_result()->fetch_assoc();
$friend_name = $friend_res['username'] ?? 'Friend';

if ($first_time !== '0' && !empty($first_time)) {
    $query = "SELECT * FROM ( SELECT * FROM messages WHERE ((user_id_from = ? AND user_id_to = ?) OR (user_id_from = ? AND user_id_to = ?)) AND created_at < ? ORDER BY created_at DESC LIMIT 50 ) AS sub ORDER BY created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("iiiis", $user_id, $user_two, $user_two, $user_id, $first_time);
} else if ($last_time === '0' || empty($last_time)) {
    $query = "SELECT * FROM ( SELECT * FROM messages WHERE (user_id_from = ? AND user_id_to = ?) OR (user_id_from = ? AND user_id_to = ?) ORDER BY created_at DESC LIMIT 50 ) AS sub ORDER BY created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("iiii", $user_id, $user_two, $user_two, $user_id);
} else {
    $query = "SELECT * FROM messages WHERE ((user_id_from = ? AND user_id_to = ?) OR (user_id_from = ? AND user_id_to = ?)) AND created_at > ? ORDER BY created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("iiiis", $user_id, $user_two, $user_two, $user_id, $last_time);
}

$stmt->execute();
$result = $stmt->get_result();

$html = '';
$new_last_time = $last_time;
$new_first_time = $first_time;
$is_first_row = true;

while($row = $result->fetch_assoc()) {
    if ($is_first_row) {
        $new_first_time = $row['created_at'];
        $is_first_row = false;
    }
    $new_last_time = $row['created_at'];

    $date = new DateTime($row['created_at']);

    $formatted_time = $date->format('j F Y, H:i');

    if ($row['user_id_from'] == $user_id) {
        $html .= '<div class="text-right mb-[2px]">';
        if ($last_sender != 1) {
            $html .= '<strong class="block text-sm text-gray-400 mb-1">You</strong>';
        }
        $html .= '<p class="inline-block bg-primary-light hover:bg-primary-dark text-white px-4 py-2 rounded-xl max-w-[75%] break-words text-left" title="' . htmlspecialchars($formatted_time) . '">';
        $html .= htmlspecialchars($row['content']);
        $html .= '</p></div>';
        $last_sender = 1;
    } else {
        $html .= '<div class="text-left mb-[2px]">';
        if ($last_sender != 2) {
            $html .= '<strong class="block text-sm text-gray-400 mb-1">' . htmlspecialchars($friend_name) . '</strong>';
        }
        $html .= '<p class="inline-block bg-neutral-700 hover:bg-zinc-800 text-white px-4 py-2 rounded-xl max-w-[75%] break-words text-left" title="' . htmlspecialchars($formatted_time) . '">';
        $html .= htmlspecialchars($row['content']);
        $html .= '</p></div>';
        $last_sender = 2;
    }
}

$count = $result->num_rows;

echo json_encode([
    'html' => $html,
    'last_time' => $new_last_time,
    'first_time' => $new_first_time,
    'last_sender' => $last_sender,
    'count' => $count
]);
exit();