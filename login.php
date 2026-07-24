<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';
start_secure_session();

if (current_user()) {
    redirect('/' . current_user()['role'] . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = attempt_login($username, $password);
    if ($user) {
        redirect('/' . $user['role'] . '/dashboard.php');
    }
    $error = "Identifiant ou mot de passe incorrect, ou trop de tentatives — réessayez dans quelques minutes.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar"><div class="topbar__inner"><span class="topbar__brand"><?= e(APP_NAME) ?></span></div></header>
<div class="login-wrap">
  <div class="login-card">
    <h1>Connexion</h1>
    <p class="sub">Élèves, parents et personnel de l'établissement.</p>
    <?php if ($error): ?>
      <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/login.php">
      <?= csrf_field() ?>
      <div class="field">
        <label for="username">Identifiant</label>
        <input type="text" id="username" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn" style="width:100%">Se connecter</button>
    </form>
  </div>
</div>
</body>
</html>
