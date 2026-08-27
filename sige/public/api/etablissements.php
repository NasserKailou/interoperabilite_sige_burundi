<?php
/**
 * SIGE — API : Établissements
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
$secteursMap = [];
foreach ($refs['secteurs'] ?? [] as $s) {
    $secteursMap[$s['id_secteur']] = $s['libelle'];
}
$niveauxMap = [];
foreach ($refs['niveaux'] ?? [] as $n) {
    $niveauxMap[$n['id_niveau']] = $n['libelle'];
}

if ($action === 'synthese') {
    $synthese = $connector->getSyntheseEtablissements($annee);
    json_response([
        'total'       => $synthese['total_etablissements'] ?? 0,
        'public'      => $synthese['par_secteur']['public'] ?? 0,
        'prive'       => $synthese['par_secteur']['prive'] ?? 0,
        'conventionne'=> $synthese['par_secteur']['conventionne'] ?? 0,
        'rural'       => $synthese['par_milieu']['rural'] ?? 0,
        'urbain'      => $synthese['par_milieu']['urbain'] ?? 0,
    ]);
}

if ($action === 'detail') {
    $synthese  = $connector->getSyntheseEtablissements($annee);
    $filtres   = [];
    if (!empty($_GET['province'])) $filtres['id_province'] = (int)$_GET['province'];
    if (!empty($_GET['secteur']))  $filtres['id_secteur']  = (int)$_GET['secteur'];
    if (!empty($_GET['search']))   $filtres['search']      = $_GET['search'];

    $liste = $connector->getEtablissements($filtres);
    $parProvince = $connector->getEtablissementsParProvince($annee);

    // Calcul indicateurs infra
    $infra = $synthese['infrastructures'] ?? [];
    $total = $synthese['total_etablissements'] ?? 1;

    $listeEnrichie = [];
    foreach ($liste as $e) {
        $listeEnrichie[] = [
            'code_etab'   => $e['code_etab'],
            'nom'         => $e['nom'],
            'province'    => $provincesMap[$e['id_province']] ?? '—',
            'secteur'     => $secteursMap[$e['id_secteur']] ?? '—',
            'secteur_key' => match($e['id_secteur']) { 1 => 'public', 2 => 'prive', 3 => 'conventionne', default => '' },
            'niveau'      => $niveauxMap[$e['id_niveau']] ?? '—',
            'milieu'      => $e['milieu'] ?? '—',
            'electricite' => $e['electricite'] ?? false,
            'eau_potable' => $e['eau_potable'] ?? false,
            'acces_internet' => $e['acces_internet'] ?? false,
            'nb_salles_classe' => $e['nb_salles_classe'] ?? 0,
        ];
    }

    // Enrichir par province
    $parProvinceData = [];
    foreach ($parProvince as $p) {
        $parProvinceData[] = [
            'libelle'     => $provincesMap[$p['id_province']] ?? 'Province ' . $p['id_province'],
            'total'       => $p['total'] ?? 0,
            'public'      => $p['public'] ?? 0,
            'prive'       => $p['prive'] ?? 0,
            'conventionne'=> $p['conventionne'] ?? 0,
        ];
    }
    usort($parProvinceData, fn($a, $b) => $b['total'] <=> $a['total']);

    json_response([
        'synthese'     => array_merge($synthese, [
            'infra_pct_electricite' => $total > 0 ? round(($infra['avec_electricite'] ?? 0) / $total * 100, 1) : 0,
            'infra_pct_eau'         => $total > 0 ? round(($infra['avec_eau_potable'] ?? 0) / $total * 100, 1) : 0,
            'infra_pct_internet'    => $total > 0 ? round(($infra['avec_internet'] ?? 0) / $total * 100, 1) : 0,
            'infra_pct_salles'      => ($infra['salles_total'] ?? 0) > 0
                ? round(($infra['salles_utilisees'] ?? 0) / ($infra['salles_total'] ?? 1) * 100, 1) : 0,
        ]),
        'liste'        => $listeEnrichie,
        'par_province' => $parProvinceData,
    ]);
}
