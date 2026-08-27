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

// ─── Chemins absolus ────────────────────────────────────────────────────────
define('ROOT_PATH',       dirname(__DIR__));
define('MOCK_DATA_PATH',  ROOT_PATH . '/mock_data');
define('ASSETS_PATH',     ROOT_PATH . '/assets');
define('LOGS_PATH',       ROOT_PATH . '/logs');
define('INCLUDES_PATH',   ROOT_PATH . '/includes');
define('CONNECTORS_PATH', ROOT_PATH . '/connectors');

// ─── Configuration base de données ──────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'sige_bu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── Endpoints API réelles (utilisés uniquement si DATA_SOURCE_MODE = 'api') ─
// POINT DE BRANCHEMENT API : remplacer les URL vides par les vrais endpoints
define('API_ENDPOINTS', [
    'statEduc'    => '',  // ex: 'https://api.statEduc.bi/v1'
    'sige_rh'     => '',  // ex: 'https://api.sige-rh.bi/v1'
    'examens'     => '',  // ex: 'https://api.examens.bi/v1'
    'carte'       => '',  // ex: 'https://api.carte-scolaire.bi/v1'
    'referentiels'=> '',  // ex: 'https://api.sige.bi/referentiels/v1'
]);

// ─── Tokens API (pour les connexions API réelles) ───────────────────────────
// POINT DE BRANCHEMENT API : renseigner les tokens d'authentification
define('API_TOKENS', [
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
