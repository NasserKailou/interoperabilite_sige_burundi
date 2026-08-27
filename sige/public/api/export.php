<?php
/**
 * API Export CSV/Excel générique
 * Usage: /api/export.php?module=eleves|rh|examens|etablissements|carte&annee=ID&format=csv|excel
 */
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$module  = $_GET['module'] ?? '';
$annee   = (int)($_GET['annee'] ?? 14);
$format  = strtolower($_GET['format'] ?? 'csv');

// Validation module
$modules_ok = ['eleves', 'rh', 'examens', 'etablissements', 'carte'];
if (!in_array($module, $modules_ok)) {
    http_response_code(400);
    echo json_encode(['error' => 'Module invalide']);
    exit;
}

// Charger le connecteur
$connector = ConnectorFactory::getConnector();

// Préparer les données selon le module
$filename = $module . '_' . $annee . '_' . date('Ymd');
$title    = 'Export SIGE Burundi';
$headers  = [];
$rows     = [];

switch ($module) {
    case 'eleves':
        $title   = 'Données Élèves — SIGE Burundi';
        $filename = "eleves_{$annee}_" . date('Ymd');
        $headers  = ['Province', 'Code', 'Élèves Total', 'Garçons', 'Filles', '% Filles', 'Variation %', 'Taux Scolarisation %'];
        
        $synthese = $connector->getSyntheseEleves($annee);
        $parProv  = $connector->getElevesParProvince($annee);
        $refs     = $connector->getProvinces();
        
        foreach ($parProv as $code => $data) {
            $libelle = $refs[$code]['libelle'] ?? $code;
            $total   = $data['total'] ?? 0;
            $garcons = $data['garcons'] ?? 0;
            $filles  = $data['filles'] ?? 0;
            $rows[]  = [
                $libelle, $code, $total, $garcons, $filles,
                $total > 0 ? round($filles / $total * 100, 1) : 0,
                round($data['variation'] ?? 0, 1),
                round($data['taux_scolarisation'] ?? 0, 1)
            ];
        }
        break;

    case 'rh':
        $title   = 'Données Ressources Humaines — SIGE Burundi';
        $filename = "rh_{$annee}_" . date('Ymd');
        $headers  = ['Province', 'Code', 'Total Personnels', 'Enseignants', 'Administratifs', '% Femmes', 'Ratio Élèves/Ens.'];
        
        $parProv = $connector->getRHParProvince($annee);
        $refs    = $connector->getProvinces();
        
        foreach ($parProv as $code => $data) {
            $libelle = $refs[$code]['libelle'] ?? $code;
            $rows[]  = [
                $libelle, $code,
                $data['total'] ?? 0,
                $data['enseignants'] ?? 0,
                $data['administratifs'] ?? 0,
                round($data['pct_femmes'] ?? 0, 1),
                round($data['ratio_eleves_ens'] ?? 0, 1)
            ];
        }
        break;

    case 'examens':
        $title   = 'Données Examens Nationaux — SIGE Burundi';
        $filename = "examens_{$annee}_" . date('Ymd');
        $headers  = ['Code Session', 'Libellé', 'Candidats', 'Admis', 'Taux Réussite %'];
        
        $sessions = $connector->getSessionsExamens($annee);
        foreach ($sessions as $s) {
            $rows[] = [
                $s['code_session'] ?? '',
                $s['libelle'] ?? '',
                $s['candidats'] ?? 0,
                $s['admis'] ?? 0,
                round($s['taux_reussite'] ?? 0, 1)
            ];
        }
        break;

    case 'etablissements':
        $title   = 'Données Établissements — SIGE Burundi';
        $filename = "etablissements_{$annee}_" . date('Ymd');
        $headers  = ['Province', 'Code', 'Total Étab.', 'Public', 'Privé', 'Conventionné'];
        
        $parProv = $connector->getEtablissementsParProvince($annee);
        $refs    = $connector->getProvinces();
        
        foreach ($parProv as $code => $data) {
            $libelle = $refs[$code]['libelle'] ?? $code;
            $rows[]  = [
                $libelle, $code,
                $data['total'] ?? 0,
                $data['public'] ?? 0,
                $data['prive'] ?? 0,
                $data['conventionne'] ?? 0
            ];
        }
        break;

    case 'carte':
        $title   = 'Établissements géolocalisés — SIGE Burundi (Source: Atlas Coline)';
        $filename = "etablissements_gps_" . date('Ymd');
        $headers  = ['Code', 'Nom Établissement', 'Province', 'Commune', 'Colline', 'Secteur', 'Statut', 'Milieu', 'Latitude', 'Longitude', 'Année Création'];
        
        $coordsFile = MOCK_DATA_PATH . '/coordonnees.json';
        if (file_exists($coordsFile)) {
            $coordData = json_decode(file_get_contents($coordsFile), true);
            $etabs     = $coordData['etablissements'] ?? [];
            foreach ($etabs as $e) {
                $rows[] = [
                    $e['code_etablissement'],
                    $e['nom'],
                    $e['province'],
                    $e['commune'],
                    $e['colline'],
                    $e['secteur'],
                    $e['statut'],
                    trim($e['milieu'] ?? ''),
                    $e['latitude'],
                    $e['longitude'],
                    $e['annee_creation']
                ];
            }
        }
        break;
}

// Headers HTTP
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
} else {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
}

// Générer CSV avec BOM UTF-8 pour Excel
$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Ligne titre et date
fputcsv($out, [$title . ' — ' . date('d/m/Y H:i')], ';', '"', '\\');
fputcsv($out, [], ';', '"', '\\');

// En-têtes colonnes
fputcsv($out, $headers, ';', '"', '\\');

// Données
foreach ($rows as $row) {
    fputcsv($out, $row, ';', '"', '\\');
}

// Pied
fputcsv($out, [], ';', '"', '\\');
fputcsv($out, ['Total lignes: ' . count($rows), 'Source: SIGE Burundi | Mode: ' . DATA_SOURCE_MODE, 'Généré le: ' . date('d/m/Y H:i:s')], ';', '"', '\\');

fclose($out);
exit;
