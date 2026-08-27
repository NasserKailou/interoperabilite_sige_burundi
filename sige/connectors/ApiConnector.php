<?php
/**
 * SIGE Burundi — Connecteur API réelle (SQUELETTE PRÊT À BRANCHER)
 * =================================================================
 * Ce fichier est le point de branchement pour les API réelles.
 * Quand les systèmes partenaires (StatEduc, SIGE-RH, Examens, Carte)
 * exposeront leurs API, implémenter les méthodes ci-dessous.
 *
 * INSTRUCTIONS DE BRANCHEMENT :
 * 1. Renseigner les endpoints dans config.php (API_ENDPOINTS)
 * 2. Renseigner les tokens dans config.php (API_TOKENS)
 * 3. Implémenter chaque méthode en remplaçant le TODO par le vrai appel HTTP
 * 4. Modifier config.php : define('DATA_SOURCE_MODE', 'api');
 *
 * COMPATIBILITÉ : Toutes les méthodes ont exactement la même signature
 * que MockConnector, garantissant un remplacement transparent.
 */

require_once __DIR__ . '/ConnectorInterface.php';

class ApiConnector implements ConnectorInterface
{
    /**
     * Effectue un appel HTTP GET vers une API et retourne les données décodées
     * @param string $system  Clé du système ('statEduc', 'sige_rh', 'examens', 'carte')
     * @param string $endpoint Chemin de l'endpoint (ex: '/effectifs/synthese')
     * @param array  $params  Paramètres GET
     */
    private function httpGet(string $system, string $endpoint, array $params = []): array
    {
        $baseUrl = API_ENDPOINTS[$system] ?? '';
        if (empty($baseUrl)) {
            throw new RuntimeException("Endpoint API non configuré pour le système : $system");
        }

        $token = API_TOKENS[$system] ?? '';
        $url   = $baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // POINT DE BRANCHEMENT API : appel cURL standard
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
                'X-Source: SIGE-Interoperabilite',
            ],
            CURLOPT_SSL_VERIFYPEER => (APP_ENV === 'production'),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_event('error', 'ApiConnector', "Erreur cURL [$system$endpoint]: $error");
            throw new RuntimeException("Erreur de connexion à l'API $system : $error");
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            log_event('error', 'ApiConnector', "HTTP $httpCode [$system$endpoint]");
            throw new RuntimeException("L'API $system a retourné HTTP $httpCode");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Réponse JSON invalide de l'API $system");
        }

        log_event('info', 'ApiConnector', "Appel réussi [$system$endpoint]", ['params' => $params]);
        return $data;
    }

    // ─── Référentiels ─────────────────────────────────────────────────────────

    public function getReferentiels(): array
    {
        // TODO: GET /referentiels/all
        return $this->httpGet('referentiels', '/all');
    }

    public function getAnnees(): array
    {
        // TODO: GET /referentiels/annees
        return $this->httpGet('referentiels', '/annees');
    }

    public function getProvinces(): array
    {
        // TODO: GET /referentiels/provinces
        return $this->httpGet('referentiels', '/provinces');
    }

    // ─── Établissements ───────────────────────────────────────────────────────

    public function getSyntheseEtablissements(int $annee): array
    {
        // TODO: GET /statEduc/etablissements/synthese?annee={annee}
        return $this->httpGet('statEduc', '/etablissements/synthese', ['annee' => $annee]);
    }

    public function getEtablissements(array $filtres = []): array
    {
        // TODO: GET /statEduc/etablissements?province=X&secteur=Y&...
        return $this->httpGet('statEduc', '/etablissements', $filtres);
    }

    public function getEtablissement(int $codeEtab): ?array
    {
        // TODO: GET /statEduc/etablissements/{codeEtab}
        try {
            return $this->httpGet('statEduc', "/etablissements/$codeEtab");
        } catch (RuntimeException $e) {
            return null;
        }
    }

    public function getEtablissementsParProvince(int $annee): array
    {
        // TODO: GET /statEduc/etablissements/par-province?annee={annee}
        return $this->httpGet('statEduc', '/etablissements/par-province', ['annee' => $annee]);
    }

    // ─── Élèves ───────────────────────────────────────────────────────────────

    public function getSyntheseEleves(int $annee): array
    {
        // TODO: GET /statEduc/eleves/synthese?annee={annee}
        return $this->httpGet('statEduc', '/eleves/synthese', ['annee' => $annee]);
    }

    public function getEvolutionEffectifs(): array
    {
        // TODO: GET /statEduc/eleves/evolution
        return $this->httpGet('statEduc', '/eleves/evolution');
    }

    public function getElevesParProvince(int $annee): array
    {
        // TODO: GET /statEduc/eleves/par-province?annee={annee}
        return $this->httpGet('statEduc', '/eleves/par-province', ['annee' => $annee]);
    }

    public function getElevesEtablissement(int $codeEtab, int $annee): ?array
    {
        // TODO: GET /statEduc/eleves/etablissement/{codeEtab}?annee={annee}
        try {
            return $this->httpGet('statEduc', "/eleves/etablissement/$codeEtab", ['annee' => $annee]);
        } catch (RuntimeException $e) {
            return null;
        }
    }

    // ─── Ressources humaines ──────────────────────────────────────────────────

    public function getSyntheseRH(int $annee): array
    {
        // TODO: GET /sige_rh/personnel/synthese?annee={annee}
        return $this->httpGet('sige_rh', '/personnel/synthese', ['annee' => $annee]);
    }

    public function getRHParProvince(int $annee): array
    {
        // TODO: GET /sige_rh/personnel/par-province?annee={annee}
        return $this->httpGet('sige_rh', '/personnel/par-province', ['annee' => $annee]);
    }

    public function getRHEtablissement(int $codeEtab): ?array
    {
        // TODO: GET /sige_rh/personnel/etablissement/{codeEtab}
        try {
            return $this->httpGet('sige_rh', "/personnel/etablissement/$codeEtab");
        } catch (RuntimeException $e) {
            return null;
        }
    }

    public function getEvolutionRH(): array
    {
        // TODO: GET /sige_rh/personnel/evolution
        return $this->httpGet('sige_rh', '/personnel/evolution');
    }

    // ─── Examens ──────────────────────────────────────────────────────────────

    public function getSessionsExamens(int $annee): array
    {
        // TODO: GET /examens/sessions?annee={annee}
        return $this->httpGet('examens', '/sessions', ['annee' => $annee]);
    }

    public function getSessionExamen(int $annee, string $codeExamen): ?array
    {
        // TODO: GET /examens/sessions/{codeExamen}?annee={annee}
        try {
            return $this->httpGet('examens', "/sessions/$codeExamen", ['annee' => $annee]);
        } catch (RuntimeException $e) {
            return null;
        }
    }

    public function getHistoriqueExamens(): array
    {
        // TODO: GET /examens/historique
        return $this->httpGet('examens', '/historique');
    }

    // ─── Méta ─────────────────────────────────────────────────────────────────

    public function getMode(): string
    {
        return 'api';
    }

    public function testConnexion(): array
    {
        $systems = ['statEduc', 'sige_rh', 'examens', 'carte', 'referentiels'];
        $status  = [];

        foreach ($systems as $sys) {
            $endpoint = API_ENDPOINTS[$sys] ?? '';
            if (empty($endpoint)) {
                $status[$sys] = ['ok' => false, 'message' => 'Endpoint non configuré'];
                continue;
            }
            try {
                $this->httpGet($sys, '/ping');
                $status[$sys] = ['ok' => true, 'message' => 'Connecté'];
            } catch (RuntimeException $e) {
                $status[$sys] = ['ok' => false, 'message' => $e->getMessage()];
            }
        }

        return [
            'mode'      => 'api',
            'timestamp' => date('Y-m-d H:i:s'),
            'systemes'  => $status,
            'ok'        => !in_array(false, array_column($status, 'ok')),
        ];
    }
}
