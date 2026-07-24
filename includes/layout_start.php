<?php
/**
 * @var array $user  Utilisateur courant (fourni par la page appelante)
 * @var string $page_title
 */
$role_labels = ['staff' => 'Administration', 'parent' => 'Espace parent', 'student' => 'Espace élève'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ?? APP_NAME) ?> — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="topbar__inner">
    <span class="topbar__brand"><?= e(APP_NAME) ?></span>
    <?php if (!empty($user)): ?>
      <nav class="topbar__nav">
        <span class="topbar__role"><?= e($role_labels[$user['role']] ?? '') ?></span>
        <span class="topbar__user"><?= e($user['full_name']) ?></span>
        <a class="topbar__logout" href="/logout.php">Déconnexion</a>
      </nav>
    <?php endif; ?>
  </div>
</header>
<main class="page">
