<?php
/**
 * SIGE Burundi — Router PHP built-in server
 * Gère le routage des assets, API et pages
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Map des MIME types pour les assets statiques
$mimeTypes = [
    'css'  => 'text/css',
    'js'   => 'application/javascript',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2'=> 'font/woff2',
    'ttf'  => 'font/ttf',
    'json' => 'application/json',
    'map'  => 'application/json',
];

$root = __DIR__;

// ─── Assets statiques (/assets/, /mock_data/) ──────────────
// Résoudre les chemins relatifs (ex: ../assets/ depuis public/)
$normalizedUri = '/' . ltrim($uri, '/');
$assetFile = $root . $normalizedUri;

// Essayer aussi sans le /public/ prefix
$patterns = [
    $root . $normalizedUri,
    $root . str_replace('/../assets/', '/assets/', $normalizedUri),
];

foreach ($patterns as $tryFile) {
    $realFile = realpath($tryFile);
    if ($realFile && is_file($realFile) && str_starts_with($realFile, $root)) {
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
            readfile($realFile);
            return true;
        }
    }
}

// ─── Routes API (dans public/api/) ─────────────────────────
if (str_starts_with($uri, '/api/')) {
    $apiFile = $root . '/public' . $uri;
    if (is_file($apiFile)) {
        require $apiFile;
        return true;
    }
}

// ─── Routes Admin ────────────────────────────────────────────
if (str_starts_with($uri, '/admin') || str_starts_with($uri, '/admin/')) {
    $adminFile = $root . $uri;
    if (is_file($adminFile)) {
        require $adminFile;
        return true;
    }
    // Essayer index.php
    $adminIndex = $root . rtrim($uri, '/') . '/index.php';
    if (is_file($adminIndex)) {
        require $adminIndex;
        return true;
    }
    // Fallback admin login
    require $root . '/admin/login.php';
    return true;
}

// ─── Page d'accueil ─────────────────────────────────────────
if ($uri === '/' || $uri === '/index.php') {
    require $root . '/public/index.php';
    return true;
}

// ─── Fichiers dans public/ ───────────────────────────────────
$publicFile = $root . '/public' . $uri;
if (is_file($publicFile)) {
    $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        readfile($publicFile);
        return true;
    }
    require $publicFile;
    return true;
}

// ─── Fallback → page d'accueil ──────────────────────────────
http_response_code(404);
require $root . '/public/index.php';
return true;
