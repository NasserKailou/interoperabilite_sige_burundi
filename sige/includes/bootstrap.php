<?php
/**
 * SIGE Burundi — Bootstrap (point d'entrée commun)
 * Inclure ce fichier en premier dans tous les scripts PHP
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../connectors/ConnectorFactory.php';

// Initialiser la session
Auth::startSession();

// Créer le répertoire de logs si absent
if (!is_dir(LOGS_PATH)) {
    @mkdir(LOGS_PATH, 0755, true);
}
