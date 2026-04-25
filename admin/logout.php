<?php
session_start();

unset($_SESSION['admin_id'], $_SESSION['role'], $_SESSION['csrf_token']);

if (empty($_SESSION)) {
    session_destroy();
}

header("Location: ../login.php");
exit;

