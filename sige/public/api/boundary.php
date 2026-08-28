<?php
/**
 * SIGE Burundi — API frontière géographique
 * Sert le GeoJSON du Burundi et le masque inversé (overlay pays voisins)
 */
require_once dirname(dirname(__DIR__)) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=86400'); // 24h cache

$type = $_GET['type'] ?? 'boundary';
$file = MOCK_DATA_PATH . '/burundi_boundary.geojson';

if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Fichier frontière introuvable']);
    exit;
}

$geojson = json_decode(file_get_contents($file), true);

if ($type === 'boundary') {
    // Frontière simple du Burundi (pour affichage du contour)
    echo json_encode($geojson);
    exit;
}

if ($type === 'mask') {
    // Masque inversé : monde entier avec trou = territoire du Burundi
    // Technique : GeoJSON Polygon avec outer ring = monde, inner ring = Burundi
    // Cela crée un "voile" sur tout ce qui est hors Burundi
    
    $features = $geojson['features'] ?? [$geojson];
    $biFeature = $features[0] ?? null;
    
    if (!$biFeature) {
        http_response_code(500);
        echo json_encode(['error' => 'Feature Burundi introuvable']);
        exit;
    }
    
    $geomType  = $biFeature['geometry']['type'] ?? '';
    $biCoords  = $biFeature['geometry']['coordinates'] ?? [];
    
    // Ring extérieur = boîte englobante monde entier (sens horaire = outer)
    $worldRing = [
        [-180, -90], [180, -90], [180, 90], [-180, 90], [-180, -90]
    ];
    
    // Construire le masque selon le type de géométrie Burundi
    $maskPolygons = [];
    
    if ($geomType === 'Polygon') {
        // biCoords[0] = anneau extérieur du Burundi (sens antihoraire normalement)
        // Pour le masque : outer = monde, inner = Burundi
        $maskPolygons[] = [$worldRing, $biCoords[0]];
    } elseif ($geomType === 'MultiPolygon') {
        // Prendre le plus grand polygone comme référence principale
        usort($biCoords, fn($a, $b) => count($b[0]) - count($a[0]));
        $maskPolygons[] = [$worldRing, $biCoords[0][0]];
        // Autres polygones (îles éventuelles) — ajoutés comme polygones pleins supplémentaires
    }
    
    $maskFeatures = array_map(fn($rings) => [
        'type'       => 'Feature',
        'properties' => ['mask' => true],
        'geometry'   => [
            'type'        => 'Polygon',
            'coordinates' => $rings
        ]
    ], $maskPolygons);
    
    echo json_encode([
        'type'     => 'FeatureCollection',
        'features' => $maskFeatures
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Type invalide. Utiliser: boundary ou mask']);
