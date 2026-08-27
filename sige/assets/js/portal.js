/**
 * SIGE Burundi — JavaScript du portail public
 * Gestion AJAX, graphiques (Chart.js), interactions Kanban
 */

'use strict';

// ─── Base URL — XAMPP sous-dossier compatible ─────────────────────────────────
// window.SIGE_BASE est injecté par PHP (PUBLIC_BASE_URL) avant ce script.
// En mode PHP built-in (localhost:3000), SIGE_BASE vaut '' → les URL restent relatives.
const BASE = (typeof window !== 'undefined' && window.SIGE_BASE) ? window.SIGE_BASE : '';

// ─── État global ──────────────────────────────────────────────────────────────
const SIGE = {
    annee: 14,          // Année active (code_type_annee)
    charts: {},         // Instances Chart.js stockées pour destroy/redraw
    activeSection: 'kanban',  // Section visible actuellement
};

// ─── Utilitaires ──────────────────────────────────────────────────────────────

/** Formatte un nombre avec séparateur d'espace */
function fmtNum(n, decimals = 0) {
    if (n === null || n === undefined) return '—';
    return Number(n).toLocaleString('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

/** Formatte un pourcentage */
function fmtPct(n, decimals = 1) {
    if (n === null || n === undefined) return '—';
    return fmtNum(n, decimals) + '\u202f%';
}

/** Anime un compteur numérique */
function animateCounter(el, target, duration = 1200, decimals = 0) {
    const start     = 0;
    const startTime = performance.now();
    const update    = (currentTime) => {
        const elapsed  = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease     = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        const value    = start + (target - start) * ease;
        el.textContent = fmtNum(value, decimals);
        if (progress < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}

/** Requête AJAX générique */
function ajax(url, params = {}, method = 'GET') {
    const searchParams = new URLSearchParams({ ...params, annee: SIGE.annee });
    const fullUrl = method === 'GET' ? `${url}?${searchParams}` : url;

    return fetch(fullUrl, {
        method,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    }).then(r => {
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
    });
}

/** Affiche un spinner dans un conteneur */
function showLoading(containerId) {
    const el = document.getElementById(containerId);
    if (el) el.innerHTML = `
        <div class="data-loading">
            <div class="spinner-ring"></div>
            <span>Chargement des données…</span>
        </div>`;
}

/** Détruit un graphique Chart.js existant avant d'en créer un nouveau */
function destroyChart(key) {
    if (SIGE.charts[key]) {
        SIGE.charts[key].destroy();
        delete SIGE.charts[key];
    }
}

// ─── Palette couleurs ─────────────────────────────────────────────────────────
const COLORS = {
    blue:    '#1e88e5',
    blueLight: '#90caf9',
    green:   '#43a047',
    greenLight: '#a5d6a7',
    red:     '#e53935',
    redLight: '#ef9a9a',
    sky:     '#29b6f6',
    orange:  '#fb8c00',
    purple:  '#8e24aa',
    provinces: [
        '#1e88e5','#43a047','#e53935','#fb8c00','#8e24aa',
        '#00acc1','#f4511e','#1e8e3e','#d81b60','#6d4c41',
        '#546e7a','#00897b','#7cb342','#fdd835','#039be5',
        '#3949ab','#8d6e63','#78909c',
    ],
};

// ─── Navigation et sections ───────────────────────────────────────────────────

function showSection(sectionId) {
    document.querySelectorAll('.detail-section').forEach(s => s.classList.remove('active'));
    document.getElementById('kanban-section').style.display = 'block';

    if (sectionId === 'kanban') {
        document.getElementById('kanban-section').style.display = 'block';
        SIGE.activeSection = 'kanban';
        return;
    }

    document.getElementById('kanban-section').style.display = 'none';
    const target = document.getElementById(sectionId + '-section');
    if (target) {
        target.classList.add('active');
        SIGE.activeSection = sectionId;
        // Charger les données de la section
        const loaders = {
            'eleves': loadElevesDetail,
            'rh': loadRHDetail,
            'examens': loadExamensDetail,
            'etablissements': loadEtablissementsDetail,
        };
        if (loaders[sectionId]) loaders[sectionId]();
    }
}

function goBack() {
    showSection('kanban');
    // Mettre à jour le menu
    document.querySelectorAll('.navbar-menu a').forEach(a => {
        a.classList.toggle('active', a.dataset.section === 'kanban' || !a.dataset.section);
    });
}

// ─── Kanban — chargement des résumés ─────────────────────────────────────────

function loadKanbanSummaries() {
    // Carte Élèves
    ajax(BASE + '/api/eleves.php', { action: 'synthese' }).then(data => {
        document.getElementById('k-eleves-total').textContent   = fmtNum(data.total);
        document.getElementById('k-eleves-filles').textContent  = fmtPct(data.pct_filles, 1);
        document.getElementById('k-eleves-garcons').textContent = fmtPct(data.pct_garcons, 1);
        drawSparkline('sparkline-eleves', data.evolution, COLORS.blue);
    }).catch(console.error);

    // Carte RH
    ajax(BASE + '/api/rh.php', { action: 'synthese' }).then(data => {
        document.getElementById('k-rh-total').textContent = fmtNum(data.total_personnel);
        document.getElementById('k-rh-ratio').textContent = data.ratio + ' élèves/enseignant';
        drawSparkline('sparkline-rh', data.evolution, COLORS.green);
    }).catch(console.error);

    // Carte Examens
    ajax(BASE + '/api/examens.php', { action: 'synthese' }).then(data => {
        document.getElementById('k-exam-taux').textContent = fmtPct(data.taux_reussite_cn8);
        document.getElementById('k-exam-admis').textContent = fmtNum(data.admis_cn8) + ' admis';
        drawSparkline('sparkline-examens', data.evolution_taux, COLORS.red);
    }).catch(console.error);

    // Carte Établissements
    ajax(BASE + '/api/etablissements.php', { action: 'synthese' }).then(data => {
        document.getElementById('k-etab-total').textContent  = fmtNum(data.total);
        document.getElementById('k-etab-public').textContent = fmtNum(data.public) + ' publics';
        document.getElementById('k-etab-rural').textContent  = fmtNum(data.rural) + ' ruraux';
        drawSparkline('sparkline-etab', [data.public, data.prive, data.conventionne], COLORS.sky);
    }).catch(console.error);
}

/** Micro-graphique sparkline */
function drawSparkline(canvasId, values, color) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !values || !values.length) return;
    destroyChart(canvasId);
    SIGE.charts[canvasId] = new Chart(canvas, {
        type: 'line',
        data: {
            labels: values.map((_, i) => i),
            datasets: [{
                data: values,
                borderColor: color,
                borderWidth: 2,
                fill: true,
                backgroundColor: color + '15',
                tension: 0.4,
                pointRadius: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                x: { display: false },
                y: { display: false },
            },
            animation: { duration: 800 },
        },
    });
}

// ─── KPI Band ─────────────────────────────────────────────────────────────────

function loadKPIs() {
    ajax(BASE + '/api/kpi.php').then(data => {
        const kpis = [
            { id: 'kpi-etab',    val: data.etablissements, label: 'Établissements',    decimals: 0 },
            { id: 'kpi-eleves',  val: data.eleves,         label: 'Élèves scolarisés', decimals: 0 },
            { id: 'kpi-enseignants', val: data.enseignants, label: 'Enseignants',      decimals: 0 },
            { id: 'kpi-reussite', val: data.taux_reussite, label: 'Taux de réussite', decimals: 1 },
        ];
        kpis.forEach(k => {
            const el = document.getElementById(k.id);
            if (el) animateCounter(el, k.val, 1500, k.decimals);
        });

        // Variations
        if (data.variation_eleves !== undefined) {
            const vEl = document.getElementById('kpi-eleves-var');
            if (vEl) {
                const pct = data.variation_eleves;
                vEl.textContent = (pct >= 0 ? '+' : '') + fmtPct(pct, 1);
                vEl.className = 'kpi-variation ' + (pct > 0 ? 'up' : pct < 0 ? 'down' : 'flat');
            }
        }
    }).catch(console.error);
}

// ─── Section Élèves ──────────────────────────────────────────────────────────

function loadElevesDetail() {
    showLoading('eleves-stats');
    showLoading('eleves-charts');
    showLoading('eleves-table-body');

    ajax(BASE + '/api/eleves.php', { action: 'detail' }).then(data => {
        // Stats cards
        document.getElementById('eleves-stats').innerHTML = `
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="fas fa-users"></i></div>
                <div>
                    <div class="stat-value">${fmtNum(data.synthese.total)}</div>
                    <div class="stat-label">Effectif total</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon green"><i class="fas fa-venus"></i></div>
                <div>
                    <div class="stat-value">${fmtPct(data.synthese.pct_filles)}</div>
                    <div class="stat-label">Part des filles</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="fas fa-school"></i></div>
                <div>
                    <div class="stat-value">${fmtPct(data.synthese.taux_scolarisation_net)}</div>
                    <div class="stat-label">Taux de scolarisation net</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon sky"><i class="fas fa-globe-africa"></i></div>
                <div>
                    <div class="stat-value">${fmtNum(data.synthese.autres_nationalites)}</div>
                    <div class="stat-label">Autres nationalités</div>
                </div>
            </div>`;

        // Charts
        document.getElementById('eleves-charts').innerHTML = `
            <div class="chart-card">
                <h4><i class="fas fa-chart-line"></i> Évolution des effectifs</h4>
                <div class="chart-wrapper"><canvas id="chart-eleves-evolution"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-chart-pie"></i> Répartition par sexe</h4>
                <div class="chart-wrapper"><canvas id="chart-eleves-sexe"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-chart-bar"></i> Nationalités</h4>
                <div class="chart-wrapper"><canvas id="chart-eleves-nationalite"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-map-marker-alt"></i> Par province (Top 8)</h4>
                <div class="chart-wrapper"><canvas id="chart-eleves-province"></canvas></div>
            </div>`;

        // Graphique évolution
        destroyChart('chart-eleves-evolution');
        SIGE.charts['chart-eleves-evolution'] = new Chart(
            document.getElementById('chart-eleves-evolution'), {
            type: 'line',
            data: {
                labels: data.evolution.map(e => e.libelle),
                datasets: [
                    { label: 'Total', data: data.evolution.map(e => e.total), borderColor: COLORS.blue, backgroundColor: COLORS.blue + '15', fill: true, tension: 0.4 },
                    { label: 'Garçons', data: data.evolution.map(e => e.garcons), borderColor: COLORS.sky, tension: 0.4, borderDash: [5,5] },
                    { label: 'Filles', data: data.evolution.map(e => e.filles), borderColor: COLORS.red, tension: 0.4, borderDash: [5,5] },
                ],
            },
            options: chartOptions('Élèves'),
        });

        // Répartition sexe
        destroyChart('chart-eleves-sexe');
        SIGE.charts['chart-eleves-sexe'] = new Chart(
            document.getElementById('chart-eleves-sexe'), {
            type: 'doughnut',
            data: {
                labels: ['Garçons', 'Filles'],
                datasets: [{ data: [data.synthese.garcons, data.synthese.filles], backgroundColor: [COLORS.blue, COLORS.red], borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } },
        });

        // Nationalités
        destroyChart('chart-eleves-nationalite');
        SIGE.charts['chart-eleves-nationalite'] = new Chart(
            document.getElementById('chart-eleves-nationalite'), {
            type: 'bar',
            data: {
                labels: data.nationalites.map(n => n.nationalite),
                datasets: [{ label: 'Effectif', data: data.nationalites.map(n => n.effectif), backgroundColor: [COLORS.blue, COLORS.green, COLORS.sky, COLORS.orange], borderRadius: 6 }],
            },
            options: chartOptions('Élèves'),
        });

        // Par province
        destroyChart('chart-eleves-province');
        const top8 = data.par_province.slice(0, 8);
        SIGE.charts['chart-eleves-province'] = new Chart(
            document.getElementById('chart-eleves-province'), {
            type: 'bar',
            data: {
                labels: top8.map(p => p.libelle),
                datasets: [{ label: 'Élèves', data: top8.map(p => p.total), backgroundColor: COLORS.provinces, borderRadius: 6 }],
            },
            options: { ...chartOptions('Élèves'), indexAxis: 'y' },
        });

        // Tableau par province
        const tbody = document.getElementById('eleves-table-body');
        tbody.innerHTML = data.par_province.map(p => `
            <tr>
                <td><strong>${escHtml(p.libelle)}</strong></td>
                <td>${fmtNum(p.total)}</td>
                <td>${fmtNum(p.garcons)}</td>
                <td>${fmtNum(p.filles)}</td>
                <td>
                    <div class="progress-bar-wrap" style="width:120px">
                        <div class="progress-bar-fill blue" style="width:${p.pct_filles}%"></div>
                    </div>
                    <small>${fmtPct(p.pct_filles)} filles</small>
                </td>
            </tr>`).join('');

    }).catch(err => {
        console.error('Erreur chargement élèves:', err);
        document.getElementById('eleves-stats').innerHTML = '<div class="data-loading">Erreur de chargement</div>';
    });
}

// ─── Section RH ───────────────────────────────────────────────────────────────

function loadRHDetail() {
    showLoading('rh-stats');
    showLoading('rh-charts');
    showLoading('rh-table-body');

    ajax(BASE + '/api/rh.php', { action: 'detail' }).then(data => {
        document.getElementById('rh-stats').innerHTML = `
            <div class="stat-card">
                <div class="stat-card-icon green"><i class="fas fa-chalkboard-teacher"></i></div>
                <div><div class="stat-value">${fmtNum(data.synthese.enseignants)}</div><div class="stat-label">Enseignants</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="fas fa-user-tie"></i></div>
                <div><div class="stat-value">${fmtNum(data.synthese.administratifs)}</div><div class="stat-label">Administratifs</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon sky"><i class="fas fa-id-card"></i></div>
                <div><div class="stat-value">${fmtNum(data.synthese.enseignants_titulaires)}</div><div class="stat-label">Titulaires</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon orange"><i class="fas fa-balance-scale"></i></div>
                <div><div class="stat-value">${data.synthese.ratio_eleves_enseignant_national}</div><div class="stat-label">Ratio élèves/enseignant</div></div>
            </div>`;

        document.getElementById('rh-charts').innerHTML = `
            <div class="chart-card">
                <h4><i class="fas fa-chart-line"></i> Évolution du personnel</h4>
                <div class="chart-wrapper"><canvas id="chart-rh-evolution"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-chart-pie"></i> Répartition par genre</h4>
                <div class="chart-wrapper"><canvas id="chart-rh-genre"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-chart-bar"></i> Catégories d'enseignants</h4>
                <div class="chart-wrapper"><canvas id="chart-rh-categories"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-map-marker-alt"></i> Ratio par province</h4>
                <div class="chart-wrapper"><canvas id="chart-rh-ratio"></canvas></div>
            </div>`;

        destroyChart('chart-rh-evolution');
        SIGE.charts['chart-rh-evolution'] = new Chart(
            document.getElementById('chart-rh-evolution'), {
            type: 'bar',
            data: {
                labels: data.evolution.map(e => e.libelle),
                datasets: [
                    { label: 'Enseignants', data: data.evolution.map(e => e.enseignants), backgroundColor: COLORS.green, borderRadius: 4 },
                    { label: 'Administratifs', data: data.evolution.map(e => e.administratifs), backgroundColor: COLORS.blue, borderRadius: 4 },
                ],
            },
            options: { ...chartOptions('Personnel'), plugins: { legend: { position: 'bottom' } } },
        });

        destroyChart('chart-rh-genre');
        SIGE.charts['chart-rh-genre'] = new Chart(
            document.getElementById('chart-rh-genre'), {
            type: 'doughnut',
            data: {
                labels: ['Hommes', 'Femmes'],
                datasets: [{ data: [data.synthese.hommes, data.synthese.femmes], backgroundColor: [COLORS.blue, COLORS.red], borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } },
        });

        if (data.categories) {
            destroyChart('chart-rh-categories');
            SIGE.charts['chart-rh-categories'] = new Chart(
                document.getElementById('chart-rh-categories'), {
                type: 'bar',
                data: {
                    labels: data.categories.map(c => c.categorie.split('(')[0].trim()),
                    datasets: [{ label: 'Effectif', data: data.categories.map(c => c.effectif), backgroundColor: COLORS.provinces.slice(0,4), borderRadius: 6 }],
                },
                options: chartOptions('Enseignants'),
            });
        }

        // Ratio par province
        const top8 = data.par_province.slice(0, 8);
        destroyChart('chart-rh-ratio');
        SIGE.charts['chart-rh-ratio'] = new Chart(
            document.getElementById('chart-rh-ratio'), {
            type: 'bar',
            data: {
                labels: top8.map(p => p.libelle),
                datasets: [{ label: 'Ratio élèves/enseignant', data: top8.map(p => p.ratio), backgroundColor: COLORS.sky, borderRadius: 6 }],
            },
            options: { ...chartOptions('Ratio'), indexAxis: 'y' },
        });

        // Tableau
        const tbody = document.getElementById('rh-table-body');
        tbody.innerHTML = data.par_province.map(p => `
            <tr>
                <td><strong>${escHtml(p.libelle)}</strong></td>
                <td>${fmtNum(p.enseignants)}</td>
                <td>${fmtNum(p.titulaires)}</td>
                <td>${fmtNum(p.contractuels)}</td>
                <td>${fmtNum(p.administratifs)}</td>
                <td>${p.ratio}</td>
            </tr>`).join('');

    }).catch(console.error);
}

// ─── Section Examens ─────────────────────────────────────────────────────────

function loadExamensDetail() {
    showLoading('examens-sessions');
    showLoading('examens-charts');
    showLoading('examens-table-body');

    ajax(BASE + '/api/examens.php', { action: 'detail' }).then(data => {
        // Cards sessions
        document.getElementById('examens-sessions').innerHTML = data.sessions.map(s => `
            <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:.5rem">
                <div style="font-size:.88rem;font-weight:700;color:var(--gray-800)">${escHtml(s.examen)}</div>
                <div style="display:flex;gap:1.5rem;flex-wrap:wrap">
                    <div><div class="stat-value" style="font-size:1.2rem">${fmtNum(s.inscrits)}</div><div class="stat-label">Inscrits</div></div>
                    <div><div class="stat-value" style="font-size:1.2rem;color:var(--green)">${fmtNum(s.admis)}</div><div class="stat-label">Admis</div></div>
                    <div>
                        <div class="stat-value" style="font-size:1.2rem;color:${s.taux_reussite >= 75 ? 'var(--green)' : s.taux_reussite >= 60 ? 'var(--orange)' : 'var(--red)'}">
                            ${fmtPct(s.taux_reussite)}</div>
                        <div class="stat-label">Taux réussite</div>
                    </div>
                </div>
                <div class="progress-bar-wrap" style="width:100%;margin-top:.25rem">
                    <div class="progress-bar-fill ${s.taux_reussite >= 75 ? 'green' : 'blue'}" style="width:${s.taux_reussite}%"></div>
                </div>
            </div>`).join('');

        document.getElementById('examens-charts').innerHTML = `
            <div class="chart-card">
                <h4><i class="fas fa-chart-line"></i> Historique des taux de réussite</h4>
                <div class="chart-wrapper"><canvas id="chart-exam-historique"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-chart-bar"></i> Taux de réussite par province (CN8)</h4>
                <div class="chart-wrapper"><canvas id="chart-exam-province"></canvas></div>
            </div>`;

        // Historique
        destroyChart('chart-exam-historique');
        SIGE.charts['chart-exam-historique'] = new Chart(
            document.getElementById('chart-exam-historique'), {
            type: 'line',
            data: {
                labels: data.historique.map(h => h.libelle),
                datasets: [
                    { label: 'CN8 (%)', data: data.historique.map(h => h.CN8?.taux), borderColor: COLORS.blue, backgroundColor: COLORS.blue + '20', fill: true, tension: 0.4 },
                    { label: 'Examen État (%)', data: data.historique.map(h => h.ETAT12?.taux), borderColor: COLORS.green, backgroundColor: COLORS.green + '20', fill: true, tension: 0.4 },
                ],
            },
            options: chartOptions('%'),
        });

        // Par province
        if (data.par_province_cn8) {
            const top10 = data.par_province_cn8.slice(0, 10);
            destroyChart('chart-exam-province');
            SIGE.charts['chart-exam-province'] = new Chart(
                document.getElementById('chart-exam-province'), {
                type: 'bar',
                data: {
                    labels: top10.map(p => p.libelle),
                    datasets: [{ label: 'Taux (%)', data: top10.map(p => p.taux), backgroundColor: top10.map(p => p.taux >= 80 ? COLORS.green : p.taux >= 70 ? COLORS.blue : COLORS.red), borderRadius: 6 }],
                },
                options: { ...chartOptions('%'), indexAxis: 'y' },
            });
        }

        // Tableau province
        const tbody = document.getElementById('examens-table-body');
        tbody.innerHTML = (data.par_province_cn8 || []).map(p => `
            <tr>
                <td><strong>${escHtml(p.libelle)}</strong></td>
                <td>${fmtNum(p.inscrits)}</td>
                <td>${fmtNum(p.presents)}</td>
                <td>${fmtNum(p.admis)}</td>
                <td><span class="kpi-variation ${p.taux >= 75 ? 'up' : 'down'}">${fmtPct(p.taux)}</span></td>
            </tr>`).join('');

    }).catch(console.error);
}

// ─── Section Établissements ───────────────────────────────────────────────────

function loadEtablissementsDetail() {
    showLoading('etab-stats');
    showLoading('etab-charts');
    showLoading('etab-table-body');

    ajax(BASE + '/api/etablissements.php', { action: 'detail' }).then(data => {
        document.getElementById('etab-stats').innerHTML = `
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="fas fa-school"></i></div>
                <div><div class="stat-value">${fmtNum(data.synthese.total_etablissements)}</div><div class="stat-label">Total établissements</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon green"><i class="fas fa-landmark"></i></div>
                <div><div class="stat-value">${fmtNum(data.synthese.par_secteur.public)}</div><div class="stat-label">Publics</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon sky"><i class="fas fa-bolt"></i></div>
                <div><div class="stat-value">${fmtPct(data.synthese.infra_pct_electricite)}</div><div class="stat-label">Avec électricité</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="fas fa-tint"></i></div>
                <div><div class="stat-value">${fmtPct(data.synthese.infra_pct_eau)}</div><div class="stat-label">Avec eau potable</div></div>
            </div>`;

        document.getElementById('etab-charts').innerHTML = `
            <div class="chart-card">
                <h4><i class="fas fa-chart-pie"></i> Par secteur</h4>
                <div class="chart-wrapper"><canvas id="chart-etab-secteur"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-chart-bar"></i> Par province</h4>
                <div class="chart-wrapper"><canvas id="chart-etab-province"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-chart-pie"></i> Milieu (urbain/rural)</h4>
                <div class="chart-wrapper"><canvas id="chart-etab-milieu"></canvas></div>
            </div>
            <div class="chart-card">
                <h4><i class="fas fa-tools"></i> Infrastructures (%)</h4>
                <div class="chart-wrapper"><canvas id="chart-etab-infra"></canvas></div>
            </div>`;

        const s = data.synthese;
        destroyChart('chart-etab-secteur');
        SIGE.charts['chart-etab-secteur'] = new Chart(
            document.getElementById('chart-etab-secteur'), {
            type: 'pie',
            data: {
                labels: ['Public', 'Privé', 'Conventionné'],
                datasets: [{ data: [s.par_secteur.public, s.par_secteur.prive, s.par_secteur.conventionne], backgroundColor: [COLORS.blue, COLORS.red, COLORS.green], borderWidth: 2, borderColor: '#fff' }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });

        destroyChart('chart-etab-milieu');
        SIGE.charts['chart-etab-milieu'] = new Chart(
            document.getElementById('chart-etab-milieu'), {
            type: 'doughnut',
            data: {
                labels: ['Urbain', 'Semi-urbain', 'Rural'],
                datasets: [{ data: [s.par_milieu.urbain, s.par_milieu['semi-urbain'] || 0, s.par_milieu.rural], backgroundColor: [COLORS.sky, COLORS.orange, COLORS.green], borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } },
        });

        // Infra radar
        destroyChart('chart-etab-infra');
        SIGE.charts['chart-etab-infra'] = new Chart(
            document.getElementById('chart-etab-infra'), {
            type: 'bar',
            data: {
                labels: ['Électricité', 'Eau potable', 'Internet', 'Salles utilisées'],
                datasets: [{ label: '%', data: [s.infra_pct_electricite, s.infra_pct_eau, s.infra_pct_internet, s.infra_pct_salles], backgroundColor: [COLORS.orange, COLORS.blue, COLORS.green, COLORS.sky], borderRadius: 8 }],
            },
            options: { ...chartOptions('%'), scales: { y: { max: 100 } } },
        });

        // Tableau établissements
        const tbody = document.getElementById('etab-table-body');
        tbody.innerHTML = data.liste.map(e => `
            <tr>
                <td><strong>${escHtml(e.nom)}</strong></td>
                <td>${escHtml(e.province)}</td>
                <td><span class="badge ${e.secteur_key}">${escHtml(e.secteur)}</span></td>
                <td><span class="badge ${e.milieu}">${escHtml(e.milieu)}</span></td>
                <td>
                    ${e.electricite ? '<i class="fas fa-check-circle" style="color:var(--green)"></i>' : '<i class="fas fa-times-circle" style="color:var(--gray-300)"></i>'}
                    ${e.eau_potable ? '<i class="fas fa-check-circle" style="color:var(--blue)" title="Eau"></i>' : '<i class="fas fa-times-circle" style="color:var(--gray-300)"></i>'}
                    ${e.acces_internet ? '<i class="fas fa-wifi" style="color:var(--sky)"></i>' : ''}
                </td>
                <td>${fmtNum(e.nb_salles_classe)}</td>
            </tr>`).join('');

    }).catch(console.error);
}

// ─── Options graphiques communes ──────────────────────────────────────────────

function chartOptions(unit = '') {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${fmtNum(ctx.raw)} ${unit}`,
                },
            },
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { grid: { color: '#f0f0f0' }, ticks: { font: { size: 11 } } },
        },
    };
}

// ─── Utilitaire anti-XSS ─────────────────────────────────────────────────────

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ─── Gestion du changement d'année ───────────────────────────────────────────

function onAnneeChange(newAnnee) {
    SIGE.annee = parseInt(newAnnee, 10);
    // Actualiser le label année
    const yearLabel = document.getElementById('year-label');
    if (yearLabel) {
        const sel = document.getElementById('year-selector');
        yearLabel.textContent = sel ? sel.options[sel.selectedIndex].text : newAnnee;
    }
    // Recharger selon la section active
    loadKPIs();
    loadKanbanSummaries();
    if (SIGE.activeSection !== 'kanban') {
        const loaders = {
            eleves: loadElevesDetail,
            rh: loadRHDetail,
            examens: loadExamensDetail,
            etablissements: loadEtablissementsDetail,
        };
        if (loaders[SIGE.activeSection]) loaders[SIGE.activeSection]();
    }
}

// ─── Menu mobile ─────────────────────────────────────────────────────────────

function toggleMobileMenu() {
    const menu = document.querySelector('.navbar-menu');
    menu.classList.toggle('open');
}

// ─── Initialisation ───────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    // Récupérer l'année initiale depuis le sélecteur
    const sel = document.getElementById('year-selector');
    if (sel) {
        SIGE.annee = parseInt(sel.value, 10);
        sel.addEventListener('change', e => onAnneeChange(e.target.value));
    }

    // Bouton menu mobile
    const toggle = document.getElementById('nav-toggle');
    if (toggle) toggle.addEventListener('click', toggleMobileMenu);

    // Fermer menu mobile au clic sur un lien
    document.querySelectorAll('.navbar-menu a').forEach(a => {
        a.addEventListener('click', () => {
            document.querySelector('.navbar-menu').classList.remove('open');
            // Gestion active
            document.querySelectorAll('.navbar-menu a').forEach(x => x.classList.remove('active'));
            a.classList.add('active');
        });
    });

    // Charger les données initiales
    loadKPIs();
    loadKanbanSummaries();
});
