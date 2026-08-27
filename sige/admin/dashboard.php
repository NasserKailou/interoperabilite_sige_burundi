<?php
/**
 * SIGE Burundi — Administration — Tableau de bord principal
 */
$pageTitle  = 'Tableau de bord';
$pageIcon   = 'fas fa-tachometer-alt';
$activePage = 'dashboard';
include __DIR__ . '/layout.php';

$connector = ConnectorFactory::getConnector();
$anneeRef  = 14;

$synthEleves  = $connector->getSyntheseEleves($anneeRef);
$synthRH      = $connector->getSyntheseRH($anneeRef);
$synthEtab    = $connector->getSyntheseEtablissements($anneeRef);
$sessionsExam = $connector->getSessionsExamens($anneeRef);
$evolution    = $connector->getEvolutionEffectifs();
$refData      = $connector->getReferentiels();

$taux_cn8 = 0;
foreach ($sessionsExam as $s) {
    if ($s['code_examen'] === 'CN8') { $taux_cn8 = $s['taux_reussite']; break; }
}

$statusConnecteur = $connector->testConnexion();
?>

<!-- ─── Alertes mode mock ─── -->
<?php if (DATA_SOURCE_MODE === 'mock'): ?>
<div class="alert alert-warning alert-dismissible fade show" style="border-radius:10px;border:none">
    <i class="fas fa-database mr-2"></i>
    <strong>Mode démo actif :</strong> Les données proviennent des fichiers JSON mockés (<code>mock_data/</code>).
    Pour brancher les API réelles, modifier <code>DATA_SOURCE_MODE</code> dans <code>includes/config.php</code>.
    <button type="button" class="close" data-dismiss="alert">×</button>
</div>
<?php endif; ?>

