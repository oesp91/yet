<?php
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: /gallery.php');
} else {
    header('Location: /login.php');
}
exit;
?>