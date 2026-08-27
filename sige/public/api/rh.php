<?php
/**
 * SIGE — API : Ressources humaines
 */
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (!is_ajax()) { http_response_code(403); exit; }

$connector = ConnectorFactory::getConnector();
$annee     = (int)($_GET['annee'] ?? 14);
$action    = $_GET['action'] ?? 'synthese';
$refs      = $connector->getReferentiels();

$provincesMap = [];
foreach ($refs['provinces'] ?? [] as $p) {
    $provincesMap[$p['id_province']] = $p['libelle'];
}

if ($action === 'synthese') {
    $synthese  = $connector->getSyntheseRH($annee);
    $evolution = $connector->getEvolutionRH();
    json_response([
        'total_personnel' => $synthese['total_personnel'] ?? 0,
        'enseignants'     => $synthese['enseignants'] ?? 0,
        'ratio'           => $synthese['ratio_eleves_enseignant_national'] ?? 0,
        'evolution'       => array_column($evolution, 'enseignants'),
    ]);
}

if ($action === 'detail') {
    $synthese    = $connector->getSyntheseRH($annee);
    $evolution   = $connector->getEvolutionRH();
    $parProvince = $connector->getRHParProvince($annee);

    // Charger catégories depuis le JSON directement
    $rhData    = json_decode(file_get_contents(MOCK_DATA_PATH . '/rh.json'), true);
    $categories = $rhData['categories'] ?? [];

    $provincesData = [];
    foreach ($parProvince as $p) {
        $provincesData[] = [
            'libelle'       => $provincesMap[$p['id_province']] ?? 'Province ' . $p['id_province'],
            'enseignants'   => $p['enseignants'] ?? 0,
            'titulaires'    => $p['titulaires'] ?? 0,
            'contractuels'  => $p['contractuels'] ?? 0,
            'administratifs'=> $p['administratifs'] ?? 0,
            'ratio'         => $p['ratio'] ?? 0,
        ];
    }
    usort($provincesData, fn($a, $b) => $b['enseignants'] <=> $a['enseignants']);

    json_response([
        'synthese'     => $synthese,
        'evolution'    => $evolution,
        'par_province' => $provincesData,
        'categories'   => $categories,
    ]);
}
