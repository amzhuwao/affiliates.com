<?php
// includes/auth.php
session_start();

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        echo "Access denied.";
        exit;
    }
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}