<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
start_secure_session();
do_logout();
redirect('/login.php');
