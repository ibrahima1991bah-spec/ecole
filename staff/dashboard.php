<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
start_secure_session();
$user = require_role('staff');

$pdo = get_pdo();
$nb_eleves = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$nb_parents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn();
$nb_absences_semaine = (int)$pdo->query(
    "SELECT COUNT(*) FROM presences WHERE statut='absent' AND date_cours >= (CURDATE() - INTERVAL 7 DAY)"
)->fetchColumn();

$page_title = 'Administration';
require __DIR__ . '/../includes/layout_start.php';
?>

<div class="section-label">Administration</div>
<h1>Tableau de bord</h1>

<div class="stat-row">
  <div class="stat"><div class="n"><?= $nb_eleves ?></div><div class="l">Élèves</div></div>
  <div class="stat"><div class="n"><?= $nb_parents ?></div><div class="l">Comptes parents</div></div>
  <div class="stat"><div class="n"><?= $nb_absences_semaine ?></div><div class="l">Absences (7 derniers jours)</div></div>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Comptes</h2>
    <p class="muted">Créer et gérer les comptes élèves et parents, et les liens de famille.</p>
    <a class="btn" href="/staff/eleves.php">Gérer les comptes</a>
  </div>
  <div class="card">
    <h2>Notes</h2>
    <p class="muted">Saisir des notes — l'élève est notifié automatiquement par WhatsApp.</p>
    <a class="btn" href="/staff/notes.php">Saisir des notes</a>
  </div>
  <div class="card">
    <h2>Présences</h2>
    <p class="muted">Enregistrer présence / retard / absence — les parents sont alertés automatiquement en cas d'absence.</p>
    <a class="btn" href="/staff/presences.php">Faire l'appel</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_end.php'; ?>
