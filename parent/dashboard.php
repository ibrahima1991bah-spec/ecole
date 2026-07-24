<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
start_secure_session();
$user = require_role('parent');

$pdo = get_pdo();

$stmt = $pdo->prepare(
    'SELECT s.id, u.full_name, s.classe FROM parent_student ps
     JOIN students s ON s.id = ps.student_id
     JOIN users u ON u.id = s.user_id
     WHERE ps.parent_user_id = ?
     ORDER BY u.full_name'
);
$stmt->execute([$user['id']]);
$children = $stmt->fetchAll();

$selected_id = isset($_GET['enfant']) ? (int)$_GET['enfant'] : ($children[0]['id'] ?? null);
$selected = null;
foreach ($children as $c) {
    if ((int)$c['id'] === $selected_id) { $selected = $c; break; }
}

$notes = [];
$presences = [];
$stats = ['present' => 0, 'absent' => 0, 'retard' => 0];

if ($selected) {
    $stmt = $pdo->prepare('SELECT matiere, trimestre, valeur, bareme, commentaire FROM notes WHERE student_id = ? ORDER BY created_at DESC');
    $stmt->execute([$selected['id']]);
    $notes = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT date_cours, matiere, statut, motif FROM presences WHERE student_id = ? ORDER BY date_cours DESC LIMIT 30');
    $stmt->execute([$selected['id']]);
    $presences = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT statut, COUNT(*) c FROM presences WHERE student_id = ? GROUP BY statut');
    $stmt->execute([$selected['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $stats[$row['statut']] = (int)$row['c'];
    }
}

$page_title = 'Espace parent';
require __DIR__ . '/../includes/layout_start.php';
?>

<div class="section-label">Parent</div>
<h1>Suivi de votre enfant</h1>

<?php if (!$children): ?>
  <div class="card"><p class="muted">Aucun enfant n'est encore associé à ce compte. Contactez l'administration de l'établissement.</p></div>
<?php else: ?>

  <?php if (count($children) > 1): ?>
    <div class="child-switch">
      <?php foreach ($children as $c): ?>
        <a href="?enfant=<?= (int)$c['id'] ?>" class="<?= $c['id'] == $selected_id ? 'active' : '' ?>"><?= e($c['full_name']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h2 style="margin-bottom:2px"><?= e($selected['full_name']) ?></h2>
  <p class="muted" style="margin-top:0">Classe : <?= e($selected['classe']) ?></p>

  <div class="stat-row">
    <div class="stat"><div class="n"><?= (int)$stats['present'] ?></div><div class="l">Présences</div></div>
    <div class="stat"><div class="n"><?= (int)$stats['retard'] ?></div><div class="l">Retards</div></div>
    <div class="stat"><div class="n"><?= (int)$stats['absent'] ?></div><div class="l">Absences</div></div>
  </div>

  <div class="grid-2">
    <div class="card">
      <h2>Notes</h2>
      <?php if (!$notes): ?>
        <p class="muted">Aucune note enregistrée pour le moment.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>Matière</th><th>Trimestre</th><th>Note</th></tr></thead>
          <tbody>
            <?php foreach ($notes as $n): ?>
              <tr>
                <td><?= e($n['matiere']) ?></td>
                <td><?= e($n['trimestre']) ?></td>
                <td><strong><?= e($n['valeur']) ?></strong>/<?= e($n['bareme']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Présences</h2>
      <?php if (!$presences): ?>
        <p class="muted">Aucun enregistrement pour le moment.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>Date</th><th>Matière</th><th>Statut</th></tr></thead>
          <tbody>
            <?php foreach ($presences as $p):
              $badgeClass = ['present' => 'badge--present', 'retard' => 'badge--retard', 'absent' => 'badge--absent'][$p['statut']];
              $label = ['present' => 'Présent', 'retard' => 'Retard', 'absent' => 'Absent'][$p['statut']];
            ?>
              <tr>
                <td><?= e($p['date_cours']) ?></td>
                <td><?= e($p['matiere'] ?? '—') ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= $label ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <p class="muted" style="margin:0">
      Les alertes d'absence sont envoyées automatiquement par WhatsApp au numéro
      renseigné sur votre compte, dès qu'une absence est enregistrée par l'établissement.
    </p>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/layout_end.php'; ?>
