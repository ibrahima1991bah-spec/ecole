-- =====================================================================
-- Portail de l'établissement scolaire — schéma de base de données
-- Compatible MySQL 5.7+ / MariaDB 10.3+ (standard sur la quasi-totalité
-- des hébergements mutualisés, y compris les offres les plus abordables)
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  role           ENUM('staff','parent','student') NOT NULL,
  full_name      VARCHAR(150) NOT NULL,
  username       VARCHAR(50)  NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  phone_whatsapp VARCHAR(20)  DEFAULT NULL COMMENT 'Format international, ex: 221785698458',
  email          VARCHAR(150) DEFAULT NULL,
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS students (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL UNIQUE,
  classe     VARCHAR(50) NOT NULL,
  matricule  VARCHAR(30) UNIQUE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS parent_student (
  parent_user_id INT NOT NULL,
  student_id     INT NOT NULL,
  PRIMARY KEY (parent_user_id, student_id),
  FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notes (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  student_id   INT NOT NULL,
  matiere      VARCHAR(80) NOT NULL,
  trimestre    VARCHAR(20) NOT NULL,
  valeur       DECIMAL(4,2) NOT NULL,
  bareme       DECIMAL(4,2) NOT NULL DEFAULT 20.00,
  commentaire  VARCHAR(255) DEFAULT NULL,
  entered_by   INT NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notified_at  DATETIME DEFAULT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (entered_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS presences (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  student_id   INT NOT NULL,
  date_cours   DATE NOT NULL,
  matiere      VARCHAR(80) DEFAULT NULL,
  statut       ENUM('present','absent','retard') NOT NULL,
  motif        VARCHAR(255) DEFAULT NULL,
  recorded_by  INT NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notified_at  DATETIME DEFAULT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50) NOT NULL,
  ip_address    VARCHAR(45) NOT NULL,
  attempted_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success       TINYINT(1) NOT NULL DEFAULT 0,
  INDEX (username, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS whatsapp_log (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  recipient_user_id  INT NOT NULL,
  message_type       VARCHAR(50) NOT NULL,
  payload            TEXT,
  status             VARCHAR(20) NOT NULL,
  sent_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (recipient_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
