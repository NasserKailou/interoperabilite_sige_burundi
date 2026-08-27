<?php
/**
 * SIGE Burundi — Factory de connecteur
 * =====================================
 * Retourne le bon connecteur selon DATA_SOURCE_MODE dans config.php.
 * Pattern Singleton pour partager une seule instance par requête.
 *
 * Usage :
 *   $connector = ConnectorFactory::getConnector();
 *   $synthese  = $connector->getSyntheseEleves(14);
 */

require_once __DIR__ . '/ConnectorInterface.php';
require_once __DIR__ . '/MockConnector.php';
require_once __DIR__ . '/ApiConnector.php';

class ConnectorFactory
{
    private static ?ConnectorInterface $instance = null;

    /**
     * Retourne l'instance unique du connecteur actif
     */
    public static function getConnector(): ConnectorInterface
    {
        if (self::$instance === null) {
            self::$instance = match(DATA_SOURCE_MODE) {
                'api'   => new ApiConnector(),
                default => new MockConnector(),  // 'mock' ou toute autre valeur → sécurisé par défaut
            };
            log_event('debug', 'ConnectorFactory', 'Connecteur initialisé : ' . self::$instance->getMode());
        }
        return self::$instance;
    }

    /**
     * Force la réinitialisation du connecteur (utile pour les tests)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    // Empêcher instanciation
    private function __construct() {}
}
