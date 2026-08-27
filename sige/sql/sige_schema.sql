-- ============================================================
-- SIGE Burundi — Schéma de base de données
-- MariaDB / MySQL 5.7+
-- Encodage : utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Base de données ────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS sige_burundi
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sige_burundi;

-- ─── Utilisateurs de la plateforme ─────────────────────────
CREATE TABLE IF NOT EXISTS sige_utilisateurs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom              VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe     VARCHAR(255) NOT NULL,
    role             ENUM('lecteur','editeur','admin','superadmin') NOT NULL DEFAULT 'lecteur',
    actif            TINYINT(1) NOT NULL DEFAULT 1,
    derniere_connexion DATETIME NULL,
    reset_token      VARCHAR(64) NULL,
    reset_token_expiry DATETIME NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Logs d'échanges ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sige_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    niveau      ENUM('debug','info','warning','error') NOT NULL DEFAULT 'info',
    source      VARCHAR(50) NOT NULL,
    message     TEXT NOT NULL,
    contexte    JSON NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_niveau (niveau),
    INDEX idx_source (source),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Configuration des connecteurs ──────────────────────────
CREATE TABLE IF NOT EXISTS sige_connecteurs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    systeme       VARCHAR(50) NOT NULL UNIQUE COMMENT 'statEduc | sige_rh | examens | carte | referentiels',
    libelle       VARCHAR(100) NOT NULL,
    mode          ENUM('mock','api') NOT NULL DEFAULT 'mock',
    endpoint_url  VARCHAR(500) NULL,
    token_api     VARCHAR(500) NULL COMMENT 'Chiffré en prod',
    actif         TINYINT(1) NOT NULL DEFAULT 1,
    dernier_test  DATETIME NULL,
    statut_test   ENUM('ok','error','pending') NULL DEFAULT 'pending',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Référentiels provinces (cache local) ───────────────────
CREATE TABLE IF NOT EXISTS sige_ref_provinces (
    id_province  SMALLINT UNSIGNED PRIMARY KEY,
    libelle      VARCHAR(100) NOT NULL,
    code         CHAR(3) NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Référentiels communes (cache local) ────────────────────
CREATE TABLE IF NOT EXISTS sige_ref_communes (
    id_commune   INT UNSIGNED PRIMARY KEY,
    id_province  SMALLINT UNSIGNED NOT NULL,
    libelle      VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_province) REFERENCES sige_ref_provinces(id_province)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Référentiels années ────────────────────────────────────
CREATE TABLE IF NOT EXISTS sige_ref_annees (
    code_type_annee SMALLINT UNSIGNED PRIMARY KEY,
    libelle         VARCHAR(20) NOT NULL,
    ordre           SMALLINT UNSIGNED NOT NULL,
    annee_reference TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Cache KPI consolidé ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS sige_kpi_cache (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code_type_annee SMALLINT UNSIGNED NOT NULL,
    indicateur      VARCHAR(100) NOT NULL,
    valeur          DECIMAL(18,4) NOT NULL,
    source_systeme  VARCHAR(50) NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_kpi (code_type_annee, indicateur, source_systeme),
    INDEX idx_annee (code_type_annee)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DONNÉES INITIALES
-- ============================================================

-- ─── Utilisateur administrateur par défaut ──────────────────
-- Mot de passe : Admin2024! (hash bcrypt)
INSERT IGNORE INTO sige_utilisateurs (nom, email, mot_de_passe, role, actif) VALUES
('Administrateur SIGE', 'admin@sige.bi',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
 'superadmin', 1),
('Jean Nkurunziza', 'jean.nk@mineac.bi',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin', 1),
('Marie Hakizimana', 'marie.hk@mineac.bi',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'editeur', 1);

-- NOTE : Le hash ci-dessus correspond au mot de passe "password" (test).
-- En production, générer un vrai hash : password_hash('VotreMotDePasse', PASSWORD_BCRYPT)
-- Pour l'administrateur principal, utiliser le mot de passe de démo : Admin2024!
-- Hash correct pour Admin2024! :
-- UPDATE sige_utilisateurs SET mot_de_passe = password_hash('Admin2024!', PASSWORD_BCRYPT)
-- WHERE email = 'admin@sige.bi';

-- ─── Connecteurs ────────────────────────────────────────────
INSERT IGNORE INTO sige_connecteurs (systeme, libelle, mode) VALUES
('statEduc',    'StatEduc — Recensement scolaire',          'mock'),
('sige_rh',     'SIGE-RH — Ressources humaines',            'mock'),
('examens',     'Examens & concours nationaux',              'mock'),
('carte',       'Carte scolaire — Géolocalisation',         'mock'),
('referentiels','Référentiels communs',                      'mock');

-- ─── Référentiels provinces ─────────────────────────────────
INSERT IGNORE INTO sige_ref_provinces (id_province, libelle, code) VALUES
(1, 'Bujumbura Mairie', 'BJM'), (2, 'Bubanza', 'BUB'),
(3, 'Bujumbura Rural', 'BRU'),  (4, 'Bururi', 'BUR'),
(5, 'Cankuzo', 'CAN'),           (6, 'Cibitoke', 'CIB'),
(7, 'Gitega', 'GIT'),            (8, 'Karuzi', 'KRZ'),
(9, 'Kayanza', 'KYZ'),           (10, 'Kirundo', 'KIR'),
(11, 'Makamba', 'MAK'),          (12, 'Muramvya', 'MUR'),
(13, 'Muyinga', 'MUY'),          (14, 'Mwaro', 'MWA'),
(15, 'Ngozi', 'NGZ'),            (16, 'Rutana', 'RUT'),
(17, 'Ruyigi', 'RUY'),           (18, 'Rumonge', 'RMG');

-- ─── Années de recensement ──────────────────────────────────
INSERT IGNORE INTO sige_ref_annees (code_type_annee, libelle, ordre, annee_reference) VALUES
(10, '2024/2025', 10, 0),
(11, '2025/2026', 11, 0),
(12, '2026/2027', 12, 0),
(13, '2027/2028', 13, 0),
(14, '2028/2029', 14, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================
