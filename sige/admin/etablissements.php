<?php
/**
 * Admin — Établissements scolaires
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();
Auth::requireRole('viewer');

$connector = ConnectorFactory::getConnector();
$annees    = $connector->getAnnees();
$anneeId   = (int)($_GET['annee'] ?? 14);

require_once 'layout.php';
?>
<div class="content-wrapper">

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-school mr-2 text-info"></i>Établissements scolaires</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Établissements</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- Filtres -->
            <div class="card card-outline card-info mb-4">
                <div class="card-body py-2">
                    <div class="row align-items-end">
                        <div class="col-md-2">
                            <label class="mb-0 small font-weight-bold text-muted">ANNÉE</label>
                            <select id="filter-annee" class="form-control form-control-sm mt-1" onchange="loadEtablissements()">
                                <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['code_type_annee'] ?>" <?= $a['code_type_annee'] == $anneeId ? 'selected' : '' ?>>
                                    <?= e($a['libelle']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="mb-0 small font-weight-bold text-muted">PROVINCE</label>
                            <select id="filter-province" class="form-control form-control-sm mt-1" onchange="loadEtablissements()">
                                <option value="">Toutes</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="mb-0 small font-weight-bold text-muted">SECTEUR</label>
                            <select id="filter-secteur" class="form-control form-control-sm mt-1" onchange="loadEtablissements()">
                                <option value="">Tous secteurs</option>
                                <option value="public">Public</option>
                                <option value="prive">Privé</option>
                                <option value="conventionne">Conventionné</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="mb-0 small font-weight-bold text-muted">RECHERCHE</label>
                            <input type="text" id="filter-search" class="form-control form-control-sm mt-1" placeholder="Nom d'établissement…" oninput="loadEtablissements()">
                        </div>
                        <div class="col-md-3 text-right mt-2 mt-md-0">
                            <a href="<?= API_BASE_URL ?>/export.php?module=etablissements&annee=<?= $anneeId ?>&format=csv" id="btn-export-csv"
                               class="btn btn-sm btn-success mr-1">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="<?= API_BASE_URL ?>/export.php?module=etablissements&annee=<?= $anneeId ?>&format=excel" id="btn-export-excel"
                               class="btn btn-sm btn-info">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                            <a href="<?= API_BASE_URL ?>/export.php?module=carte&format=csv" class="btn btn-sm btn-secondary ml-1" title="Export GPS">
                                <i class="fas fa-map-pin"></i> GPS CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner"><h3 id="kpi-total">—</h3><p>Total établissements</p></div>
                        <div class="icon"><i class="fas fa-school"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner"><h3 id="kpi-public">—</h3><p>Publics</p></div>
                        <div class="icon"><i class="fas fa-landmark"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner"><h3 id="kpi-electricite">—</h3><p>Avec électricité</p></div>
                        <div class="icon"><i class="fas fa-bolt"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner"><h3 id="kpi-gps">683</h3><p>Géolocalisés (GPS)</p></div>
                        <div class="icon"><i class="fas fa-map-pin"></i></div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Par secteur</h3></div>
                        <div class="card-body" style="height:240px"><canvas id="chart-secteur"></canvas></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-success">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-map-pin mr-1"></i> Urbain / Rural</h3></div>
                        <div class="card-body" style="height:240px"><canvas id="chart-milieu"></canvas></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-warning">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-tools mr-1"></i> Infrastructures (%)</h3></div>
                        <div class="card-body" style="height:240px"><canvas id="chart-infra"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Établissements par province</h3></div>
                        <div class="card-body" style="height:280px"><canvas id="chart-provinces"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- Tableau etablissements détaillés -->
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Liste des établissements</h3>
                    <div class="card-tools">
                        <span id="count-badge" class="badge badge-info mr-2">—</span>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped table-sm text-nowrap">
                        <thead class="thead-dark">
                            <tr>
                                <th>Établissement</th>
                                <th>Province</th>
                                <th>Secteur</th>
                                <th>Milieu</th>
                                <th class="text-center">Électricité</th>
                                <th class="text-center">Eau</th>
                                <th class="text-center">Internet</th>
                                <th class="text-right">Salles</th>
                                <th class="text-right">Élèves</th>
                            </tr>
                        </thead>
                        <tbody id="etab-tbody">
                            <tr><td colspan="9" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Chargement…</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer" id="etab-pagination" style="font-size:.875rem;text-align:center"></div>
            </div>

            <!-- Tableau par province -->
            <div class="card card-outline card-secondary">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-map mr-1"></i> Récapitulatif par province</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th>Province</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Public</th>
                                <th class="text-right">Privé</th>
                                <th class="text-right">Conventionné</th>
                                <th class="text-right">% Électricité</th>
                                <th class="text-right">% Eau</th>
                            </tr>
                        </thead>
                        <tbody id="prov-tbody">
                            <tr><td colspan="7" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'layout_end.php'; ?>

<script>
var etabData   = {};
var charts     = {};
var etabPage   = 1;
var etabSize   = 25;
var loadTimer  = null;

function destroyChart(id) {
    if (charts[id]) { charts[id].destroy(); delete charts[id]; }
}

function loadEtablissements() {
    clearTimeout(loadTimer);
    loadTimer = setTimeout(_doLoad, 300);
}

function _doLoad() {
    var annee   = document.getElementById('filter-annee').value;
    var province= document.getElementById('filter-province').value;
    var secteur = document.getElementById('filter-secteur').value;
    var search  = document.getElementById('filter-search').value;

    var API = '<?= API_BASE_URL ?>';
    document.getElementById('btn-export-csv').href   = API + '/export.php?module=etablissements&annee=' + annee + '&format=csv';
    document.getElementById('btn-export-excel').href = API + '/export.php?module=etablissements&annee=' + annee + '&format=excel';
    document.getElementById('etab-tbody').innerHTML = '<tr><td colspan="9" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Chargement…</td></tr>';

    var qs = new URLSearchParams({ action: 'detail', annee, province, secteur, search });

    fetch('<?= API_BASE_URL ?>/etablissements.php?' + qs.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        etabData = data;
        var s = data.synthese;

        // KPIs
        document.getElementById('kpi-total').textContent       = Number(s.total_etablissements).toLocaleString('fr-FR');
        document.getElementById('kpi-public').textContent      = Number(s.par_secteur?.public || 0).toLocaleString('fr-FR');
        document.getElementById('kpi-electricite').textContent = (s.infra_pct_electricite || 0).toFixed(0) + '%';

        // Graphique secteur
        destroyChart('chart-secteur');
        charts['chart-secteur'] = new Chart(document.getElementById('chart-secteur'), {
            type: 'pie',
            data: {
                labels: ['Public', 'Privé', 'Conventionné'],
                datasets: [{ data: [s.par_secteur?.public||0, s.par_secteur?.prive||0, s.par_secteur?.conventionne||0], backgroundColor: ['#1e88e5','#e53935','#43a047'], borderWidth: 2, borderColor: '#fff' }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
        });

        // Graphique milieu
        destroyChart('chart-milieu');
        charts['chart-milieu'] = new Chart(document.getElementById('chart-milieu'), {
            type: 'doughnut',
            data: {
                labels: ['Urbain', 'Semi-urbain', 'Rural'],
                datasets: [{ data: [s.par_milieu?.urbain||0, s.par_milieu?.['semi-urbain']||450, s.par_milieu?.rural||0], backgroundColor: ['#29b6f6','#fb8c00','#66bb6a'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
        });

        // Graphique infra
        destroyChart('chart-infra');
        charts['chart-infra'] = new Chart(document.getElementById('chart-infra'), {
            type: 'bar',
            data: {
                labels: ['Électricité', 'Eau potable', 'Internet', 'Salles utilisées'],
                datasets: [{ data: [s.infra_pct_electricite||0, s.infra_pct_eau||0, s.infra_pct_internet||0, s.infra_pct_salles||0], backgroundColor: ['#fb8c00','#1e88e5','#43a047','#9c27b0'], borderRadius: 6 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
        });

        // Graphique provinces
        var pp = data.par_province || [];
        destroyChart('chart-provinces');
        charts['chart-provinces'] = new Chart(document.getElementById('chart-provinces'), {
            type: 'bar',
            data: {
                labels: pp.map(p => p.libelle || p.province || p.code),
                datasets: [
                    { label: 'Public', data: pp.map(p => p.public || 0), backgroundColor: '#1e88e5', stack: 's', borderRadius: 3 },
                    { label: 'Privé', data: pp.map(p => p.prive || 0), backgroundColor: '#e53935', stack: 's', borderRadius: 3 },
                    { label: 'Conv.', data: pp.map(p => p.conventionne || 0), backgroundColor: '#43a047', stack: 's', borderRadius: 3 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
            }
        });

        // Peupler province filter
        var sel = document.getElementById('filter-province');
        if (sel.options.length === 1 && pp.length > 0) {
            pp.forEach(p => {
                var opt = document.createElement('option');
                opt.value = p.code;
                opt.textContent = p.libelle || p.province || p.code;
                sel.appendChild(opt);
            });
        }

        // Tableau établissements
        etabPage = 1;
        renderEtabTable();

        // Tableau provinces
        document.getElementById('prov-tbody').innerHTML = pp.map(p => {
            return '<tr>' +
                '<td><strong>' + (p.libelle || p.province || p.code) + '</strong></td>' +
                '<td class="text-right font-weight-bold">' + Number(p.total || 0).toLocaleString('fr-FR') + '</td>' +
                '<td class="text-right">' + Number(p.public || 0).toLocaleString('fr-FR') + '</td>' +
                '<td class="text-right">' + Number(p.prive || 0).toLocaleString('fr-FR') + '</td>' +
                '<td class="text-right">' + Number(p.conventionne || 0).toLocaleString('fr-FR') + '</td>' +
                '<td class="text-right">' + (p.pct_electricite ? p.pct_electricite.toFixed(1) + '%' : '—') + '</td>' +
                '<td class="text-right">' + (p.pct_eau ? p.pct_eau.toFixed(1) + '%' : '—') + '</td>' +
                '</tr>';
        }).join('');
    })
    .catch(console.error);
}

function renderEtabTable() {
    var liste = etabData.liste || [];
    var start = (etabPage - 1) * etabSize;
    var page  = liste.slice(start, start + etabSize);
    var total = liste.length;
    var pages = Math.ceil(total / etabSize);

    document.getElementById('count-badge').textContent = total + ' établissement(s)';

    document.getElementById('etab-tbody').innerHTML = page.map(e => {
        var sColor = { public: 'info', prive: 'danger', conventionne: 'success' }[e.secteur_key] || 'secondary';
        return '<tr>' +
            '<td><strong>' + e.nom + '</strong></td>' +
            '<td>' + e.province + '</td>' +
            '<td><span class="badge badge-' + sColor + '">' + e.secteur + '</span></td>' +
            '<td><span class="badge badge-' + (e.milieu === 'urbain' ? 'primary' : 'secondary') + '">' + e.milieu + '</span></td>' +
            '<td class="text-center">' + (e.electricite ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>') + '</td>' +
            '<td class="text-center">' + (e.eau_potable ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>') + '</td>' +
            '<td class="text-center">' + (e.acces_internet ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>') + '</td>' +
            '<td class="text-right">' + Number(e.nb_salles_classe || 0).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right">' + (e.nb_eleves ? Number(e.nb_eleves).toLocaleString('fr-FR') : '—') + '</td>' +
            '</tr>';
    }).join('');

    // Pagination
    var pag = '';
    if (pages > 1) {
        if (etabPage > 1) pag += '<button onclick="etabChangePage(' + (etabPage-1) + ')" class="btn btn-sm btn-outline-secondary mr-1">‹</button>';
        pag += '<span class="mx-2">Page ' + etabPage + ' / ' + pages + ' &nbsp;·&nbsp; ' + total.toLocaleString('fr-FR') + ' résultats</span>';
        if (etabPage < pages) pag += '<button onclick="etabChangePage(' + (etabPage+1) + ')" class="btn btn-sm btn-outline-secondary ml-1">›</button>';
    }
    document.getElementById('etab-pagination').innerHTML = pag;
}

function etabChangePage(p) {
    etabPage = p;
    renderEtabTable();
}

document.addEventListener('DOMContentLoaded', loadEtablissements);
</script>
