<?php
/**
 * SIGE Burundi — Connexion PDO à la base de données
 * Singleton pattern pour éviter les connexions multiples
 */

class Database
{
    private static ?PDO $instance = null;

    /**
     * Retourne l'instance unique de la connexion PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // En mode mock, la DB peut être absente — ne pas bloquer
                if (APP_DEBUG) {
                    error_log('[SIGE-DB] Connexion échouée : ' . $e->getMessage());
                }
                self::$instance = null;
            }
        }
        return self::$instance;
    }

    // Empêcher instanciation et clonage
    private function __construct() {}
    private function __clone() {}
}
