<?php
/**
 * API Carte - Établissements géolocalisés
 * Retourne GeoJSON pour Leaflet.js
 * Source: ATLAS_COLINE_LISTE_ETAB_BURUNDI.xlsx (683 établissements avec GPS)
 */
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

// Paramètres de filtre
$province = $_GET['province'] ?? '';
$secteur  = $_GET['secteur'] ?? '';
$milieu   = $_GET['milieu'] ?? '';
$search   = trim($_GET['q'] ?? '');
$format   = $_GET['format'] ?? 'geojson';

// Charger les données de coordonnées
$coords_file = MOCK_DATA_PATH . '/coordonnees.json';
if (!file_exists($coords_file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Fichier coordonnées introuvable']);
    exit;
}

$data = json_decode(file_get_contents($coords_file), true);
$etablissements = $data['etablissements'] ?? [];
$stats_province = $data['stats_par_province'] ?? [];
$meta = $data['meta'] ?? [];

// Appliquer les filtres
$filtered = array_filter($etablissements, function($e) use ($province, $secteur, $milieu, $search) {
    if ($province && strtoupper($e['province_code'] ?? '') !== strtoupper($province)) return false;
    if ($secteur && strtolower($e['secteur'] ?? '') !== strtolower($secteur)) return false;
    if ($milieu && strtolower(trim($e['milieu'] ?? '')) !== strtolower($milieu)) return false;
    if ($search) {
        $hay = strtolower($e['nom'] . ' ' . $e['commune'] . ' ' . $e['colline']);
        if (strpos($hay, strtolower($search)) === false) return false;
    }
    return true;
});
$filtered = array_values($filtered);

// Format GeoJSON pour Leaflet
if ($format === 'geojson') {
    $features = [];
    foreach ($filtered as $e) {
        // Couleur selon secteur
        $color = match(strtolower($e['secteur'] ?? '')) {
            'fondamental'   => '#2196F3',
            'préscolaire'   => '#9C27B0',
            'post-fondamental' => '#FF9800',
            'secondaire'    => '#4CAF50',
            default         => '#607D8B'
        };
        
        // Icône selon milieu
        $icon = (strtolower(trim($e['milieu'] ?? '')) === 'urbain') ? 'city' : 'leaf';
        
        $features[] = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [(float)$e['longitude'], (float)$e['latitude']]
            ],
            'properties' => [
                'id'          => $e['code_etablissement'],
                'nom'         => $e['nom'],
                'province'    => $e['province'],
                'province_code' => $e['province_code'],
                'commune'     => $e['commune'],
                'colline'     => $e['colline'],
                'secteur'     => $e['secteur'],
                'statut'      => $e['statut'],
                'milieu'      => trim($e['milieu'] ?? ''),
                'type'        => $e['type'] ?? '',
                'annee_creation' => $e['annee_creation'],
                'color'       => $color,
                'icon'        => $icon
            ]
        ];
    }
    
    echo json_encode([
        'type'     => 'FeatureCollection',
        'features' => $features,
        'meta'     => [
            'total'        => count($features),
            'total_source' => $meta['total'] ?? 0,
            'filtres'      => compact('province', 'secteur', 'milieu', 'search')
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} elseif ($format === 'stats') {
    // Stats globales pour les compteurs de la carte
    $secteurs_count = [];
    $milieux_count  = [];
    $provinces_list = [];
    
    foreach ($etablissements as $e) {
        $s = $e['secteur'] ?? 'Autre';
        $m = trim($e['milieu'] ?? 'Autre');
        $p = $e['province_code'] ?? '';
        
        $secteurs_count[$s] = ($secteurs_count[$s] ?? 0) + 1;
        $milieux_count[$m]  = ($milieux_count[$m] ?? 0) + 1;
        if ($p && !isset($provinces_list[$p])) {
            $provinces_list[$p] = ['code' => $p, 'nom' => $e['province'], 'count' => 0];
        }
        if ($p) $provinces_list[$p]['count']++;
    }
    
    arsort($secteurs_count);
    arsort($milieux_count);
    usort($provinces_list, fn($a, $b) => strcmp($a['nom'], $b['nom']));
    
    echo json_encode([
        'total_avec_gps' => count($etablissements),
        'total_source'   => $meta['total_all'] ?? 0,
        'secteurs'       => $secteurs_count,
        'milieux'        => $milieux_count,
        'provinces'      => array_values($provinces_list),
        'stats_province' => $stats_province
    ], JSON_UNESCAPED_UNICODE);

} elseif ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="etablissements_gps_' . date('Ymd') . '.csv"');
    
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['Code', 'Nom', 'Province', 'Commune', 'Colline', 'Secteur', 'Statut', 'Milieu', 'Latitude', 'Longitude', 'Année création'], ';', '"', '\\');
    
    foreach ($filtered as $e) {
        fputcsv($out, [
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
        ], ';', '"', '\\');
    }
    fclose($out);
    exit;
}
