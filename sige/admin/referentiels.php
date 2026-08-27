<?php
/**
 * SIGE Burundi — Admin — Référentiels communs
 */
$pageTitle  = 'Référentiels communs';
$pageIcon   = 'fas fa-book';
$activePage = 'referentiels';
include __DIR__ . '/layout.php';

$connector = ConnectorFactory::getConnector();
$refs      = $connector->getReferentiels();
?>

<ul class="nav nav-tabs mb-4" id="refTabs">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-annees">
        <i class="fas fa-calendar"></i> Années (<?= count($refs['annees'] ?? []) ?>)
    </a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-provinces">
        <i class="fas fa-map"></i> Provinces (<?= count($refs['provinces'] ?? []) ?>)
    </a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-communes">
        <i class="fas fa-map-pin"></i> Communes (<?= count($refs['communes'] ?? []) ?>)
    </a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-secteurs">
        <i class="fas fa-tag"></i> Secteurs & niveaux
    </a></li>
</ul>

<div class="tab-content">
    <!-- Années -->
    <div class="tab-pane active" id="tab-annees">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i> Années de recensement</h3>
                <div class="card-tools">
                    <span class="badge badge-sige-mock">Aligné sur StatEduc</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr><th>Code type année</th><th>Libellé</th><th>Ordre</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_reverse($refs['annees'] ?? []) as $a): ?>
                    <tr>
                        <td><code><?= (int)$a['code_type_annee'] ?></code></td>
                        <td><strong><?= e($a['libelle']) ?></strong></td>
                        <td><?= (int)$a['ordre'] ?></td>
                        <td>
                            <?php if ($a['code_type_annee'] == 14): ?>
                            <span class="badge badge-success">Référence actuelle</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Historique</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Provinces -->
    <div class="tab-pane" id="tab-provinces">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map mr-2"></i> 18 Provinces du Burundi</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>ID</th><th>Libellé</th><th>Code</th><th>Communes rattachées</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($refs['provinces'] ?? [] as $p):
                            $nbCommunes = count(array_filter($refs['communes'] ?? [], fn($c) => $c['id_province'] == $p['id_province']));
                        ?>
                        <tr>
                            <td><code><?= (int)$p['id_province'] ?></code></td>
                            <td><strong><?= e($p['libelle']) ?></strong></td>
                            <td><span class="badge badge-info"><?= e($p['code'] ?? '') ?></span></td>
                            <td><?= $nbCommunes ?> commune(s) dans les référentiels</td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Communes -->
    <div class="tab-pane" id="tab-communes">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-pin mr-2"></i> Communes (extrait)</h3>
            </div>
            <div class="card-body p-0">
                <?php
                $provincesMap = [];
                foreach ($refs['provinces'] ?? [] as $p) $provincesMap[$p['id_province']] = $p['libelle'];
                ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>ID</th><th>Libellé</th><th>Province</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($refs['communes'] ?? [] as $c): ?>
                        <tr>
                            <td><code><?= (int)$c['id_commune'] ?></code></td>
                            <td><?= e($c['libelle']) ?></td>
                            <td><?= e($provincesMap[$c['id_province']] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Secteurs & niveaux -->
    <div class="tab-pane" id="tab-secteurs">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Secteurs</h3></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead class="thead-light"><tr><th>ID</th><th>Libellé</th></tr></thead>
                            <tbody>
                            <?php foreach ($refs['secteurs'] ?? [] as $s): ?>
                            <tr>
                                <td><code><?= (int)$s['id_secteur'] ?></code></td>
                                <td><span class="badge" style="background:<?= e($s['couleur'] ?? '#999') ?>;color:white"><?= e($s['libelle']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Niveaux d'enseignement</h3></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead class="thead-light"><tr><th>ID</th><th>Libellé</th><th>Abr.</th></tr></thead>
                            <tbody>
                            <?php foreach ($refs['niveaux'] ?? [] as $n): ?>
                            <tr>
                                <td><code><?= (int)$n['id_niveau'] ?></code></td>
                                <td><?= e($n['libelle']) ?></td>
                                <td><span class="badge badge-secondary"><?= e($n['abrev'] ?? '') ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Types d'enseignement</h3></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead class="thead-light"><tr><th>ID</th><th>Libellé</th></tr></thead>
                            <tbody>
                            <?php foreach ($refs['types_enseignement'] ?? [] as $t): ?>
                            <tr>
                                <td><code><?= (int)$t['id_systeme'] ?></code></td>
                                <td><?= e($t['libelle']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
