-- =====================================================================
-- Données de démonstration — À NE PAS utiliser en production.
-- Sert uniquement à tester le portail avant de créer les vrais comptes.
-- Supprimez ces comptes une fois vos tests terminés (voir README.md).
--
-- Comptes créés :
--   Administration : identifiant "admin"   / mot de passe "admin123"
--   Élève          : identifiant "eleve1"  / mot de passe "eleve123"
--   Parent         : identifiant "parent1" / mot de passe "parent123"
-- =====================================================================

INSERT INTO users (role, full_name, username, password_hash, phone_whatsapp) VALUES
('staff',   'Ibrahima Ba (Administration)', 'admin',   '$2y$10$D44zThfU80ZfBtSlYDrPoOPH4iG3XoduSvzjvr9Krgqd0uE7kSAR.', NULL),
('student', 'Aminata Diop',                 'eleve1',  '$2y$10$i9KcVBsnU1wzQrEujZOWpugnlzT1g23JvF962/mvovnrNPEtXU4cq', '221700000001'),
('parent',  'Moussa Diop',                  'parent1', '$2y$10$Ty/R65L7YP/j05PV.rs/UeFpuCAVCOO74UxmZ/IUnsvCgpKdIa83a', '221700000002');

INSERT INTO students (user_id, classe, matricule)
SELECT id, '3ème A', 'MAT-0001' FROM users WHERE username = 'eleve1';

INSERT INTO parent_student (parent_user_id, student_id)
SELECT (SELECT id FROM users WHERE username = 'parent1'), s.id
FROM students s JOIN users u ON u.id = s.user_id WHERE u.username = 'eleve1';

INSERT INTO notes (student_id, matiere, trimestre, valeur, bareme, entered_by)
SELECT s.id, 'Mathématiques', 'Trimestre 1', 15.5, 20, (SELECT id FROM users WHERE username = 'admin')
FROM students s JOIN users u ON u.id = s.user_id WHERE u.username = 'eleve1';

INSERT INTO notes (student_id, matiere, trimestre, valeur, bareme, entered_by)
SELECT s.id, 'Français', 'Trimestre 1', 13, 20, (SELECT id FROM users WHERE username = 'admin')
FROM students s JOIN users u ON u.id = s.user_id WHERE u.username = 'eleve1';

INSERT INTO presences (student_id, date_cours, matiere, statut, recorded_by)
SELECT s.id, CURDATE(), 'Mathématiques', 'present', (SELECT id FROM users WHERE username = 'admin')
FROM students s JOIN users u ON u.id = s.user_id WHERE u.username = 'eleve1';

INSERT INTO presences (student_id, date_cours, matiere, statut, recorded_by)
SELECT s.id, (CURDATE() - INTERVAL 2 DAY), 'Français', 'absent', (SELECT id FROM users WHERE username = 'admin')
FROM students s JOIN users u ON u.id = s.user_id WHERE u.username = 'eleve1';