<!-- ─── Small Boxes (KPI) ─── -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= fmt_number($synthEtab['total_etablissements'] ?? 0) ?></h3>
                <p>Établissements</p>
            </div>
            <div class="icon"><i class="fas fa-school"></i></div>
            <a href="etablissements.php" class="small-box-footer">Détails <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= fmt_number($synthEleves['total'] ?? 0) ?></h3>
                <p>Élèves scolarisés</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <a href="eleves.php" class="small-box-footer">Détails <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= fmt_number($synthRH['enseignants'] ?? 0) ?></h3>
                <p>Enseignants</p>
            </div>
            <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <a href="rh.php" class="small-box-footer">Détails <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= fmt_pct($taux_cn8) ?></h3>
                <p>Taux de réussite CN8</p>
            </div>
            <div class="icon"><i class="fas fa-star"></i></div>
            <a href="examens.php" class="small-box-footer">Détails <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- ─── Graphiques ─── -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-2" style="color:#1e88e5"></i>
                    Évolution des effectifs élèves (pluriannuel)
                </h3>
            </div>
            <div class="card-body">
                <canvas id="chart-evolution" style="height:280px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie mr-2" style="color:#43a047"></i>
                    Établissements par secteur
                </h3>
            </div>
            <div class="card-body">
                <canvas id="chart-secteur" style="height:200px"></canvas>
                <div class="mt-3">
                    <?php
                    $ps = $synthEtab['par_secteur'] ?? [];
                    $total_etab = $synthEtab['total_etablissements'] ?? 1;
                    foreach ([['Public','public','info'],['Privé','prive','danger'],['Conventionné','conventionne','success']] as [$lbl, $key, $cls]):
                        $n = $ps[$key] ?? 0;
                        $pct = round($n / max($total_etab, 1) * 100, 1);
                    ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span><?= $lbl ?></span>
                        <span class="badge badge-<?= $cls ?>"><?= fmt_number($n) ?> (<?= $pct ?>%)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── État des connecteurs ─── -->
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header" style="background:linear-gradient(135deg,#1565c0,#1e88e5);color:white">
                <h3 class="card-title text-white">
                    <i class="fas fa-plug mr-2"></i> État des connecteurs d'interopérabilité
                </h3>
                <div class="card-tools">
                    <a href="connecteurs.php" class="btn btn-sm btn-light">
                        <i class="fas fa-cog"></i> Gérer
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php
                $connecteurConfig = [
                    ['StatEduc (recensement scolaire)', 'fas fa-database', 'mock'],
                    ['SIGE-RH (ressources humaines)',   'fas fa-users-cog', 'mock'],
                    ['Examens & concours',              'fas fa-file-alt',  'mock'],
                    ['Carte scolaire',                  'fas fa-map-marked-alt', 'mock'],
                ];
                foreach ($connecteurConfig as [$nom, $icon, $mode]):
                ?>
                <div class="d-flex align-items-center px-3 py-2 border-bottom">
                    <div style="width:36px;height:36px;background:#e3f2fd;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#1e88e5;flex-shrink:0">
                        <i class="<?= $icon ?>"></i>
                    </div>
                    <div class="ml-3 flex-grow-1">
                        <div style="font-weight:700;font-size:.88rem"><?= e($nom) ?></div>
                        <div style="font-size:.75rem;color:#9aa0a6">JSON local &bull; mock_data/</div>
                    </div>
                    <div class="connector-status">
                        <div class="dot green"></div>
                        <span class="badge badge-sige-mock rounded-pill px-2">ACTIF — mock</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer text-center py-2">
                <a href="connecteurs.php" style="font-size:.82rem;color:#1e88e5;font-weight:600">
                    <i class="fas fa-arrow-right mr-1"></i> Gérer les connecteurs et tester les API
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <!-- Info boxes RH -->
        <div class="row">
            <?php
            $rhData = $connector->getSyntheseRH($anneeRef);
            $infoBoxes = [
                ['Personnel total', $rhData['total_personnel'] ?? 0, 'fas fa-id-card', 'bg-info'],
                ['Titulaires', $rhData['enseignants_titulaires'] ?? 0, 'fas fa-certificate', 'bg-success'],
                ['Contractuels', $rhData['enseignants_contractuels'] ?? 0, 'fas fa-handshake', 'bg-warning'],
                ['Administratifs', $rhData['administratifs'] ?? 0, 'fas fa-briefcase', 'bg-secondary'],
            ];
            foreach ($infoBoxes as [$label, $val, $icon, $cls]):
            ?>
            <div class="col-6 mb-3">
                <div class="info-box">
                    <span class="info-box-icon <?= $cls ?> elevation-1">
                        <i class="<?= $icon ?>"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text" style="font-size:.8rem"><?= e($label) ?></span>
                        <span class="info-box-number" style="font-size:1.2rem;font-weight:800"><?= fmt_number($val) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Taux réussite examens -->
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2" style="color:#e53935"></i> Taux de réussite aux examens</h3>
            </div>
            <div class="card-body pt-0">
                <?php foreach ($sessionsExam as $s): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.82rem;font-weight:600"><?= e($s['examen']) ?></span>
                        <strong style="color:<?= $s['taux_reussite'] >= 75 ? '#43a047' : ($s['taux_reussite'] >= 60 ? '#fb8c00' : '#e53935') ?>">
                            <?= fmt_pct($s['taux_reussite']) ?>
                        </strong>
                    </div>
                    <div class="progress" style="height:8px;border-radius:20px">
                        <div class="progress-bar <?= $s['taux_reussite'] >= 75 ? 'bg-success' : ($s['taux_reussite'] >= 60 ? 'bg-warning' : 'bg-danger') ?>"
                             style="width:<?= $s['taux_reussite'] ?>%;border-radius:20px">
                        </div>
                    </div>
                    <div style="font-size:.72rem;color:#9aa0a6;margin-top:3px">
                        <?= fmt_number($s['admis']) ?> admis / <?= fmt_number($s['inscrits']) ?> inscrits
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ─── Tableau provinces (extrait) ─── -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map mr-2"></i> Données par province — Récapitulatif</h3>
                <div class="card-tools">
                    <span class="badge badge-sige-mock">Année 2028/2029</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Province</th>
                                <th>Établissements</th>
                                <th>Élèves</th>
                                <th>Enseignants</th>
                                <th>Ratio él./ens.</th>
                                <th>Taux réussite CN8</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $provinces   = $refData['provinces'] ?? [];
                        $parProv     = $connector->getEtablissementsParProvince($anneeRef);
                        $elevProv    = $connector->getElevesParProvince($anneeRef);
                        $rhProv      = $connector->getRHParProvince($anneeRef);
                        $examSess    = $connector->getSessionsExamens($anneeRef);
                        $examProv    = [];
                        foreach ($examSess as $s) {
                            if ($s['code_examen'] === 'CN8') {
                                foreach ($s['par_province'] ?? [] as $ep) {
                                    $examProv[$ep['id_province']] = $ep['taux'] ?? 0;
                                }
                                break;
                            }
                        }

                        $etabMap  = []; foreach ($parProv as $p) $etabMap[$p['id_province']] = $p;
                        $elevMap  = []; foreach ($elevProv as $p) $elevMap[$p['id_province']] = $p;
                        $rhMap    = []; foreach ($rhProv as $p) $rhMap[$p['id_province']] = $p;

                        foreach ($provinces as $prov):
                            $id   = $prov['id_province'];
                            $etab = $etabMap[$id] ?? [];
                            $elev = $elevMap[$id] ?? [];
                            $rh   = $rhMap[$id] ?? [];
                            $taux = $examProv[$id] ?? null;
                        ?>
                        <tr>
                            <td><strong><?= e($prov['libelle']) ?></strong></td>
                            <td><?= fmt_number($etab['total'] ?? 0) ?></td>
                            <td><?= fmt_number($elev['total'] ?? 0) ?></td>
                            <td><?= fmt_number($rh['enseignants'] ?? 0) ?></td>
                            <td><?= $rh['ratio'] ?? '—' ?></td>
                            <td>
                                <?php if ($taux !== null): ?>
                                <span class="badge badge-<?= $taux >= 80 ? 'success' : ($taux >= 70 ? 'warning' : 'danger') ?>">
                                    <?= fmt_pct($taux) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$evolutionJson = json_encode($evolution);
$synthEtabJson = json_encode($synthEtab['par_secteur'] ?? []);
$pageScript = <<<JS
$(function() {
    var evol = $evolutionJson;
    var ctx1 = document.getElementById('chart-evolution').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: evol.map(e => e.libelle),
            datasets: [
                { label: 'Total élèves', data: evol.map(e => e.total), borderColor: '#1e88e5', backgroundColor: 'rgba(30,136,229,.1)', fill: true, tension: 0.4, borderWidth: 2 },
                { label: 'Garçons',      data: evol.map(e => e.garcons), borderColor: '#29b6f6', borderDash: [5,5], tension: 0.4, borderWidth: 2 },
                { label: 'Filles',       data: evol.map(e => e.filles), borderColor: '#e53935', borderDash: [5,5], tension: 0.4, borderWidth: 2 },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position:'bottom' }, tooltip: { callbacks: { label: ctx => ' '+Number(ctx.raw).toLocaleString('fr-FR') } } },
            scales: { y: { ticks: { callback: v => Number(v).toLocaleString('fr-FR') } } }
        }
    });
    var ps = $synthEtabJson;
    var ctx2 = document.getElementById('chart-secteur').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Public','Privé','Conventionné'],
            datasets: [{ data: [ps.public||0, ps.prive||0, ps.conventionne||0], backgroundColor: ['#1e88e5','#e53935','#43a047'], borderWidth: 2, borderColor:'#fff' }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position:'bottom' } } }
    });
});
JS;
include __DIR__ . '/layout_end.php';
