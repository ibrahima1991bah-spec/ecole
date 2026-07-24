# Portail de l'établissement scolaire

Site avec comptes séparés **élèves / parents / administration**, notes,
présences, et notifications **WhatsApp** automatiques. Testé et
fonctionnel (PHP 8 + MySQL/MariaDB).

## 1. Ce qui est inclus

- Connexion sécurisée par rôle (mots de passe chiffrés avec bcrypt,
  protection anti-force-brute, sessions sécurisées, protection CSRF sur
  tous les formulaires, requêtes SQL préparées).
- **Élève** : consulte ses notes et son historique de présence.
- **Parent** : suit un ou plusieurs enfants (notes + présences), reçoit
  une alerte WhatsApp automatique en cas d'absence ou de retard.
- **Administration** : crée les comptes élèves/parents, saisit les
  notes (notifie l'élève par WhatsApp) et fait l'appel (notifie les
  parents par WhatsApp en cas d'absence/retard).

## 2. Prérequis d'hébergement

- Hébergement supportant **PHP 8+** et **MySQL/MariaDB** — c'est le
  standard sur la quasi-totalité des hébergements mutualisés abordables
  (cPanel, Plesk, etc.), y compris une offre payante AwardSpace si vous
  voulez rester chez le même hébergeur que votre portfolio.
- Un nom de domaine (le sous-domaine gratuit type
  `xxx.mywebcommunity.org` ne suffira pas ici : il vous faut un
  hébergement PHP/MySQL réel, contrairement au portfolio qui est un
  simple fichier HTML).

## 3. Installation

1. Créez une base de données MySQL et un utilisateur dédié depuis le
   panneau de votre hébergeur.
2. Importez `schema.sql` dans cette base (phpMyAdmin, ou en ligne de
   commande `mysql -u ... -p nom_base < schema.sql`).
3. Ouvrez `config/config.php` et renseignez `DB_HOST`, `DB_NAME`,
   `DB_USER`, `DB_PASS`.
4. Envoyez tout le dossier sur votre hébergement par FTP/SFTP (ou le
   gestionnaire de fichiers du panneau), à la racine du domaine.
5. **Optionnel pour tester** : importez aussi `seed_demo.sql`, qui crée
   3 comptes de démonstration (voir le fichier pour les identifiants).
   **Supprimez ces comptes avant l'ouverture réelle aux élèves**
   (`DELETE FROM users WHERE username IN ('admin','eleve1','parent1');`
   — les lignes liées sont supprimées automatiquement).
6. Connectez-vous en tant qu'administration et créez vos vrais comptes
   depuis **Gérer les comptes**.

## 4. Passer le site en HTTPS

Une fois le site déployé sur un vrai nom de domaine, vous pouvez lui
donner le HTTPS gratuitement avec **Cloudflare** (comme discuté pour
votre portfolio) : ajoutez votre domaine dans Cloudflare, pointez ses
serveurs de noms vers Cloudflare, puis activez "Always Use HTTPS". Le
fichier `.htaccess` fourni redirige déjà automatiquement le HTTP vers
le HTTPS dès qu'un certificat est actif.

## 5. Configurer WhatsApp — étapes réelles

Un numéro WhatsApp personnel classique **ne suffit pas** pour envoyer
des messages automatiques : il faut passer par l'API officielle
**WhatsApp Business Platform** de Meta.

1. Créez un compte sur [business.facebook.com](https://business.facebook.com)
   et vérifiez votre établissement (documents légaux de l'école).
2. Dans Meta Business Manager, ajoutez le produit **WhatsApp**.
3. Choisissez le numéro qui enverra les messages. **Important** : ce
   numéro ne pourra plus être utilisé comme WhatsApp "normal" sur un
   téléphone en parallèle. Si vous voulez garder votre 78 569 84 58
   pour un usage personnel, utilisez un **autre numéro** dédié à
   l'école pour cette étape.
4. Créez et faites approuver par Meta les modèles de message
   ("message templates") — impossible d'envoyer du texte libre à
   quelqu'un qui ne vous a pas écrit dans les 24h précédentes. Il vous
   faut au minimum deux modèles :
   - `nouvelle_note` — variables : matière, note
   - `alerte_absence` — variables : nom de l'élève, date, statut
5. Récupérez le **jeton d'accès permanent** et l'**identifiant du
   numéro (Phone Number ID)**, et renseignez-les dans
   `config/config.php` (`WHATSAPP_ACCESS_TOKEN`,
   `WHATSAPP_PHONE_NUMBER_ID`).
6. Assurez-vous que chaque élève/parent a bien donné son accord pour
   recevoir ces messages (obligatoire côté Meta).

Tant que ces identifiants ne sont pas renseignés, le site fonctionne
normalement mais les notifications WhatsApp échouent silencieusement
(consignées dans la table `whatsapp_log` avec le statut `echec`) — rien
ne bloque la saisie des notes ou des présences.

## 6. Sécurité — à lire avant une vraie mise en production

Ce site gère des données d'enfants (notes, présences). Ce qui est déjà
en place :

- Mots de passe jamais stockés en clair (bcrypt).
- Protection contre l'injection SQL (requêtes préparées partout).
- Protection CSRF sur tous les formulaires.
- Séparation stricte des accès : un élève ne peut voir que ses propres
  données, un parent que celles de ses enfants déclarés.
- Limitation des tentatives de connexion (anti-force-brute).
- En-têtes et dossiers sensibles protégés (`.htaccess`).

Avant d'y faire entrer de vraies données d'élèves, il est fortement
recommandé de :

- Faire réaliser un **audit de sécurité** par un développeur ou une
  personne compétente en sécurité, même rapide.
- Mettre en place des **sauvegardes régulières** de la base de données.
- Vérifier vos obligations légales sur les données personnelles
  d'enfants (au Sénégal : la Commission de protection des données
  personnelles — CDP).
- Changer immédiatement les mots de passe de démonstration, ou mieux,
  les supprimer (étape 3 ci-dessus).

## 7. Limites connues (à faire évoluer si besoin)

- Pas encore de "mot de passe oublié" par email/SMS — c'est
  l'administration qui définit les mots de passe initiaux.
- Pas d'interface pour modifier ou supprimer une note/présence après
  coup (uniquement ajout) — modifiable directement en base si besoin
  pour l'instant.
- Un seul rôle "administration" pour l'instant (pas de distinction
  fine enseignant / direction) — la table `users` est prête à accueillir
  des rôles plus fins si vous en avez besoin plus tard.

## 8. Structure du projet

```
config/config.php     Identifiants base de données + WhatsApp
includes/              Connexion DB, authentification, CSRF, WhatsApp
student/dashboard.php  Espace élève
parent/dashboard.php   Espace parent
staff/                 Comptes, notes, présences (administration)
assets/style.css       Mise en page
schema.sql             Structure de la base de données
seed_demo.sql          Données de démonstration (à supprimer avant prod)
```
