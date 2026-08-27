<?php
/**
 * SIGE Burundi — Gestion de l'authentification et des sessions admin
 */

class Auth
{
    /**
     * Démarre la session sécurisée
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false, // true en production HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public static function isLoggedIn(): bool
    {
        self::startSession();
        return !empty($_SESSION['admin_user_id']);
    }

    /**
     * Retourne l'utilisateur connecté
     */
    public static function currentUser(): ?array
    {
        self::startSession();
        if (empty($_SESSION['admin_user_id'])) return null;
        return [
            'id'    => $_SESSION['admin_user_id'],
            'nom'   => $_SESSION['admin_user_nom'] ?? '',
            'email' => $_SESSION['admin_user_email'] ?? '',
            'role'  => $_SESSION['admin_user_role'] ?? 'lecteur',
        ];
    }

    /**
     * Tente une connexion admin
     */
    public static function login(string $email, string $password): bool
    {
        self::startSession();
        try {
            $db = Database::getInstance();
            if (!$db) return false;

            $stmt = $db->prepare(
                "SELECT id, nom, email, mot_de_passe, role, actif
                 FROM sige_utilisateurs
                 WHERE email = :email AND actif = 1 LIMIT 1"
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                session_regenerate_id(true);
                $_SESSION['admin_user_id']    = $user['id'];
                $_SESSION['admin_user_nom']   = $user['nom'];
                $_SESSION['admin_user_email'] = $user['email'];
                $_SESSION['admin_user_role']  = $user['role'];
                $_SESSION['admin_login_time'] = time();

                // Journaliser la connexion
                $db->prepare(
                    "UPDATE sige_utilisateurs SET derniere_connexion = NOW() WHERE id = :id"
                )->execute([':id' => $user['id']]);

                log_event('info', 'AUTH', 'Connexion réussie', ['email' => $email]);
                return true;
            }
        } catch (Throwable $e) {
            log_event('error', 'AUTH', 'Erreur login : ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
    }

    /**
     * Protège une page admin — redirige si non connecté
     */
    public static function requireLogin(string $redirect = '/admin/login.php'): void
    {
        self::startSession();
        if (!self::isLoggedIn()) {
            redirect($redirect);
        }
    }

    /**
     * Vérifie si l'utilisateur a un rôle suffisant
     */
    public static function requireRole(string $minRole, string $redirect = '/admin/dashboard.php'): void
    {
        $roles = ['lecteur' => 1, 'editeur' => 2, 'admin' => 3, 'superadmin' => 4];
        $user  = self::currentUser();
        if (!$user || ($roles[$user['role']] ?? 0) < ($roles[$minRole] ?? 99)) {
            redirect($redirect . '?error=access_denied');
        }
    }
}
