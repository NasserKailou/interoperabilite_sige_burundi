<?php
/**
 * SIGE Burundi — Configuration centrale
 * ======================================
 * Ce fichier centralise toute la configuration du système d'interopérabilité.
 *
 * BASCULEMENT MOCK ↔ API RÉELLE :
 * Modifier DATA_SOURCE_MODE : 'mock' = lecture JSON local, 'api' = appels API réels
 *
 * PHP 7.3+ compatible
 */

// ─── Mode de source de données ─────────────────────────────────────────────
// 'mock' : lectures des fichiers JSON dans mock_data/
// 'api'  : appels vers les API réelles (décommenter les URLs ci-dessous)
define('DATA_SOURCE_MODE', 'mock');

// ─── Chemins absolus (système de fichiers) ──────────────────────────────────
define('ROOT_PATH',       dirname(__DIR__));
define('MOCK_DATA_PATH',  ROOT_PATH . '/mock_data');
define('ASSETS_PATH',     ROOT_PATH . '/assets');
define('LOGS_PATH',       ROOT_PATH . '/logs');
define('INCLUDES_PATH',   ROOT_PATH . '/includes');
define('CONNECTORS_PATH', ROOT_PATH . '/connectors');

// ─── BASE_URL — Détection automatique du contexte de déploiement ─────────────
// Fonctionne que le projet soit à la racine (http://localhost/)
// ou dans un sous-dossier XAMPP (http://localhost:8085/interoperabilite_sige_burundi/sige/public/)
if (!defined('BASE_URL')) {
    if (php_sapi_name() === 'cli') {
        // Serveur CLI built-in (développement sandbox)
        define('BASE_URL',        'http://localhost:3000');
        define('PUBLIC_URL',      'http://localhost:3000');
        define('ADMIN_URL',       'http://localhost:3000/admin');
        define('ASSETS_BASE_URL', '/assets');
        define('API_BASE_URL',    '/api');
        define('ADMIN_BASE_URL',  '/admin');
        define('PUBLIC_BASE_URL', '');
    } else {
        // Apache / XAMPP — calcul dynamique depuis SCRIPT_FILENAME
        // ROOT_PATH = /path/to/sige (dossier racine du projet)
        // ROOT_PATH . '/public' = dossier public/ servi par Apache
        $docRoot    = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        $publicPath = ROOT_PATH . '/public';

        // Calculer le chemin web relatif vers public/
        // Ex: docRoot=/Applications/XAMPP/htdocs, publicPath=.../htdocs/interoperabilite_sige_burundi/sige/public
        // → webPublicPath = /interoperabilite_sige_burundi/sige/public
        $webPublicPath = '';
        if (strpos($publicPath, $docRoot) === 0) {
            $webPublicPath = str_replace('\\', '/', substr($publicPath, strlen($docRoot)));
        } else {
            // Fallback : déduire depuis REQUEST_URI + SCRIPT_NAME
            $scriptName   = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
            $webPublicPath = rtrim(dirname($scriptName), '/\\');
            // Remonter si on est dans un sous-dossier (ex: /api ou /admin depuis public/)
            if (strpos($scriptName, '/api/') !== false) {
                $webPublicPath = dirname(dirname($scriptName));
            } elseif (strpos($scriptName, '/admin/') !== false) {
                $webPublicPath = dirname(dirname($scriptName)) . '/public';
            }
            $webPublicPath = rtrim($webPublicPath, '/');
        }
        $webPublicPath = rtrim($webPublicPath, '/');

        // Scheme + host
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        define('PUBLIC_BASE_URL',  $webPublicPath);                     // ex: /interoperabilite_sige_burundi/sige/public
        define('BASE_URL',         $scheme . '://' . $host . $webPublicPath);
        define('PUBLIC_URL',       BASE_URL);
        define('ASSETS_BASE_URL',  $webPublicPath . '/assets');         // ex: /inter.../sige/public/assets
        define('API_BASE_URL',     $webPublicPath . '/api');            // ex: /inter.../sige/public/api
        define('ADMIN_URL',        BASE_URL . '/../admin');
        define('ADMIN_BASE_URL',   dirname($webPublicPath) . '/admin'); // ex: /inter.../sige/admin
    }
}

// ─── Configuration base de données ──────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'sige_bu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── Endpoints API réelles (utilisés uniquement si DATA_SOURCE_MODE = 'api') ─
// POINT DE BRANCHEMENT API : remplacer les URL vides par les vrais endpoints
define('API_ENDPOINTS', [
    'iue'         => '',  // ex: 'https://api.iue.bi/v1'          ← Identification Unique des Élèves
    'statEduc'    => '',  // ex: 'https://api.statEduc.bi/v1'     ← agrège les données IUE
    'sige_rh'     => '',  // ex: 'https://api.sige-rh.bi/v1'
    'examens'     => '',  // ex: 'https://api.examens.bi/v1'
    'carte'       => '',  // ex: 'https://api.carte-scolaire.bi/v1'
    'referentiels'=> '',  // ex: 'https://api.sige.bi/referentiels/v1'
]);

// ─── Tokens API (pour les connexions API réelles) ───────────────────────────
// POINT DE BRANCHEMENT API : renseigner les tokens d'authentification
define('API_TOKENS', [
    'iue'         => '',  // Token d'accès au registre IUE
    'statEduc'    => '',
    'sige_rh'     => '',
    'examens'     => '',
    'carte'       => '',
]);

// ─── Paramètres de l'application ────────────────────────────────────────────
define('APP_NAME',    'SIGE Burundi — Système d\'Interopérabilité');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     'development'); // 'development' | 'production'
define('APP_DEBUG',   true);

// ─── Session & Sécurité ─────────────────────────────────────────────────────
define('SESSION_LIFETIME', 3600);        // 1 heure en secondes
define('SESSION_NAME',     'SIGE_SESS');
define('CSRF_TOKEN_NAME',  'sige_csrf_token');

// ─── Pagination ─────────────────────────────────────────────────────────────
define('DEFAULT_PAGE_SIZE', 20);

// ─── Logs ───────────────────────────────────────────────────────────────────
define('LOG_ENABLED', true);
define('LOG_LEVEL',   'info'); // 'debug' | 'info' | 'warning' | 'error'

// ─── Fuseau horaire ─────────────────────────────────────────────────────────
date_default_timezone_set('Africa/Bujumbura');

// ─── Gestion des erreurs ────────────────────────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ─── Autoload simple (PSR-0 light) ──────────────────────────────────────────
spl_autoload_register(function ($class) {
    $base_dirs = [
        CONNECTORS_PATH . '/',
        INCLUDES_PATH . '/',
    ];
    foreach ($base_dirs as $dir) {
        $file = $dir . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
