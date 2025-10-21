<?php
session_start();

unset($_SESSION['user_id']);
unset($_SESSION['user_two']);

header("Location: ../home/index.php");
exit();
?>