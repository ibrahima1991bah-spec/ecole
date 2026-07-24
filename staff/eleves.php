<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
start_secure_session();
$user = require_role('staff');
$pdo = get_pdo();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_student') {
            $full_name = trim($_POST['full_name'] ?? '');
            $username  = trim($_POST['username'] ?? '');
            $password  = $_POST['password'] ?? '';
            $classe    = trim($_POST['classe'] ?? '');
            $matricule = trim($_POST['matricule'] ?? '');
            $phone     = trim($_POST['phone_whatsapp'] ?? '');

            if ($full_name === '' || $username === '' || strlen($password) < 6 || $classe === '') {
                throw new RuntimeException('Merci de remplir tous les champs obligatoires (mot de passe : 6 caractères minimum).');
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users (role, full_name, username, password_hash, phone_whatsapp) VALUES ("student", ?, ?, ?, ?)');
            $stmt->execute([$full_name, $username, password_hash($password, PASSWORD_DEFAULT), $phone ?: null]);
            $user_id = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO students (user_id, classe, matricule) VALUES (?, ?, ?)');
            $stmt->execute([$user_id, $classe, $matricule ?: null]);
            $pdo->commit();

            $success = "Compte élève créé pour {$full_name} (identifiant : {$username}).";
        }

        if ($action === 'add_parent') {
            $full_name = trim($_POST['full_name'] ?? '');
            $username  = trim($_POST['username'] ?? '');
            $password  = $_POST['password'] ?? '';
            $phone     = trim($_POST['phone_whatsapp'] ?? '');
            $children  = $_POST['children'] ?? [];

            if ($full_name === '' || $username === '' || strlen($password) < 6) {
                throw new RuntimeException('Merci de remplir tous les champs obligatoires (mot de passe : 6 caractères minimum).');
            }
            if ($phone === '') {
                throw new RuntimeException("Le numéro WhatsApp du parent est requis pour recevoir les alertes d'absence.");
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users (role, full_name, username, password_hash, phone_whatsapp) VALUES ("parent", ?, ?, ?, ?)');
            $stmt->execute([$full_name, $username, password_hash($password, PASSWORD_DEFAULT), $phone]);
            $parent_id = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO parent_student (parent_user_id, student_id) VALUES (?, ?)');
            foreach ($children as $student_id) {
                $stmt->execute([$parent_id, (int)$student_id]);
            }
            $pdo->commit();

            $success = "Compte parent créé pour {$full_name} (identifiant : {$username}).";
        }
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $error = $ex instanceof RuntimeException ? $ex->getMessage() : "Une erreur est survenue (identifiant peut-être déjà utilisé).";
    }
}

$students = $pdo->query(
    "SELECT s.id, u.full_name, s.classe, s.matricule,
            GROUP_CONCAT(pu.full_name SEPARATOR ', ') AS parents
     FROM students s
     JOIN users u ON u.id = s.user_id
     LEFT JOIN parent_student ps ON ps.student_id = s.id
     LEFT JOIN users pu ON pu.id = ps.parent_user_id
     GROUP BY s.id
     ORDER BY u.full_name"
)->fetchAll();

$page_title = 'Comptes';
require __DIR__ . '/../includes/layout_start.php';
?>

<div class="section-label">Administration</div>
<h1>Comptes élèves &amp; parents</h1>

<?php if ($success): ?><div class="alert alert--success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="grid-2">
  <div class="card">
    <h2>Ajouter un élève</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_student">
      <div class="field"><label>Nom complet</label><input type="text" name="full_name" required></div>
      <div class="form-row">
        <div class="field"><label>Identifiant de connexion</label><input type="text" name="username" required></div>
        <div class="field"><label>Mot de passe initial</label><input type="text" name="password" required minlength="6"></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Classe</label><input type="text" name="classe" required placeholder="ex : 3ème A"></div>
        <div class="field"><label>Matricule (optionnel)</label><input type="text" name="matricule"></div>
      </div>
      <div class="field">
        <label>Numéro WhatsApp de l'élève (optionnel — international, ex : 221771234567)</label>
        <input type="tel" name="phone_whatsapp">
      </div>
      <button class="btn" type="submit">Créer le compte élève</button>
    </form>
  </div>

  <div class="card">
    <h2>Ajouter un parent</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_parent">
      <div class="field"><label>Nom complet</label><input type="text" name="full_name" required></div>
      <div class="form-row">
        <div class="field"><label>Identifiant de connexion</label><input type="text" name="username" required></div>
        <div class="field"><label>Mot de passe initial</label><input type="text" name="password" required minlength="6"></div>
      </div>
      <div class="field">
        <label>Numéro WhatsApp (requis — international, ex : 221785698458)</label>
        <input type="tel" name="phone_whatsapp" required>
      </div>
      <div class="field">
        <label>Enfant(s) à rattacher</label>
        <select name="children[]" multiple size="5">
          <?php foreach ($students as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?> — <?= e($s['classe']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="muted" style="margin:6px 0 0">Ctrl/Cmd + clic pour sélectionner plusieurs enfants.</p>
      </div>
      <button class="btn" type="submit">Créer le compte parent</button>
    </form>
  </div>
</div>

<div class="card">
  <h2>Élèves inscrits (<?= count($students) ?>)</h2>
  <?php if (!$students): ?>
    <p class="muted">Aucun élève enregistré pour le moment.</p>
  <?php else: ?>
    <table>
      <thead><tr><th>Nom</th><th>Classe</th><th>Matricule</th><th>Parent(s) lié(s)</th></tr></thead>
      <tbody>
        <?php foreach ($students as $s): ?>
          <tr>
            <td><?= e($s['full_name']) ?></td>
            <td><?= e($s['classe']) ?></td>
            <td class="muted"><?= e($s['matricule'] ?? '—') ?></td>
            <td class="muted"><?= e($s['parents'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/layout_end.php'; ?>
