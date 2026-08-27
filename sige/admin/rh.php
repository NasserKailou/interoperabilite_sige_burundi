<?php
/**
 * Admin — Ressources Humaines
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
                    <h1 class="m-0"><i class="fas fa-chalkboard-teacher mr-2 text-success"></i>Ressources Humaines</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Ressources Humaines</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- Filtres -->
            <div class="card card-outline card-success mb-4">
                <div class="card-body py-2">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="mb-0 small font-weight-bold text-muted">ANNÉE SCOLAIRE</label>
                            <select id="filter-annee" class="form-control form-control-sm mt-1" onchange="loadRH()">
                                <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['code_type_annee'] ?>" <?= $a['code_type_annee'] == $anneeId ? 'selected' : '' ?>>
                                    <?= e($a['libelle']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="mb-0 small font-weight-bold text-muted">PROVINCE</label>
                            <select id="filter-province" class="form-control form-control-sm mt-1" onchange="filterTable()">
                                <option value="">Toutes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="mb-0 small font-weight-bold text-muted">CATÉGORIE</label>
                            <select id="filter-categorie" class="form-control form-control-sm mt-1" onchange="loadRH()">
                                <option value="">Toutes catégories</option>
                                <option value="A0">A0 (Licence)</option>
                                <option value="A1">A1 (Bac+3)</option>
                                <option value="A2">A2 (Bac)</option>
                                <option value="contractuels">Contractuels</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-right mt-2 mt-md-0">
                            <a href="<?= API_BASE_URL ?>/export.php?module=rh&annee=<?= $anneeId ?>&format=csv" id="btn-export-csv"
                               class="btn btn-sm btn-success mr-1">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="<?= API_BASE_URL ?>/export.php?module=rh&annee=<?= $anneeId ?>&format=excel" id="btn-export-excel"
                               class="btn btn-sm btn-info">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner"><h3 id="kpi-total">—</h3><p>Total personnels</p></div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner"><h3 id="kpi-enseignants">—</h3><p>Enseignants</p></div>
                        <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner"><h3 id="kpi-ratio">—</h3><p>Ratio élèves/ens.</p></div>
                        <div class="icon"><i class="fas fa-balance-scale"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner"><h3 id="kpi-femmes">—</h3><p>% Femmes</p></div>
                        <div class="icon"><i class="fas fa-female"></i></div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-outline card-success">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Évolution du personnel</h3></div>
                        <div class="card-body" style="height:260px"><canvas id="chart-evolution"></canvas></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Par catégorie</h3></div>
                        <div class="card-body" style="height:260px"><canvas id="chart-categorie"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-warning">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Ratio élèves/enseignant par province</h3></div>
                        <div class="card-body" style="height:280px"><canvas id="chart-ratio"></canvas></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline card-danger">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-venus-mars mr-1"></i> % Femmes par province</h3></div>
                        <div class="card-body" style="height:280px"><canvas id="chart-femmes"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- Tableau -->
            <div class="card card-outline card-success">
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
                                <th class="text-right">Enseignants</th>
                                <th class="text-right">Admin.</th>
                                <th class="text-right">Ratio Él./Ens.</th>
                                <th class="text-right">% Femmes</th>
                                <th class="text-right">A0</th>
                                <th class="text-right">A1</th>
                                <th class="text-right">A2</th>
                            </tr>
                        </thead>
                        <tbody id="rh-tbody">
                            <tr><td colspan="9" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Chargement…</td></tr>
                        </tbody>
                        <tfoot id="rh-tfoot"></tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'layout_end.php'; ?>

<script>
var rhData = {};
var charts = {};

function destroyChart(id) {
    if (charts[id]) { charts[id].destroy(); delete charts[id]; }
}

function loadRH() {
    var annee = document.getElementById('filter-annee').value;
    var API = '<?= API_BASE_URL ?>';
    document.getElementById('btn-export-csv').href   = API + '/export.php?module=rh&annee=' + annee + '&format=csv';
    document.getElementById('btn-export-excel').href = API + '/export.php?module=rh&annee=' + annee + '&format=excel';
    document.getElementById('rh-tbody').innerHTML = '<tr><td colspan="9" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Chargement…</td></tr>';

    fetch('<?= API_BASE_URL ?>/rh.php?action=detail&annee=' + annee, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        rhData = data;
        var s = data.synthese;

        // KPIs
        document.getElementById('kpi-total').textContent      = Number(s.total).toLocaleString('fr-FR');
        document.getElementById('kpi-enseignants').textContent = Number(s.enseignants).toLocaleString('fr-FR');
        document.getElementById('kpi-ratio').textContent      = (s.ratio_eleves_ens || 0).toFixed(1);
        document.getElementById('kpi-femmes').textContent     = (s.pct_femmes || 0).toFixed(1) + '%';

        // Évolution
        destroyChart('chart-evolution');
        var evo = data.evolution || [];
        charts['chart-evolution'] = new Chart(document.getElementById('chart-evolution'), {
            type: 'line',
            data: {
                labels: evo.map(e => e.annee),
                datasets: [
                    { label: 'Total personnels', data: evo.map(e => e.total), borderColor: '#43a047', backgroundColor: 'rgba(67,160,71,.1)', fill: true, tension: .4, borderWidth: 2 },
                    { label: 'Enseignants', data: evo.map(e => e.enseignants), borderColor: '#1e88e5', fill: false, tension: .4, borderWidth: 2 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: false } } }
        });

        // Catégories
        destroyChart('chart-categorie');
        var cats = data.categories || {};
        charts['chart-categorie'] = new Chart(document.getElementById('chart-categorie'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(cats).map(k => 'Niveau ' + k),
                datasets: [{ data: Object.values(cats), backgroundColor: ['#1e88e5','#43a047','#fb8c00','#e53935','#9c27b0'], borderWidth: 2, borderColor: '#fff' }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
        });

        // Ratio par province
        var pp = data.par_province || [];
        destroyChart('chart-ratio');
        charts['chart-ratio'] = new Chart(document.getElementById('chart-ratio'), {
            type: 'bar',
            data: {
                labels: pp.map(p => p.province),
                datasets: [{ label: 'Ratio él./ens.', data: pp.map(p => p.ratio_eleves_ens || 0), backgroundColor: '#fb8c00', borderRadius: 4 }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });

        // % Femmes par province
        destroyChart('chart-femmes');
        charts['chart-femmes'] = new Chart(document.getElementById('chart-femmes'), {
            type: 'bar',
            data: {
                labels: pp.map(p => p.province),
                datasets: [{ label: '% Femmes', data: pp.map(p => p.pct_femmes || 0), backgroundColor: '#e91e63', borderRadius: 4 }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, max: 100 } }
            }
        });

        // Peupler province filter
        var sel = document.getElementById('filter-province');
        if (sel.options.length === 1) {
            pp.forEach(p => {
                var opt = document.createElement('option');
                opt.value = p.code;
                opt.textContent = p.province;
                sel.appendChild(opt);
            });
        }

        renderTable(pp);
    })
    .catch(console.error);
}

function renderTable(rows) {
    var search = document.getElementById('table-search').value.toLowerCase();
    var prov   = document.getElementById('filter-province').value;

    var filtered = rows.filter(r => {
        if (search && !r.province.toLowerCase().includes(search)) return false;
        if (prov && r.code !== prov) return false;
        return true;
    });

    var tot = 0, ens = 0, adm = 0;
    document.getElementById('rh-tbody').innerHTML = filtered.map(r => {
        tot += r.total; ens += r.enseignants; adm += (r.administratifs || 0);
        var ratioClass = (r.ratio_eleves_ens || 0) > 40 ? 'text-danger' : ((r.ratio_eleves_ens || 0) > 30 ? 'text-warning' : 'text-success');
        return '<tr>' +
            '<td><strong>' + r.province + '</strong></td>' +
            '<td class="text-right font-weight-bold">' + Number(r.total).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right">' + Number(r.enseignants).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right">' + Number(r.administratifs || 0).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right ' + ratioClass + '"><strong>' + (r.ratio_eleves_ens || 0).toFixed(1) + '</strong></td>' +
            '<td class="text-right">' + (r.pct_femmes || 0).toFixed(1) + '%</td>' +
            '<td class="text-right">' + Number(r.a0 || 0).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right">' + Number(r.a1 || 0).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right">' + Number(r.a2 || 0).toLocaleString('fr-FR') + '</td>' +
            '</tr>';
    }).join('');

    document.getElementById('rh-tfoot').innerHTML = '<tr class="bg-light font-weight-bold">' +
        '<td>TOTAL</td>' +
        '<td class="text-right">' + Number(tot).toLocaleString('fr-FR') + '</td>' +
        '<td class="text-right">' + Number(ens).toLocaleString('fr-FR') + '</td>' +
        '<td class="text-right">' + Number(adm).toLocaleString('fr-FR') + '</td>' +
        '<td colspan="5"></td></tr>';
}

function filterTable() {
    if (rhData.par_province) renderTable(rhData.par_province);
}

document.addEventListener('DOMContentLoaded', loadRH);
</script>
