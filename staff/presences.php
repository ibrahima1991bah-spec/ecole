<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/whatsapp.php';
start_secure_session();
$user = require_role('staff');
$pdo = get_pdo();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $date_cours = $_POST['date_cours'] ?? '';
        $matiere    = trim($_POST['matiere'] ?? '');
        $statut     = $_POST['statut'] ?? '';
        $motif      = trim($_POST['motif'] ?? '');

        if (!$student_id || $date_cours === '' || !in_array($statut, ['present', 'absent', 'retard'], true)) {
            throw new RuntimeException('Merci de remplir tous les champs obligatoires.');
        }

        $stmt = $pdo->prepare('INSERT INTO presences (student_id, date_cours, matiere, statut, motif, recorded_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$student_id, $date_cours, $matiere ?: null, $statut, $motif ?: null, $user['id']]);

        $notified = 0;
        if ($statut !== 'present') {
            $stmt = $pdo->prepare('SELECT u.full_name FROM users u JOIN students s ON s.user_id = u.id WHERE s.id = ?');
            $stmt->execute([$student_id]);
            $student_name = $stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'SELECT u.id, u.phone_whatsapp FROM users u
                 JOIN parent_student ps ON ps.parent_user_id = u.id
                 WHERE ps.student_id = ?'
            );
            $stmt->execute([$student_id]);
            foreach ($stmt->fetchAll() as $parent) {
                notifier_absence($parent, $student_name, $date_cours, $statut);
                $notified++;
            }
        }

        $success = 'Présence enregistrée.' . ($notified ? " {$notified} parent(s) notifié(s) par WhatsApp." : '');
    } catch (Throwable $ex) {
        $error = $ex instanceof RuntimeException ? $ex->getMessage() : 'Une erreur est survenue.';
    }
}

$students = $pdo->query(
    'SELECT s.id, u.full_name, s.classe FROM students s JOIN users u ON u.id = s.user_id ORDER BY u.full_name'
)->fetchAll();

$recent = $pdo->query(
    'SELECT p.date_cours, p.matiere, p.statut, u.full_name
     FROM presences p JOIN students s ON s.id = p.student_id JOIN users u ON u.id = s.user_id
     ORDER BY p.created_at DESC LIMIT 15'
)->fetchAll();

$page_title = 'Présences';
require __DIR__ . '/../includes/layout_start.php';
?>

<div class="section-label">Administration</div>
<h1>Faire l'appel</h1>

<?php if ($success): ?><div class="alert alert--success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="grid-2">
  <div class="card">
    <h2>Enregistrer une présence</h2>
    <form method="post">
      <?= csrf_field() ?>
      <div class="field">
        <label>Élève</label>
        <select name="student_id" required>
          <option value="">— Sélectionner —</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?> — <?= e($s['classe']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="field"><label>Date</label><input type="date" name="date_cours" required value="<?= date('Y-m-d') ?>"></div>
        <div class="field"><label>Matière (optionnel)</label><input type="text" name="matiere"></div>
      </div>
      <div class="field">
        <label>Statut</label>
        <select name="statut" required>
          <option value="present">Présent</option>
          <option value="retard">Retard</option>
          <option value="absent">Absent</option>
        </select>
      </div>
      <div class="field"><label>Motif (optionnel)</label><input type="text" name="motif"></div>
      <button class="btn" type="submit">Enregistrer</button>
    </form>
    <p class="muted" style="margin-top:10px">En cas de retard ou d'absence, le(s) parent(s) rattaché(s) sont notifiés automatiquement par WhatsApp.</p>
  </div>

  <div class="card">
    <h2>Derniers enregistrements</h2>
    <?php if (!$recent): ?>
      <p class="muted">Aucun enregistrement pour le moment.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Élève</th><th>Date</th><th>Statut</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $p):
            $badgeClass = ['present' => 'badge--present', 'retard' => 'badge--retard', 'absent' => 'badge--absent'][$p['statut']];
            $label = ['present' => 'Présent', 'retard' => 'Retard', 'absent' => 'Absent'][$p['statut']];
          ?>
            <tr>
              <td><?= e($p['full_name']) ?></td>
              <td><?= e($p['date_cours']) ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= $label ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_end.php'; ?>
