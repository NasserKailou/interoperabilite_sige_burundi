<?php
/**
 * Admin — Examens & Concours
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();
Auth::requireRole('viewer');

$connector = ConnectorFactory::getConnector();
$annees    = $connector->getAnnees();
$anneeId   = (int)($_GET['annee'] ?? 14);

$pageTitle  = 'Examens & Concours';
$pageIcon   = 'fas fa-file-alt';
$activePage = 'examens';
require_once 'layout.php';
?>

            <!-- Filtres -->
            <div class="card card-outline card-danger mb-4">
                <div class="card-body py-2">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="mb-0 small font-weight-bold text-muted">ANNÉE SCOLAIRE</label>
                            <select id="filter-annee" class="form-control form-control-sm mt-1" onchange="loadExamens()">
                                <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['code_type_annee'] ?>" <?= $a['code_type_annee'] == $anneeId ? 'selected' : '' ?>>
                                    <?= e($a['libelle']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="mb-0 small font-weight-bold text-muted">TYPE EXAMEN</label>
                            <select id="filter-type" class="form-control form-control-sm mt-1" onchange="showTab(this.value)">
                                <option value="cn8">CN8 — Certificat Primaire</option>
                                <option value="etat">Examen d'État (Bac)</option>
                                <option value="cepf">CEPF — Fin Fondamental</option>
                            </select>
                        </div>
                        <div class="col-md-3"></div>
                        <div class="col-md-3 text-right mt-2 mt-md-0">
                            <a href="<?= API_BASE_URL ?>/export.php?module=examens&annee=<?= $anneeId ?>&format=csv" id="btn-export-csv"
                               class="btn btn-sm btn-success mr-1">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a href="<?= API_BASE_URL ?>/export.php?module=examens&annee=<?= $anneeId ?>&format=excel" id="btn-export-excel"
                               class="btn btn-sm btn-info">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs dynamiques -->
            <div class="row" id="examens-kpis">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner"><h3 id="kpi-candidats">—</h3><p id="kpi-label-candidats">Candidats CN8</p></div>
                        <div class="icon"><i class="fas fa-user-graduate"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner"><h3 id="kpi-admis">—</h3><p id="kpi-label-admis">Admis CN8</p></div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner"><h3 id="kpi-taux">—</h3><p>Taux de réussite</p></div>
                        <div class="icon"><i class="fas fa-percent"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner"><h3 id="kpi-sessions">—</h3><p>Sessions</p></div>
                        <div class="icon"><i class="fas fa-calendar-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-outline card-danger">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Historique des taux de réussite</h3></div>
                        <div class="card-body" style="height:260px"><canvas id="chart-historique"></canvas></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-warning">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Résultats CN8</h3></div>
                        <div class="card-body" style="height:260px"><canvas id="chart-resultats"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Taux de réussite CN8 par province</h3></div>
                        <div class="card-body" style="height:280px"><canvas id="chart-cn8-province"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- Sessions -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Sessions d'examens — <span id="session-label">CN8</span></h3>
                            <div class="card-tools">
                                <input type="text" id="table-search" class="form-control form-control-sm" placeholder="Filtrer…" oninput="filterTable()" style="width:180px;display:inline-block">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover table-striped table-sm">
                                <thead class="thead-dark">
                                    <tr id="table-header">
                                        <th>Province</th>
                                        <th class="text-right">Candidats</th>
                                        <th class="text-right">Admis</th>
                                        <th class="text-right">Taux (%)</th>
                                        <th>Niveau</th>
                                    </tr>
                                </thead>
                                <tbody id="examens-tbody">
                                    <tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Chargement…</td></tr>
                                </tbody>
                                <tfoot id="examens-tfoot"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

<?php require_once 'layout_end.php'; ?>

<script>
var examensData = {};
var charts = {};
var currentType = 'cn8';

function destroyChart(id) {
    if (charts[id]) { charts[id].destroy(); delete charts[id]; }
}

function showTab(type) {
    currentType = type;
    document.getElementById('session-label').textContent = type.toUpperCase();
    if (examensData.sessions) renderTable();
}

function loadExamens() {
    var annee = document.getElementById('filter-annee').value;
    var API = '<?= API_BASE_URL ?>';
    document.getElementById('btn-export-csv').href   = API + '/export.php?module=examens&annee=' + annee + '&format=csv';
    document.getElementById('btn-export-excel').href = API + '/export.php?module=examens&annee=' + annee + '&format=excel';
    document.getElementById('examens-tbody').innerHTML = '<tr><td colspan="5" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Chargement…</td></tr>';

    fetch('<?= API_BASE_URL ?>/examens.php?action=detail&annee=' + annee, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        examensData = data;
        var s = data.synthese;

        // Trouver session CN8 principale
        var cn8 = data.sessions.find(s => s.code === 'CN8') || data.sessions[0] || {};

        // KPIs CN8
        document.getElementById('kpi-candidats').textContent = Number(cn8.candidats || 0).toLocaleString('fr-FR');
        document.getElementById('kpi-admis').textContent     = Number(cn8.admis || 0).toLocaleString('fr-FR');
        document.getElementById('kpi-taux').textContent      = (cn8.taux_reussite || 0).toFixed(1) + '%';
        document.getElementById('kpi-sessions').textContent  = data.sessions.length;

        // Historique
        destroyChart('chart-historique');
        var hist = data.historique || [];
        var sessionTypes = [...new Set(hist.map(h => h.code_session))];
        var colors = { 'CN8': '#e53935', 'ETAT': '#1e88e5', 'CEPF': '#43a047' };
        charts['chart-historique'] = new Chart(document.getElementById('chart-historique'), {
            type: 'line',
            data: {
                labels: [...new Set(hist.map(h => h.annee))],
                datasets: sessionTypes.map(type => ({
                    label: type,
                    data: hist.filter(h => h.code_session === type).map(h => h.taux_reussite),
                    borderColor: colors[type] || '#607d8b',
                    backgroundColor: (colors[type] || '#607d8b') + '20',
                    tension: .4, borderWidth: 2, fill: false
                }))
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { min: 0, max: 100 } } }
        });

        // Résultats CN8 doughnut
        destroyChart('chart-resultats');
        charts['chart-resultats'] = new Chart(document.getElementById('chart-resultats'), {
            type: 'doughnut',
            data: {
                labels: ['Admis', 'Non-admis'],
                datasets: [{ data: [cn8.admis || 0, (cn8.candidats || 0) - (cn8.admis || 0)], backgroundColor: ['#43a047', '#e53935'], borderWidth: 2, borderColor: '#fff' }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom' } } }
        });

        // Par province CN8
        var pp = data.par_province_cn8 || [];
        destroyChart('chart-cn8-province');
        charts['chart-cn8-province'] = new Chart(document.getElementById('chart-cn8-province'), {
            type: 'bar',
            data: {
                labels: pp.map(p => p.province),
                datasets: [{ label: 'Taux réussite CN8 (%)', data: pp.map(p => p.taux), backgroundColor: pp.map(p => p.taux >= 75 ? '#43a047' : p.taux >= 60 ? '#fb8c00' : '#e53935'), borderRadius: 5 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });

        renderTable();
    })
    .catch(console.error);
}

function renderTable() {
    var search = document.getElementById('table-search').value.toLowerCase();
    var type   = document.getElementById('filter-type').value;
    
    var rows = [];
    var headers = '';
    
    if (type === 'cn8') {
        rows = (examensData.par_province_cn8 || []).filter(r => !search || r.province.toLowerCase().includes(search));
        headers = '<th>Province</th><th class="text-right">Candidats</th><th class="text-right">Admis</th><th class="text-right">Taux (%)</th><th>Niveau</th>';
        
        var tot_c = 0, tot_a = 0;
        document.getElementById('examens-tbody').innerHTML = rows.map(r => {
            tot_c += r.candidats; tot_a += r.admis;
            var cls = r.taux >= 75 ? 'text-success' : r.taux >= 60 ? 'text-warning' : 'text-danger';
            var badge = r.taux >= 75 ? 'badge-success' : r.taux >= 60 ? 'badge-warning' : 'badge-danger';
            return '<tr>' +
                '<td><strong>' + r.province + '</strong></td>' +
                '<td class="text-right">' + Number(r.candidats).toLocaleString('fr-FR') + '</td>' +
                '<td class="text-right">' + Number(r.admis).toLocaleString('fr-FR') + '</td>' +
                '<td class="text-right ' + cls + '"><span class="badge ' + badge + '">' + r.taux.toFixed(1) + '%</span></td>' +
                '<td><span class="badge badge-secondary">CN8</span></td>' +
                '</tr>';
        }).join('');
        document.getElementById('examens-tfoot').innerHTML = '<tr class="bg-light font-weight-bold"><td>NATIONAL</td>' +
            '<td class="text-right">' + Number(tot_c).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right">' + Number(tot_a).toLocaleString('fr-FR') + '</td>' +
            '<td class="text-right">' + (tot_c > 0 ? (tot_a/tot_c*100).toFixed(1) + '%' : '—') + '</td>' +
            '<td></td></tr>';
    } else {
        // Sessions générales
        var sessions = (examensData.sessions || []).filter(s => {
            if (type === 'etat' && s.code !== 'ETAT') return false;
            if (type === 'cepf' && s.code !== 'CEPF') return false;
            return !search || s.libelle.toLowerCase().includes(search);
        });
        document.getElementById('examens-tbody').innerHTML = sessions.map(s => {
            var cls = s.taux_reussite >= 75 ? 'text-success' : s.taux_reussite >= 60 ? 'text-warning' : 'text-danger';
            return '<tr>' +
                '<td><strong>' + s.libelle + '</strong></td>' +
                '<td class="text-right">' + Number(s.candidats).toLocaleString('fr-FR') + '</td>' +
                '<td class="text-right">' + Number(s.admis).toLocaleString('fr-FR') + '</td>' +
                '<td class="text-right ' + cls + '"><strong>' + s.taux_reussite.toFixed(1) + '%</strong></td>' +
                '<td><span class="badge badge-info">' + s.code + '</span></td>' +
                '</tr>';
        }).join('');
        document.getElementById('examens-tfoot').innerHTML = '';
    }
}

function filterTable() { renderTable(); }

document.addEventListener('DOMContentLoaded', loadExamens);
</script>
