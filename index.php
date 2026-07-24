<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
start_secure_session();

$user = current_user();
if ($user) {
    redirect('/' . $user['role'] . '/dashboard.php');
}
redirect('/login.php');
