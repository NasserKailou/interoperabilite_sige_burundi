<?php
/**
 * SIGE — API : Examens et concours
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
    $sessions  = $connector->getSessionsExamens($annee);
    $historique = $connector->getHistoriqueExamens();
    $cn8 = null;
    foreach ($sessions as $s) {
        if ($s['code_examen'] === 'CN8') { $cn8 = $s; break; }
    }
    json_response([
        'taux_reussite_cn8'  => $cn8['taux_reussite'] ?? 0,
        'admis_cn8'          => $cn8['admis'] ?? 0,
        'evolution_taux'     => array_map(fn($h) => $h['CN8']['taux'] ?? 0, $historique),
    ]);
}

if ($action === 'detail') {
    $sessions   = $connector->getSessionsExamens($annee);
    $historique = $connector->getHistoriqueExamens();

    // Enrichir par province pour CN8
    $parProvinceCn8 = [];
    foreach ($sessions as $s) {
        if ($s['code_examen'] === 'CN8') {
            foreach ($s['par_province'] ?? [] as $p) {
                $parProvinceCn8[] = [
                    'libelle'  => $provincesMap[$p['id_province']] ?? 'Province ' . $p['id_province'],
                    'inscrits' => $p['inscrits'] ?? 0,
                    'presents' => $p['presents'] ?? 0,
                    'admis'    => $p['admis'] ?? 0,
                    'taux'     => $p['taux'] ?? 0,
                ];
            }
            break;
        }
    }
    usort($parProvinceCn8, fn($a, $b) => $b['taux'] <=> $a['taux']);

    json_response([
        'sessions'       => $sessions,
        'historique'     => $historique,
        'par_province_cn8' => $parProvinceCn8,
    ]);
}
