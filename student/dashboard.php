<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
start_secure_session();
$user = require_role('student');

$pdo = get_pdo();

$stmt = $pdo->prepare('SELECT id, classe, matricule FROM students WHERE user_id = ?');
$stmt->execute([$user['id']]);
$student = $stmt->fetch();

$notes = [];
$presences = [];
$stats = ['present' => 0, 'absent' => 0, 'retard' => 0, 'moyenne' => null];

if ($student) {
    $stmt = $pdo->prepare('SELECT matiere, trimestre, valeur, bareme, commentaire, created_at FROM notes WHERE student_id = ? ORDER BY created_at DESC');
    $stmt->execute([$student['id']]);
    $notes = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT date_cours, matiere, statut, motif FROM presences WHERE student_id = ? ORDER BY date_cours DESC LIMIT 30');
    $stmt->execute([$student['id']]);
    $presences = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT statut, COUNT(*) c FROM presences WHERE student_id = ? GROUP BY statut");
    $stmt->execute([$student['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $stats[$row['statut']] = (int)$row['c'];
    }

    if ($notes) {
        $sum = 0; $n = 0;
        foreach ($notes as $note) { $sum += ($note['valeur'] / $note['bareme']) * 20; $n++; }
        $stats['moyenne'] = $n ? round($sum / $n, 2) : null;
    }
}

$page_title = 'Mon espace';
require __DIR__ . '/../includes/layout_start.php';
?>

<div class="section-label">Élève</div>
<h1>Bonjour <?= e(explode(' ', $user['full_name'])[0]) ?></h1>

<?php if (!$student): ?>
  <div class="card"><p class="muted">Aucune fiche élève n'est encore associée à ce compte. Contactez l'administration.</p></div>
<?php else: ?>

  <div class="stat-row">
    <div class="stat"><div class="n"><?= $stats['moyenne'] !== null ? e((string)$stats['moyenne']) : '—' ?>/20</div><div class="l">Moyenne générale</div></div>
    <div class="stat"><div class="n"><?= (int)$stats['present'] ?></div><div class="l">Présences</div></div>
    <div class="stat"><div class="n"><?= (int)$stats['retard'] ?></div><div class="l">Retards</div></div>
    <div class="stat"><div class="n"><?= (int)$stats['absent'] ?></div><div class="l">Absences</div></div>
  </div>

  <div class="grid-2">
    <div class="card">
      <h2>Mes notes</h2>
      <?php if (!$notes): ?>
        <p class="muted">Aucune note enregistrée pour le moment.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>Matière</th><th>Trimestre</th><th>Note</th><th>Commentaire</th></tr></thead>
          <tbody>
            <?php foreach ($notes as $n): ?>
              <tr>
                <td><?= e($n['matiere']) ?></td>
                <td><?= e($n['trimestre']) ?></td>
                <td><strong><?= e($n['valeur']) ?></strong>/<?= e($n['bareme']) ?></td>
                <td class="muted"><?= e($n['commentaire'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Mes présences</h2>
      <?php if (!$presences): ?>
        <p class="muted">Aucun enregistrement de présence pour le moment.</p>
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

<?php endif; ?>

<?php require __DIR__ . '/../includes/layout_end.php'; ?>
