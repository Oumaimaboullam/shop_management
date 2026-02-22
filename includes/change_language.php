<?php
session_start();
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_POST['lang']) ? $_POST['lang'] : 'en');

if (in_array($lang, ['en', 'fr'])) {
    $_SESSION['lang'] = $lang;
}

// Redirect back to previous page
if (isset($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: ../index.php');
}
exit();
?>