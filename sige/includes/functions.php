<?php
/**
 * SIGE Burundi — Fonctions utilitaires globales
 */

/**
 * Échappe une valeur pour l'affichage HTML (protection XSS)
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Retourne une valeur JSON encodée et sécurisée
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Génère ou récupère le token CSRF de la session
 */
function csrf_token(): string
{
    // Utiliser Auth::startSession() pour garantir le même nom de session que le reste de l'app
    Auth::startSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Vérifie le token CSRF soumis dans un formulaire
 */
function verify_csrf(string $token): bool
{
    // Même session que csrf_token() — nom SESSION_NAME défini dans config.php
    Auth::startSession();
    return isset($_SESSION[CSRF_TOKEN_NAME])
        && !empty($token)
        && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Redirige vers une URL et arrête l'exécution
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Vérifie si la requête est AJAX
 */
function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Formate un nombre avec séparateur de milliers (espace fine)
 */
function fmt_number($n, int $decimals = 0): string
{
    if ($n === null) return '—';
    return number_format((float)$n, $decimals, ',', ' ');
}

/**
 * Formate un pourcentage
 */
function fmt_pct($n, int $decimals = 1): string
{
    if ($n === null) return '—';
    return number_format((float)$n, $decimals, ',', ' ') . ' %';
}

/**
 * Journalise un message dans les logs
 */
function log_event(string $level, string $source, string $message, array $context = []): void
{
    if (!LOG_ENABLED) return;

    $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
    if (($levels[$level] ?? 0) < ($levels[LOG_LEVEL] ?? 0)) return;

    $line = sprintf(
        "[%s] [%s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $source,
        $message,
        !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );

    $logFile = LOGS_PATH . '/sige_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

    // Sauvegarder en base si disponible
    try {
        $db = Database::getInstance();
        if ($db) {
            $stmt = $db->prepare(
                "INSERT INTO sige_logs (niveau, source, message, contexte, created_at)
                 VALUES (:niveau, :source, :message, :contexte, NOW())"
            );
            $stmt->execute([
                ':niveau'   => $level,
                ':source'   => $source,
                ':message'  => $message,
                ':contexte' => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            ]);
        }
    } catch (Throwable $e) {
        // Silencieux si la DB n'est pas disponible
    }
}

/**
 * Calcule la variation en pourcentage entre deux valeurs
 */
function variation_pct(float $ancien, float $nouveau): float
{
    if ($ancien == 0) return 0.0;
    return round(($nouveau - $ancien) / $ancien * 100, 1);
}

/**
 * Retourne la couleur Bootstrap selon une variation
 */
function variation_color(float $pct): string
{
    if ($pct > 0) return 'success';
    if ($pct < 0) return 'danger';
    return 'secondary';
}
