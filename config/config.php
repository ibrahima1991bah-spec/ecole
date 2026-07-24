<?php
// =====================================================================
// CONFIGURATION — à adapter avant la mise en ligne.
// Ce fichier contient des secrets : ne le rendez JAMAIS accessible
// publiquement (voir le .htaccess fourni) et ne le partagez jamais
// tel quel (email, dépôt public, etc.).
// =====================================================================

// --- Base de données -------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecole_portail');
define('DB_USER', 'CHANGE_MOI');
define('DB_PASS', 'CHANGE_MOI');

// --- WhatsApp Business Cloud API (Meta) -------------------------------
// À remplir une fois votre compte WhatsApp Business API configuré.
// Voir README.md, section "Configurer WhatsApp", pour la marche à
// suivre complète — un simple numéro WhatsApp personnel ne suffit pas.
define('WHATSAPP_ACCESS_TOKEN', '');     // Jeton d'accès permanent (Meta)
define('WHATSAPP_PHONE_NUMBER_ID', '');  // Identifiant technique du numéro expéditeur
define('WHATSAPP_API_VERSION', 'v20.0');

// --- Sécurité des sessions --------------------------------------------
define('SESSION_LIFETIME', 60 * 60 * 2); // Déconnexion après 2h d'inactivité

// --- Divers ------------------------------------------------------------
define('APP_NAME', "Portail de l'établissement");
