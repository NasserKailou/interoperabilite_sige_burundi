<?php
/**
 * Admin — Données Élèves
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();
Auth::requireRole('viewer');

$connector = ConnectorFactory::getConnector();
$annees    = $connector->getAnnees();
$anneeId   = (int)($_GET['annee'] ?? 14);
$anneeLib  = '2028/2029';
foreach ($annees as $a) {
    if ($a['code_type_annee'] == $anneeId) { $anneeLib = $a['libelle']; break; }
}

require_once 'layout.php';
?>
<!-- Content Wrapper -->
<div class="content-wrapper">

    <!-- Page Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-users mr-2 text-primary"></i>Données Élèves</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Élèves</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- Filtres -->
            <div class="card card-outline card-primary mb-4">
                <div class="card-body py-2">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="mb-0 small font-weight-bold text-muted">ANNÉE SCOLAIRE</label>
                            <select id="filter-annee" class="form-control form-control-sm mt-1" onchange="loadEleves()">
                                <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['code_type_annee'] ?>" <?= $a['code_type_annee'] == $anneeId ? 'selected' : '' ?>>
                                    <?= e($a['libelle']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="mb-0 small font-weight-bold text-muted">PROVINCE</label>
                            <select id="filter-province" class="form-control form-control-sm mt-1" onchange="loadEleves()">
                                <option value="">Toutes les provinces</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-2 mt-md-0">
                            <label class="mb-0 small font-weight-bold text-muted">GENRE</label>
                            <select id="filter-genre" class="form-control form-control-sm mt-1" onchange="filterTable()">
                                <option value="">Tous</option>
                                <option value="filles">Filles &gt; 50%</option>
                                <option value="garcons">Garçons &gt; 50%</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-2 mt-md-0 text-right">
                            <a href="<?= API_BASE_URL ?>/export.php?module=eleves&annee=<?= $anneeId ?>&format=csv" id="btn-export-csv"
                               class="btn btn-sm btn-success mr-1">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="<?= API_BASE_URL ?>/export.php?module=eleves&annee=<?= $anneeId ?>&format=excel" id="btn-export-excel"
                               class="btn btn-sm btn-info">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="row" id="eleves-kpis">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner"><h3 id="kpi-total">—</h3><p>Total élèves</p></div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner"><h3 id="kpi-filles">—</h3><p>% Filles</p></div>
                        <div class="icon"><i class="fas fa-female"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner"><h3 id="kpi-variation">—</h3><p>Variation annuelle</p></div>
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner"><h3 id="kpi-provinces">18</h3><p>Provinces couvertes</p></div>
                        <div class="icon"><i class="fas fa-map"></i></div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Évolution des effectifs</h3>
                        </div>
                        <div class="card-body" style="height:260px">
                            <canvas id="chart-evolution"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-venus-mars mr-1"></i> Répartition genre</h3>
                        </div>
                        <div class="card-body" style="height:260px">
                            <canvas id="chart-genre"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Effectifs par province</h3>
                        </div>
                        <div class="card-body" style="height:280px">
                            <canvas id="chart-provinces"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-table mr-1"></i> Détail par province</h3>
                    <div class="card-tools">
                        <input type="text" id="table-search" class="form-control form-control-sm" placeholder="Filtrer…" oninput="filterTable()" style="width:180px;display:inline-block">
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped table-sm text-nowrap">
                        <thead class="thead-dark">
                            <tr>
                                <th>Province</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Garçons</th>
                                <th class="text-right">Filles</th>
                                <th class="text-right">% Filles</th>
                                <th class="text-right">Variation</th>
                                <th class="text-right">Tx Scolarisation</th>
                                <th>Parité</th>
                            </tr>
                        </thead>
                        <tbody id="eleves-tbody">
                            <tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Chargement…</td></tr>
                        </tbody>
                        <tfoot id="eleves-tfoot"></tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'layout_end.php'; ?>

<script>
var elevesData = [];
var charts = {};

function destroyChart(id) {
    if (charts[id]) { charts[id].destroy(); delete charts[id]; }
}

function loadEleves() {
    var annee = document.getElementById('filter-annee').value;
    
    // Mettre à jour liens export
    var API = '<?= API_BASE_URL ?>';
    document.getElementById('btn-export-csv').href   = API + '/export.php?module=eleves&annee=' + annee + '&format=csv';
    document.getElementById('btn-export-excel').href = API + '/export.php?module=eleves&annee=' + annee + '&format=excel';

    document.getElementById('eleves-tbody').innerHTML = '<tr><td colspan="8" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Chargement…</td></tr>';

    fetch('<?= API_BASE_URL ?>/eleves.php?action=detail&annee=' + annee, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        elevesData = data;

        // KPIs
        var s = data.synthese;
        document.getElementById('kpi-total').textContent     = Number(s.total).toLocaleString('fr-FR');
        document.getElementById('kpi-filles').textContent    = s.pct_filles.toFixed(1) + '%';
        document.getElementById('kpi-variation').textContent = (s.variation >= 0 ? '+' : '') + s.variation.toFixed(1) + '%';

        // Graphique évolution
        destroyChart('chart-evolution');
        var evo = data.evolution;
        charts['chart-evolution'] = new Chart(document.getElementById('chart-evolution'), {
            type: 'line',
            data: {
                labels: evo.map(e => e.annee),
                datasets: [
                    { label: 'Total élèves', data: evo.map(e => e.total), borderColor: '#1e88e5', backgroundColor: 'rgba(30,136,229,.1)', fill: true, tension: .4, borderWidth: 2 },
                    { label: 'Filles', data: evo.map(e => e.filles), borderColor: '#e91e63', backgroundColor: 'rgba(233,30,99,.05)', fill: false, tension: .4, borderWidth: 2, borderDash: [4,4] }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: false } } }
        });

        // Graphique genre
        destroyChart('chart-genre');
        charts['chart-genre'] = new Chart(document.getElementById('chart-genre'), {
            type: 'doughnut',
            data: {
                labels: ['Garçons', 'Filles'],
                datasets: [{ data: [100 - s.pct_filles, s.pct_filles], backgroundColor: ['#1e88e5', '#e91e63'], borderWidth: 2, borderColor: '#fff' }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom' } } }
        });

        // Graphique provinces
        var pp = data.par_province.slice(0, 18);
        destroyChart('chart-provinces');
        charts['chart-provinces'] = new Chart(document.getElementById('chart-provinces'), {
            type: 'bar',
            data: {
                labels: pp.map(p => p.province),
                datasets: [
                    { label: 'Garçons', data: pp.map(p => p.garcons), backgroundColor: '#1e88e5', stack: 'genre', borderRadius: 4 },
                    { label: 'Filles',  data: pp.map(p => p.filles),  backgroundColor: '#e91e63', stack: 'genre', borderRadius: 4 }
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
        if (sel.options.length === 1) {
            data.par_province.forEach(p => {
                var opt = document.createElement('option');
                opt.value = p.code;
                opt.textContent = p.province;
                sel.appendChild(opt);
            });
        }

        renderTable(data.par_province);
    })
    .catch(err => {
        console.error(err);
        document.getElementById('eleves-tbody').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur de chargement</td></tr>';
    });
}

function renderTable(rows) {
    var search = document.getElementById('table-search').value.toLowerCase();
    var genre  = document.getElementById('filter-genre').value;

    var filtered = rows.filter(r => {
        if (search && !r.province.toLowerCase().includes(search)) return false;
        if (genre === 'filles'  && r.pct_filles <= 50) return false;
        if (genre === 'garcons' && r.pct_filles >= 50) return false;
        return true;
    });

    var total_t = 0, total_g = 0, total_f = 0;

    document.getElementById('eleves-tbody').innerHTML = filtered.map(r => {
        total_t += r.total; total_g += r.garcons; total_f += r.filles;
        var varClass = (r.variation || 0) >= 0 ? 'text-success' : 'text-danger';
        var varIcon  = (r.variation || 0) >= 0 ? '▲' : '▼';
        var parite   = r.pct_filles >= 48 && r.pct_filles <= 52 ? '<span class="badge badge-success">Équilibre</span>' : (r.pct_filles > 52 ? '<span class="badge badge-info">Avantage filles</span>' : '<span class="badge badge-warning">Avantage garçons</span>');
        return '<tr>' +
            '<td><strong>' + r.province + '</strong></td>' +
            '<td class="text-right font-weight-bold">' + Number(r.total).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right">' + Number(r.garcons).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right">' + Number(r.filles).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right"><strong>' + r.pct_filles.toFixed(1) + '%</strong></td>' +
            '<td class="text-right ' + varClass + '">' + varIcon + ' ' + ((r.variation||0) >= 0 ? '+' : '') + (r.variation||0).toFixed(1) + '%</td>' +
            '<td class="text-right">' + (r.taux_scolarisation ? r.taux_scolarisation.toFixed(1) + '%' : '—') + '</td>' +
            '<td>' + parite + '</td>' +
            '</tr>';
    }).join('');

    // Totaux
    document.getElementById('eleves-tfoot').innerHTML = '<tr class="bg-light font-weight-bold">' +
        '<td>TOTAL NATIONAL</td>' +
        '<td class="text-right">' + Number(total_t).toLocaleString('fr-FR') + '</td>' +
        '<td class="text-right">' + Number(total_g).toLocaleString('fr-FR') + '</td>' +
        '<td class="text-right">' + Number(total_f).toLocaleString('fr-FR') + '</td>' +
        '<td class="text-right">' + (total_t > 0 ? (total_f/total_t*100).toFixed(1) + '%' : '—') + '</td>' +
        '<td colspan="3"></td></tr>';
}

function filterTable() {
    if (elevesData.par_province) renderTable(elevesData.par_province);
}

document.addEventListener('DOMContentLoaded', loadEleves);
</script>
