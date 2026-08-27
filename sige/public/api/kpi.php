<?php
/**
 * SIGE — API : Indicateurs clés (KPI Band)
 */
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (!is_ajax()) { http_response_code(403); exit; }

$connector = ConnectorFactory::getConnector();
$annee     = (int)($_GET['annee'] ?? 14);

$eleves    = $connector->getSyntheseEleves($annee);
$rh        = $connector->getSyntheseRH($annee);
$etab      = $connector->getSyntheseEtablissements($annee);
$examens   = $connector->getSessionsExamens($annee);

// Taux de réussite CN8
$taux_cn8 = 0;
foreach ($examens as $s) {
    if ($s['code_examen'] === 'CN8') { $taux_cn8 = $s['taux_reussite']; break; }
}

// Variation élèves (par rapport à l'année précédente)
$evolution  = $connector->getEvolutionEffectifs();
$prev_total = 0;
$curr_total = $eleves['total'] ?? 0;
foreach ($evolution as $e) {
    if ($e['code_type_annee'] == $annee - 1) { $prev_total = $e['total']; break; }
}
$variation = $prev_total > 0 ? round(($curr_total - $prev_total) / $prev_total * 100, 1) : 0;

json_response([
    'etablissements' => $etab['total_etablissements'] ?? 4520,
    'eleves'         => $curr_total,
    'enseignants'    => $rh['enseignants'] ?? 0,
    'taux_reussite'  => $taux_cn8,
    'variation_eleves' => $variation,
]);
