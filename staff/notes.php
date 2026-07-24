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
        $student_id  = (int)($_POST['student_id'] ?? 0);
        $matiere     = trim($_POST['matiere'] ?? '');
        $trimestre   = trim($_POST['trimestre'] ?? '');
        $valeur      = (float)($_POST['valeur'] ?? -1);
        $bareme      = (float)($_POST['bareme'] ?? 20);
        $commentaire = trim($_POST['commentaire'] ?? '');

        if (!$student_id || $matiere === '' || $trimestre === '' || $valeur < 0 || $bareme <= 0) {
            throw new RuntimeException('Merci de remplir tous les champs obligatoires.');
        }

        $stmt = $pdo->prepare('INSERT INTO notes (student_id, matiere, trimestre, valeur, bareme, commentaire, entered_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$student_id, $matiere, $trimestre, $valeur, $bareme, $commentaire ?: null, $user['id']]);

        // Notification WhatsApp automatique à l'élève.
        $stmt = $pdo->prepare('SELECT u.id, u.phone_whatsapp, u.full_name FROM users u JOIN students s ON s.user_id = u.id WHERE s.id = ?');
        $stmt->execute([$student_id]);
        $student_user = $stmt->fetch();
        if ($student_user) {
            notifier_nouvelle_note($student_user, $matiere, $valeur . '/' . $bareme);
        }

        $success = 'Note enregistrée' . ($student_user && $student_user['phone_whatsapp'] ? ' — notification WhatsApp envoyée à l\'élève.' : '.');
    } catch (Throwable $ex) {
        $error = $ex instanceof RuntimeException ? $ex->getMessage() : 'Une erreur est survenue.';
    }
}

$students = $pdo->query(
    'SELECT s.id, u.full_name, s.classe FROM students s JOIN users u ON u.id = s.user_id ORDER BY u.full_name'
)->fetchAll();

$recent = $pdo->query(
    'SELECT n.matiere, n.trimestre, n.valeur, n.bareme, n.created_at, u.full_name
     FROM notes n JOIN students s ON s.id = n.student_id JOIN users u ON u.id = s.user_id
     ORDER BY n.created_at DESC LIMIT 15'
)->fetchAll();

$page_title = 'Notes';
require __DIR__ . '/../includes/layout_start.php';
?>

<div class="section-label">Administration</div>
<h1>Saisie des notes</h1>

<?php if ($success): ?><div class="alert alert--success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="grid-2">
  <div class="card">
    <h2>Nouvelle note</h2>
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
        <div class="field"><label>Matière</label><input type="text" name="matiere" required></div>
        <div class="field"><label>Trimestre</label><input type="text" name="trimestre" required placeholder="ex : Trimestre 1"></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Note obtenue</label><input type="number" step="0.25" min="0" name="valeur" required></div>
        <div class="field"><label>Barème</label><input type="number" step="0.25" min="1" name="bareme" value="20" required></div>
      </div>
      <div class="field"><label>Commentaire (optionnel)</label><textarea name="commentaire" rows="2"></textarea></div>
      <button class="btn" type="submit">Enregistrer et notifier l'élève</button>
    </form>
  </div>

  <div class="card">
    <h2>Dernières notes saisies</h2>
    <?php if (!$recent): ?>
      <p class="muted">Aucune note saisie pour le moment.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Élève</th><th>Matière</th><th>Note</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $n): ?>
            <tr>
              <td><?= e($n['full_name']) ?></td>
              <td><?= e($n['matiere']) ?></td>
              <td><strong><?= e($n['valeur']) ?></strong>/<?= e($n['bareme']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_end.php'; ?>
