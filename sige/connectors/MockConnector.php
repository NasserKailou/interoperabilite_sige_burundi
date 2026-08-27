<?php
/**
 * SIGE Burundi — Connecteur Mock (lecture des fichiers JSON)
 * ===========================================================
 * Lit les données depuis les fichiers JSON situés dans mock_data/.
 * Utilisé quand DATA_SOURCE_MODE = 'mock' dans config.php.
 *
 * POINT DE BRANCHEMENT API :
 * Quand les API réelles seront disponibles, créer ApiConnector.php
 * qui implémente les mêmes méthodes en faisant des appels HTTP.
 * Il suffit alors de passer DATA_SOURCE_MODE = 'api' dans config.php.
 */

require_once __DIR__ . '/ConnectorInterface.php';

class MockConnector implements ConnectorInterface
{
    /** Cache en mémoire pour éviter de relire les JSON plusieurs fois */
    private array $cache = [];

    // ─── Chargement des JSON ───────────────────────────────────────────────────

    /**
     * Charge un fichier JSON du dossier mock_data/ et le met en cache
     */
    private function load(string $file): array
    {
        if (isset($this->cache[$file])) {
            return $this->cache[$file];
        }
        $path = MOCK_DATA_PATH . '/' . $file;
        if (!file_exists($path)) {
            log_event('error', 'MockConnector', "Fichier JSON introuvable : $path");
            return [];
        }
        $content = file_get_contents($path);
        $data    = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_event('error', 'MockConnector', "JSON invalide : $file — " . json_last_error_msg());
            return [];
        }
        $this->cache[$file] = $data;
        log_event('debug', 'MockConnector', "Fichier JSON chargé : $file");
        return $data;
    }

    // ─── Référentiels ─────────────────────────────────────────────────────────

    public function getReferentiels(): array
    {
        return $this->load('referentiels.json');
    }

    public function getAnnees(): array
    {
        $data = $this->load('referentiels.json');
        return $data['annees'] ?? [];
    }

    public function getProvinces(): array
    {
        $data = $this->load('referentiels.json');
        return $data['provinces'] ?? [];
    }

    // ─── Établissements ───────────────────────────────────────────────────────

    public function getSyntheseEtablissements(int $annee): array
    {
        $data = $this->load('etablissements.json');
        return $data['synthese'] ?? [];
    }

    public function getEtablissements(array $filtres = []): array
    {
        $data  = $this->load('etablissements.json');
        $liste = $data['etablissements'] ?? [];

        // Application des filtres
        if (!empty($filtres['id_province'])) {
            $liste = array_filter($liste, fn($e) => $e['id_province'] == $filtres['id_province']);
        }
        if (!empty($filtres['id_secteur'])) {
            $liste = array_filter($liste, fn($e) => $e['id_secteur'] == $filtres['id_secteur']);
        }
        if (!empty($filtres['id_niveau'])) {
            $liste = array_filter($liste, fn($e) => $e['id_niveau'] == $filtres['id_niveau']);
        }
        if (!empty($filtres['milieu'])) {
            $liste = array_filter($liste, fn($e) => $e['milieu'] === $filtres['milieu']);
        }
        if (!empty($filtres['search'])) {
            $q     = mb_strtolower($filtres['search'], 'UTF-8');
            $liste = array_filter($liste, fn($e) => str_contains(mb_strtolower($e['nom'] ?? '', 'UTF-8'), $q));
        }

        return array_values($liste);
    }

    public function getEtablissement(int $codeEtab): ?array
    {
        $data = $this->load('etablissements.json');
        foreach ($data['etablissements'] ?? [] as $etab) {
            if ($etab['code_etab'] == $codeEtab) return $etab;
        }
        return null;
    }

    public function getEtablissementsParProvince(int $annee): array
    {
        $data = $this->load('etablissements.json');
        return $data['par_province'] ?? [];
    }

    // ─── Élèves ───────────────────────────────────────────────────────────────

    public function getSyntheseEleves(int $annee): array
    {
        $data = $this->load('eleves.json');

        // Cherche les données pour l'année demandée
        $synthese = $data['synthese_nationale'] ?? [];

        // Cherche dans effectifs_par_annee
        $effAnnee = null;
        foreach ($data['effectifs_par_annee'] ?? [] as $eff) {
            if ($eff['code_type_annee'] == $annee) {
                $effAnnee = $eff;
                break;
            }
        }

        return array_merge($synthese, $effAnnee ?? []);
    }

    public function getEvolutionEffectifs(): array
    {
        $data = $this->load('eleves.json');
        return $data['effectifs_par_annee'] ?? [];
    }

    public function getElevesParProvince(int $annee): array
    {
        $data   = $this->load('eleves.json');
        $result = [];
        foreach ($data['par_province'] ?? [] as $p) {
            if ($p['code_type_annee'] == $annee) {
                $result[] = $p;
            }
        }
        return $result;
    }

    public function getElevesEtablissement(int $codeEtab, int $annee): ?array
    {
        $data = $this->load('eleves.json');
        foreach ($data['effectifs_par_etablissement'] ?? [] as $item) {
            if ($item['code_etab'] == $codeEtab && $item['code_type_annee'] == $annee) {
                return $item;
            }
        }
        return null;
    }

    // ─── Ressources humaines ──────────────────────────────────────────────────

    public function getSyntheseRH(int $annee): array
    {
        $data = $this->load('rh.json');
        // En mode mock, retourne les données de l'année disponible
        return $data['synthese'] ?? [];
    }

    public function getRHParProvince(int $annee): array
    {
        $data = $this->load('rh.json');
        return $data['par_province'] ?? [];
    }

    public function getRHEtablissement(int $codeEtab): ?array
    {
        $data = $this->load('rh.json');
        foreach ($data['par_etablissement'] ?? [] as $item) {
            if ($item['code_etab'] == $codeEtab) return $item;
        }
        return null;
    }

    public function getEvolutionRH(): array
    {
        $data = $this->load('rh.json');
        return $data['evolution'] ?? [];
    }

    // ─── Examens ──────────────────────────────────────────────────────────────

    public function getSessionsExamens(int $annee): array
    {
        $data = $this->load('examens.json');
        return array_values(array_filter(
            $data['sessions'] ?? [],
            fn($s) => $s['code_type_annee'] == $annee
        ));
    }

    public function getSessionExamen(int $annee, string $codeExamen): ?array
    {
        foreach ($this->getSessionsExamens($annee) as $s) {
            if ($s['code_examen'] === $codeExamen) return $s;
        }
        return null;
    }

    public function getHistoriqueExamens(): array
    {
        $data = $this->load('examens.json');
        return $data['historique'] ?? [];
    }

    // ─── Méta ─────────────────────────────────────────────────────────────────

    public function getMode(): string
    {
        return 'mock';
    }

    public function testConnexion(): array
    {
        $files  = ['referentiels.json', 'etablissements.json', 'eleves.json', 'rh.json', 'examens.json'];
        $status = [];
        foreach ($files as $f) {
            $path = MOCK_DATA_PATH . '/' . $f;
            $ok   = file_exists($path) && is_readable($path);
            $status[$f] = [
                'ok'      => $ok,
                'message' => $ok ? 'Fichier accessible' : 'Fichier introuvable ou illisible',
                'taille'  => $ok ? filesize($path) : 0,
            ];
        }
        return [
            'mode'      => 'mock',
            'timestamp' => date('Y-m-d H:i:s'),
            'fichiers'  => $status,
            'ok'        => !in_array(false, array_column($status, 'ok')),
        ];
    }
}
