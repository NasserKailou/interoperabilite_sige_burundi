<?php
/**
 * SIGE Burundi — Interface du connecteur d'interopérabilité
 * ==========================================================
 * Toute implémentation de connecteur (Mock ou API réelle) doit implémenter
 * cette interface. Cela garantit que le basculement mock ↔ API est transparent
 * pour le reste de l'application.
 *
 * POINT DE BRANCHEMENT API :
 * Créer une classe ApiConnector implements ConnectorInterface
 * qui effectue les appels HTTP vers les API réelles.
 * Modifier config.php : define('DATA_SOURCE_MODE', 'api');
 */

interface ConnectorInterface
{
    // ── Référentiels ─────────────────────────────────────────────────────────

    /** Retourne tous les référentiels (provinces, communes, années, etc.) */
    public function getReferentiels(): array;

    /** Retourne la liste des années de recensement disponibles */
    public function getAnnees(): array;

    /** Retourne les provinces */
    public function getProvinces(): array;

    // ── Établissements (StatEduc) ─────────────────────────────────────────────

    /** Synthèse nationale des établissements */
    public function getSyntheseEtablissements(int $annee): array;

    /** Liste détaillée des établissements avec filtres optionnels */
    public function getEtablissements(array $filtres = []): array;

    /** Un établissement par code */
    public function getEtablissement(int $codeEtab): ?array;

    /** Répartition des établissements par province */
    public function getEtablissementsParProvince(int $annee): array;

    // ── Élèves (StatEduc) ─────────────────────────────────────────────────────

    /** Synthèse nationale des élèves pour une année */
    public function getSyntheseEleves(int $annee): array;

    /** Évolution des effectifs sur toutes les années disponibles */
    public function getEvolutionEffectifs(): array;

    /** Effectifs par province pour une année */
    public function getElevesParProvince(int $annee): array;

    /** Effectifs d'un établissement pour une année */
    public function getElevesEtablissement(int $codeEtab, int $annee): ?array;

    // ── Ressources humaines (SIGE-RH) ─────────────────────────────────────────

    /** Synthèse nationale RH pour une année */
    public function getSyntheseRH(int $annee): array;

    /** RH par province pour une année */
    public function getRHParProvince(int $annee): array;

    /** RH d'un établissement */
    public function getRHEtablissement(int $codeEtab): ?array;

    /** Évolution des effectifs RH sur toutes les années */
    public function getEvolutionRH(): array;

    // ── Examens et concours ────────────────────────────────────────────────────

    /** Toutes les sessions d'examens pour une année */
    public function getSessionsExamens(int $annee): array;

    /** Une session spécifique par code examen */
    public function getSessionExamen(int $annee, string $codeExamen): ?array;

    /** Historique des résultats d'examens */
    public function getHistoriqueExamens(): array;

    // ── Méta-informations ──────────────────────────────────────────────────────

    /** Retourne le mode actif ('mock' ou 'api') */
    public function getMode(): string;

    /** Teste la connectivité de la source de données */
    public function testConnexion(): array;
}
