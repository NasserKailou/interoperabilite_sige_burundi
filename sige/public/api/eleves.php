<?php
/**
 * SIGE — API : Données élèves
 * Actions : synthese (Kanban), detail (vue complète)
 */
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (!is_ajax()) { http_response_code(403); exit; }

$connector = ConnectorFactory::getConnector();
$annee     = (int)($_GET['annee'] ?? 14);
$action    = $_GET['action'] ?? 'synthese';

if ($action === 'synthese') {
    $synthese  = $connector->getSyntheseEleves($annee);
    $evolution = $connector->getEvolutionEffectifs();

    $total    = $synthese['total'] ?? 0;
    $garcons  = $synthese['garcons'] ?? 0;
    $filles   = $synthese['filles'] ?? 0;

    json_response([
        'total'       => $total,
        'garcons'     => $garcons,
        'filles'      => $filles,
        'pct_filles'  => $total > 0 ? round($filles / $total * 100, 1) : 0,
        'pct_garcons' => $total > 0 ? round($garcons / $total * 100, 1) : 0,
        'evolution'   => array_column($evolution, 'total'),
    ]);
}

if ($action === 'detail') {
    $synthese    = $connector->getSyntheseEleves($annee);
    $evolution   = $connector->getEvolutionEffectifs();
    $parProvince = $connector->getElevesParProvince($annee);
    $refs        = $connector->getReferentiels();

    // Associer noms de provinces
    $provincesMap = [];
    foreach ($refs['provinces'] ?? [] as $p) {
        $provincesMap[$p['id_province']] = $p['libelle'];
    }

    $total   = $synthese['total'] ?? 0;
    $garcons = $synthese['garcons'] ?? 0;
    $filles  = $synthese['filles'] ?? 0;

    // Nationalités
    $nationalites = $synthese['par_nationalite'] ?? [];
    $autresNat    = 0;
    foreach ($nationalites as $n) {
        if ($n['nationalite'] !== 'Burundaise') $autresNat += $n['effectif'];
    }

    // Enrichir par province
    $provincesData = [];
    foreach ($parProvince as $p) {
        $pTotal = $p['total'] ?? 0;
        $provincesData[] = [
            'libelle'    => $provincesMap[$p['id_province']] ?? 'Province ' . $p['id_province'],
            'total'      => $pTotal,
            'garcons'    => $p['garcons'] ?? 0,
            'filles'     => $p['filles'] ?? 0,
            'pct_filles' => $pTotal > 0 ? round(($p['filles'] ?? 0) / $pTotal * 100, 1) : 0,
        ];
    }
    // Trier par effectif décroissant
    usort($provincesData, fn($a, $b) => $b['total'] <=> $a['total']);

    json_response([
        'synthese' => [
            'total'                 => $total,
            'garcons'               => $garcons,
            'filles'                => $filles,
            'pct_filles'            => $total > 0 ? round($filles / $total * 100, 1) : 0,
            'taux_scolarisation_net'=> $synthese['taux_scolarisation_net'] ?? 0,
            'autres_nationalites'   => $autresNat,
        ],
        'evolution'    => $evolution,
        'par_province' => $provincesData,
        'nationalites' => $nationalites,
    ]);
}
