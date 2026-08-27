<?php
/**
 * SIGE Burundi — Portail Public — Page d'accueil
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$connector = ConnectorFactory::getConnector();
$annees    = $connector->getAnnees();
$anneeRef  = 14; // Année par défaut
$anneeLibelle = '2028/2029';
foreach ($annees as $a) {
    if ($a['code_type_annee'] == $anneeRef) { $anneeLibelle = $a['libelle']; break; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Système d'Information pour la Gestion de l'Éducation — République du Burundi">
    <title>SIGE Burundi — Système d'Interopérabilité</title>

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- CSS portail -->
    <link rel="stylesheet" href="<?= ASSETS_BASE_URL ?>/css/portal.css">

    <style>
        body { font-family: 'Nunito', 'Segoe UI', Arial, sans-serif; }
        /* Navbar flag accent */
        .flag-band {
            height: 4px;
            background: linear-gradient(90deg, #1e88e5 33.3%, #ffffff 33.3% 66.6%, #43a047 66.6%);
        }
        .hero-flag { display: flex; align-items: center; gap: 6px; margin-bottom: .5rem; }
        .hero-flag span { font-size: .75rem; opacity: .8; letter-spacing: 1px; text-transform: uppercase; }
    </style>
</head>
<body>

<!-- ─── Bande drapeau ─── -->
<div class="flag-band"></div>

<!-- ─── NAVBAR ─── -->
<nav class="portal-navbar" id="navbar">
    <div class="navbar-inner">
        <div class="navbar-brand">
            <div class="navbar-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="navbar-title">
                <h1>SIGE Burundi</h1>
                <span>Système d'Interopérabilité Éducation</span>
            </div>
        </div>

        <button class="navbar-toggle" id="nav-toggle" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="navbar-menu" id="main-nav">
            <li><a href="#" class="active" data-section="kanban" onclick="showSection('kanban');return false;">
                <i class="fas fa-home"></i> Accueil
            </a></li>
            <li><a href="#" data-section="eleves" onclick="showSection('eleves');return false;">
                <i class="fas fa-users"></i> Élèves
            </a></li>
            <li><a href="#" data-section="rh" onclick="showSection('rh');return false;">
                <i class="fas fa-chalkboard-teacher"></i> Ressources humaines
            </a></li>
            <li><a href="#" data-section="examens" onclick="showSection('examens');return false;">
                <i class="fas fa-file-alt"></i> Examens &amp; concours
            </a></li>
            <li><a href="#" data-section="etablissements" onclick="showSection('etablissements');return false;">
                <i class="fas fa-school"></i> Établissements
            </a></li>
            <li><a href="#" data-section="carte" onclick="showSection('carte');return false;">
                <i class="fas fa-map-marked-alt"></i> Carte scolaire
            </a></li>
            <li><a href="../admin/" class="navbar-admin-link" target="_self">
                <i class="fas fa-lock"></i> Administration
            </a></li>
        </ul>
    </div>
</nav>

<!-- ─── HERO BANNER ─── -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <div class="hero-flag">
                <i class="fas fa-flag" style="font-size:.9rem"></i>
                <span>République du Burundi</span>
                <span class="hero-badge">
                    <i class="fas fa-database"></i> Mode démonstration
                </span>
            </div>
            <h2>Système d'Information pour<br>la Gestion de l'Éducation</h2>
            <p>Plateforme d'interopérabilité consolidant les données de StatEduc, du SIGE-RH, 
            des examens &amp; concours et de la carte scolaire du Burundi.</p>
        </div>
        <div class="hero-controls">
            <label for="year-selector"><i class="fas fa-calendar-alt"></i> Année de recensement</label>
            <select class="year-selector" id="year-selector" aria-label="Sélecteur d'année">
                <?php foreach (array_reverse($annees) as $a): ?>
                <option value="<?= (int)$a['code_type_annee'] ?>"<?= $a['code_type_annee'] == $anneeRef ? ' selected' : '' ?>>
                    <?= e($a['libelle']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div class="data-source-badge mock">
                <i class="fas fa-circle"></i> Données mock JSON
            </div>
        </div>
    </div>
</section>

<!-- ─── BANDE KPI ─── -->
<div class="kpi-band">
    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-school"></i></div>
        <div class="kpi-content">
            <div class="kpi-value" id="kpi-etab">—</div>
            <div class="kpi-label">Établissements</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fas fa-users"></i></div>
        <div class="kpi-content">
            <div class="kpi-value" id="kpi-eleves">—</div>
            <div class="kpi-label">Élèves scolarisés</div>
            <div class="kpi-variation flat" id="kpi-eleves-var"></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon sky"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="kpi-content">
            <div class="kpi-value" id="kpi-enseignants">—</div>
            <div class="kpi-label">Enseignants</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-star"></i></div>
        <div class="kpi-content">
            <div class="kpi-value" id="kpi-reussite">—</div>
            <div class="kpi-label">Taux de réussite CN8 (%)</div>
        </div>
    </div>
</div>

<!-- ─── CONTENU PRINCIPAL ─── -->
<main class="main-content" style="margin-top: -4px;">

<!-- ═══ KANBAN ═══════════════════════════════════════════════════════════════ -->
<div id="kanban-section">
    <div class="section-header" style="margin-top:1.5rem">
        <h3 class="section-title">
            <i class="fas fa-th-large"></i>
            Tableau de bord — Année <span id="year-label"><?= e($anneeLibelle) ?></span>
        </h3>
        <div class="data-source-badge mock">
            <i class="fas fa-database"></i> StatEduc · SIGE-RH · Examens · Carte scolaire
        </div>
    </div>

    <div class="kanban-grid">
        <!-- Carte Élèves -->
        <div class="kanban-card blue" onclick="showSection('eleves')" role="button" tabindex="0">
            <div class="kanban-header">
                <span class="kanban-title"><i class="fas fa-users" style="color:var(--blue)"></i> Données des élèves</span>
                <span class="kanban-badge">StatEduc</span>
            </div>
            <div class="kanban-body">
                <div class="kanban-stat">
                    <div class="kanban-stat-value" id="k-eleves-total">
                        <span class="loading-spinner"></span>
                    </div>
                    <div class="kanban-stat-label">Élèves scolarisés</div>
                </div>
                <canvas id="sparkline-eleves" class="kanban-sparkline"></canvas>
                <div class="kanban-meta">
                    <div class="kanban-meta-item">
                        <i class="fas fa-venus" style="color:var(--red)"></i>
                        Filles : <strong id="k-eleves-filles">…</strong>
                    </div>
                    <div class="kanban-meta-item">
                        <i class="fas fa-mars" style="color:var(--blue)"></i>
                        Garçons : <strong id="k-eleves-garcons">…</strong>
                    </div>
                </div>
            </div>
            <div class="kanban-footer">
                <span style="font-size:.75rem;color:var(--gray-500)">Recensement pluriannuel</span>
                <button class="btn-explore" onclick="showSection('eleves');event.stopPropagation()">
                    Explorer <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Carte RH -->
        <div class="kanban-card green" onclick="showSection('rh')" role="button" tabindex="0">
            <div class="kanban-header">
                <span class="kanban-title"><i class="fas fa-chalkboard-teacher" style="color:var(--green)"></i> Ressources humaines</span>
                <span class="kanban-badge">SIGE-RH</span>
            </div>
            <div class="kanban-body">
                <div class="kanban-stat">
                    <div class="kanban-stat-value" id="k-rh-total">
                        <span class="loading-spinner"></span>
                    </div>
                    <div class="kanban-stat-label">Personnel total</div>
                </div>
                <canvas id="sparkline-rh" class="kanban-sparkline"></canvas>
                <div class="kanban-meta">
                    <div class="kanban-meta-item">
                        <i class="fas fa-balance-scale" style="color:var(--green)"></i>
                        <strong id="k-rh-ratio">…</strong>
                    </div>
                </div>
            </div>
            <div class="kanban-footer">
                <span style="font-size:.75rem;color:var(--gray-500)">Enseignants &amp; administratifs</span>
                <button class="btn-explore" onclick="showSection('rh');event.stopPropagation()">
                    Explorer <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Carte Examens -->
        <div class="kanban-card red" onclick="showSection('examens')" role="button" tabindex="0">
            <div class="kanban-header">
                <span class="kanban-title"><i class="fas fa-file-alt" style="color:var(--red)"></i> Examens &amp; concours</span>
                <span class="kanban-badge">MINÉAC</span>
            </div>
            <div class="kanban-body">
                <div class="kanban-stat">
                    <div class="kanban-stat-value" id="k-exam-taux">
                        <span class="loading-spinner"></span>
                    </div>
                    <div class="kanban-stat-label">Taux de réussite CN8</div>
                </div>
                <canvas id="sparkline-examens" class="kanban-sparkline"></canvas>
                <div class="kanban-meta">
                    <div class="kanban-meta-item">
                        <i class="fas fa-check-circle" style="color:var(--green)"></i>
                        <strong id="k-exam-admis">…</strong>
                    </div>
                </div>
            </div>
            <div class="kanban-footer">
                <span style="font-size:.75rem;color:var(--gray-500)">CN8 · Examen d'État</span>
                <button class="btn-explore" onclick="showSection('examens');event.stopPropagation()">
                    Explorer <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Carte Établissements -->
        <div class="kanban-card sky" onclick="showSection('etablissements')" role="button" tabindex="0">
            <div class="kanban-header">
                <span class="kanban-title"><i class="fas fa-school" style="color:#0288d1"></i> Établissements</span>
                <span class="kanban-badge">Carte scolaire</span>
            </div>
            <div class="kanban-body">
                <div class="kanban-stat">
                    <div class="kanban-stat-value" id="k-etab-total">
                        <span class="loading-spinner"></span>
                    </div>
                    <div class="kanban-stat-label">Établissements recensés</div>
                </div>
                <canvas id="sparkline-etab" class="kanban-sparkline"></canvas>
                <div class="kanban-meta">
                    <div class="kanban-meta-item">
                        <i class="fas fa-landmark" style="color:#0288d1"></i>
                        <strong id="k-etab-public">…</strong>
                    </div>
                    <div class="kanban-meta-item">
                        <i class="fas fa-tree" style="color:var(--green)"></i>
                        <strong id="k-etab-rural">…</strong>
                    </div>
                </div>
            </div>
            <div class="kanban-footer">
                <span style="font-size:.75rem;color:var(--gray-500)">18 provinces · 3 secteurs</span>
                <button class="btn-explore" onclick="showSection('etablissements');event.stopPropagation()">
                    Explorer <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Présentation du SIGE -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:2rem">
        <div class="chart-card">
            <h4 style="font-size:1rem;font-weight:700;margin-bottom:1rem;color:var(--gray-800)">
                <i class="fas fa-info-circle" style="color:var(--blue)"></i> 
                À propos du Système d'Interopérabilité du SIGE
            </h4>
            <p style="color:var(--gray-600);font-size:.88rem;line-height:1.8;margin-bottom:.75rem">
                Le <strong>Système d'Interopérabilité du SIGE</strong> est la plateforme centrale qui fait communiquer 
                les quatre systèmes d'information du secteur éducatif burundais : 
                <strong>StatEduc</strong> (recensement scolaire), <strong>SIGE-RH</strong> (ressources humaines), 
                le système <strong>Examens &amp; concours</strong> et la <strong>Carte scolaire</strong>.
            </p>
            <p style="color:var(--gray-600);font-size:.88rem;line-height:1.8">
                Il garantit la cohérence des référentiels communs (établissements, localisation, années académiques) 
                et permet une vision consolidée de l'éducation sur l'ensemble du territoire national, 
                couvrant les <strong>18 provinces</strong> et plus de <strong>4&nbsp;500 établissements</strong>.
            </p>
        </div>
        <div class="chart-card" style="background:linear-gradient(135deg,#1e88e5,#1565c0);color:white">
            <h4 style="font-size:.9rem;font-weight:700;margin-bottom:1rem;opacity:.9">
                <i class="fas fa-link"></i> Systèmes connectés
            </h4>
            <?php
            $systemes = [
                ['StatEduc', 'Recensement scolaire pluriannuel', 'fas fa-database', '#e3f2fd', '#1565c0'],
                ['SIGE-RH', 'Gestion des ressources humaines', 'fas fa-users-cog', '#e8f5e9', '#2e7d32'],
                ['Examens', 'Concours nationaux &amp; examen d\'État', 'fas fa-file-alt', '#ffebee', '#b71c1c'],
                ['Carte scolaire', 'Géolocalisation des établissements', 'fas fa-map-marked-alt', '#e1f5fe', '#01579b'],
            ];
            foreach ($systemes as [$nom, $desc, $icon, $bg, $color]): ?>
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;background:rgba(255,255,255,.12);border-radius:8px;padding:.6rem .85rem">
                <div style="width:32px;height:32px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="<?= $icon ?>" style="font-size:.85rem"></i>
                </div>
                <div>
                    <div style="font-size:.82rem;font-weight:700"><?= $nom ?></div>
                    <div style="font-size:.72rem;opacity:.75"><?= $desc ?></div>
                </div>
                <div style="margin-left:auto;font-size:.65rem;background:rgba(255,255,255,.15);border-radius:20px;padding:2px 8px;color:rgba(255,255,255,.9)">
                    ACTIF
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ═══ SECTION ÉLÈVES ════════════════════════════════════════════════════════ -->
<section class="detail-section" id="eleves-section">
    <div class="detail-header">
        <button class="detail-back" onclick="goBack()">
            <i class="fas fa-arrow-left"></i> Retour
        </button>
        <h2 class="detail-title">
            <i class="fas fa-users" style="color:var(--blue)"></i>
            Données des élèves — StatEduc
        </h2>
        <div class="data-source-badge mock"><i class="fas fa-database"></i> StatEduc</div>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <label><i class="fas fa-calendar"></i> Année</label>
            <select id="eleves-annee" onchange="SIGE.annee=this.value;loadElevesDetail()">
                <?php foreach (array_reverse($annees) as $a): ?>
                <option value="<?= (int)$a['code_type_annee'] ?>"<?= $a['code_type_annee'] == $anneeRef ? ' selected' : '' ?>>
                    <?= e($a['libelle']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label><i class="fas fa-map"></i> Province</label>
            <select id="eleves-province">
                <option value="">Toutes les provinces</option>
                <?php foreach ($connector->getProvinces() as $p): ?>
                <option value="<?= (int)$p['id_province'] ?>"><?= e($p['libelle']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="eleves-stats" class="stats-grid">
        <div class="data-loading"><div class="spinner-ring"></div> Chargement…</div>
    </div>
    <div id="eleves-charts" class="chart-grid">
        <div class="data-loading"><div class="spinner-ring"></div> Chargement des graphiques…</div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h4><i class="fas fa-table"></i> Effectifs par province</h4>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Province</th>
                        <th>Effectif total</th>
                        <th>Garçons</th>
                        <th>Filles</th>
                        <th>Parité</th>
                    </tr>
                </thead>
                <tbody id="eleves-table-body">
                    <tr><td colspan="5" class="data-loading">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ═══ SECTION RH ════════════════════════════════════════════════════════════ -->
<section class="detail-section" id="rh-section">
    <div class="detail-header">
        <button class="detail-back" onclick="goBack()"><i class="fas fa-arrow-left"></i> Retour</button>
        <h2 class="detail-title"><i class="fas fa-chalkboard-teacher" style="color:var(--green)"></i> Ressources humaines — SIGE-RH</h2>
        <div class="data-source-badge mock"><i class="fas fa-database"></i> SIGE-RH</div>
    </div>

    <div id="rh-stats" class="stats-grid">
        <div class="data-loading"><div class="spinner-ring"></div> Chargement…</div>
    </div>
    <div id="rh-charts" class="chart-grid">
        <div class="data-loading"><div class="spinner-ring"></div></div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h4><i class="fas fa-table"></i> Personnel par province</h4>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Province</th>
                        <th>Enseignants</th>
                        <th>Titulaires</th>
                        <th>Contractuels</th>
                        <th>Administratifs</th>
                        <th>Ratio él./ens.</th>
                    </tr>
                </thead>
                <tbody id="rh-table-body">
                    <tr><td colspan="6" class="data-loading">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ═══ SECTION EXAMENS ═══════════════════════════════════════════════════════ -->
<section class="detail-section" id="examens-section">
    <div class="detail-header">
        <button class="detail-back" onclick="goBack()"><i class="fas fa-arrow-left"></i> Retour</button>
        <h2 class="detail-title"><i class="fas fa-file-alt" style="color:var(--red)"></i> Examens &amp; concours</h2>
        <div class="data-source-badge mock"><i class="fas fa-database"></i> Système Examens</div>
    </div>

    <div id="examens-sessions" class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(340px,1fr))">
        <div class="data-loading"><div class="spinner-ring"></div></div>
    </div>
    <div id="examens-charts" class="chart-grid">
        <div class="data-loading"><div class="spinner-ring"></div></div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h4><i class="fas fa-table"></i> Résultats CN8 par province</h4>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Province</th>
                        <th>Inscrits</th>
                        <th>Présents</th>
                        <th>Admis</th>
                        <th>Taux de réussite</th>
                    </tr>
                </thead>
                <tbody id="examens-table-body">
                    <tr><td colspan="5" class="data-loading">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ═══ SECTION ÉTABLISSEMENTS ═══════════════════════════════════════════════ -->
<section class="detail-section" id="etablissements-section">
    <div class="detail-header">
        <button class="detail-back" onclick="goBack()"><i class="fas fa-arrow-left"></i> Retour</button>
        <h2 class="detail-title"><i class="fas fa-school" style="color:#0288d1"></i> Établissements scolaires</h2>
        <div class="data-source-badge mock"><i class="fas fa-database"></i> Carte scolaire</div>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <label><i class="fas fa-search"></i> Recherche</label>
            <input type="text" id="etab-search" class="filter-search" placeholder="Nom d'établissement…" oninput="loadEtablissementsDetail()">
        </div>
        <div class="filter-group">
            <label><i class="fas fa-map"></i> Province</label>
            <select id="etab-province" onchange="loadEtablissementsDetail()">
                <option value="">Toutes</option>
                <?php foreach ($connector->getProvinces() as $p): ?>
                <option value="<?= (int)$p['id_province'] ?>"><?= e($p['libelle']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label><i class="fas fa-tag"></i> Secteur</label>
            <select id="etab-secteur" onchange="loadEtablissementsDetail()">
                <option value="">Tous</option>
                <option value="1">Public</option>
                <option value="2">Privé</option>
                <option value="3">Conventionné</option>
            </select>
        </div>
    </div>

    <div id="etab-stats" class="stats-grid">
        <div class="data-loading"><div class="spinner-ring"></div></div>
    </div>
    <div id="etab-charts" class="chart-grid">
        <div class="data-loading"><div class="spinner-ring"></div></div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h4><i class="fas fa-table"></i> Liste des établissements</h4>
            <small style="color:var(--gray-400);font-size:.78rem">Données de l'échantillon mock</small>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Province</th>
                        <th>Secteur</th>
                        <th>Milieu</th>
                        <th>Infrastructures</th>
                        <th>Salles</th>
                    </tr>
                </thead>
                <tbody id="etab-table-body">
                    <tr><td colspan="6" class="data-loading">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ─────────────────────────────────── SECTION CARTE ─────────────────────────────────── -->
<section class="detail-section" id="carte-section">
    <div class="section-header">
        <h2><i class="fas fa-map-marked-alt"></i> Carte scolaire du Burundi</h2>
        <p class="section-sub">Localisation géographique des établissements scolaires — Source : Atlas Coline SIGE</p>
    </div>

    <!-- Compteurs carte -->
    <div class="kpi-band" id="carte-kpi-band">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-map-pin"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" id="carte-kpi-total">—</div>
                <div class="kpi-label">Établissements localisés</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-city"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" id="carte-kpi-urbain">—</div>
                <div class="kpi-label">En milieu urbain</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fff3e0;color:#fb8c00"><i class="fas fa-tree"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" id="carte-kpi-rural">—</div>
                <div class="kpi-label">En milieu rural</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#f3e5f5;color:#9c27b0"><i class="fas fa-map"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" id="carte-kpi-provinces">—</div>
                <div class="kpi-label">Provinces couvertes</div>
            </div>
        </div>
    </div>

    <!-- Filtres carte -->
    <div class="filters-bar" style="flex-wrap:wrap;gap:.75rem;align-items:flex-end;margin-bottom:1rem">
        <div class="filter-group">
            <label class="filter-label"><i class="fas fa-map"></i> Province</label>
            <select id="carte-province" class="filter-select" onchange="applyCarteFilters()">
                <option value="">Toutes les provinces</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label"><i class="fas fa-layer-group"></i> Secteur</label>
            <select id="carte-secteur" class="filter-select" onchange="applyCarteFilters()">
                <option value="">Tous secteurs</option>
                <option value="Fondamental">Fondamental</option>
                <option value="Préscolaire">Préscolaire</option>
                <option value="Post-fondamental">Post-fondamental</option>
                <option value="Secondaire">Secondaire</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label"><i class="fas fa-map-pin"></i> Milieu</label>
            <select id="carte-milieu" class="filter-select" onchange="applyCarteFilters()">
                <option value="">Tous milieux</option>
                <option value="urbain">Urbain</option>
                <option value="rural">Rural</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label"><i class="fas fa-search"></i> Recherche</label>
            <input type="text" id="carte-search" class="filter-input" placeholder="Nom, commune, colline…" oninput="debounceCarteSearch()" style="min-width:200px">
        </div>
        <div class="filter-group" style="margin-left:auto;display:flex;gap:.5rem;align-items:flex-end">
            <button class="btn-explore" onclick="resetCarteFilters()" style="background:#6c757d">
                <i class="fas fa-undo"></i> Réinitialiser
            </button>
            <a id="carte-export-btn" href="<?= API_BASE_URL ?>/export.php?module=carte&format=csv" class="btn-explore" style="background:var(--green);text-decoration:none">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Légende -->
    <div style="display:flex;flex-wrap:wrap;gap:.5rem 1.2rem;margin-bottom:.75rem;font-size:.82rem;align-items:center">
        <strong style="color:var(--text-muted)">Légende :</strong>
        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#2196F3;margin-right:4px"></span>Fondamental</span>
        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#9C27B0;margin-right:4px"></span>Préscolaire</span>
        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#FF9800;margin-right:4px"></span>Post-fondamental</span>
        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#4CAF50;margin-right:4px"></span>Secondaire</span>
        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#607D8B;margin-right:4px"></span>Autre</span>
        <span style="margin-left:auto;color:var(--text-muted)" id="carte-count-label">—</span>
    </div>

    <!-- Carte Leaflet -->
    <div id="map-container" style="border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.12);position:relative">
        <div id="map-loading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:1000;background:rgba(255,255,255,.95);padding:1.5rem 2.5rem;border-radius:12px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.15)">
            <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--blue);margin-bottom:.5rem"></i>
            <div style="font-weight:600;color:var(--text-primary)">Chargement de la carte…</div>
            <div style="font-size:.8rem;color:var(--text-muted)">683 établissements géolocalisés</div>
        </div>
        <div id="sige-map" style="height:680px;width:100%"></div>
    </div>

    <!-- Tableau résultats carte -->
    <div class="table-wrapper" style="margin-top:1.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
            <h4 style="margin:0;font-size:1rem;color:var(--text-primary)"><i class="fas fa-list"></i> Liste des établissements géolocalisés</h4>
        </div>
        <div style="overflow-x:auto">
            <table class="data-table" id="carte-table">
                <thead>
                    <tr>
                        <th>Nom de l'établissement</th>
                        <th>Province</th>
                        <th>Commune</th>
                        <th>Colline</th>
                        <th>Secteur</th>
                        <th>Milieu</th>
                        <th>Statut</th>
                        <th>GPS</th>
                    </tr>
                </thead>
                <tbody id="carte-table-body">
                    <tr><td colspan="8" class="data-loading">Chargement des données…</td></tr>
                </tbody>
            </table>
        </div>
        <div id="carte-pagination" style="text-align:center;margin-top:.75rem;font-size:.85rem;color:var(--text-muted)"></div>
    </div>
</section>

</main>

<!-- ─── FOOTER ─── -->
<footer class="portal-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:.75rem">
                <div class="navbar-logo" style="width:40px;height:40px;font-size:1rem"><i class="fas fa-graduation-cap"></i></div>
                <div>
                    <div style="font-size:.95rem;font-weight:700">SIGE Burundi</div>
                    <div style="font-size:.75rem;opacity:.6">Ministère de l'Éducation Nationale</div>
                </div>
            </div>
            <p>Plateforme d'interopérabilité du Système d'Information pour la Gestion de l'Éducation 
            de la République du Burundi. Données issues de StatEduc, SIGE-RH, Examens &amp; Carte scolaire.</p>
        </div>
        <div class="footer-section">
            <h5>Navigation</h5>
            <ul>
                <li><a href="#" onclick="showSection('kanban');return false">Tableau de bord</a></li>
                <li><a href="#" onclick="showSection('eleves');return false">Données élèves</a></li>
                <li><a href="#" onclick="showSection('rh');return false">Ressources humaines</a></li>
                <li><a href="#" onclick="showSection('examens');return false">Examens &amp; concours</a></li>
                <li><a href="#" onclick="showSection('etablissements');return false">Établissements</a></li>
                <li><a href="#" onclick="showSection('carte');return false">Carte scolaire</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h5>Liens utiles</h5>
            <ul>
                <li><a href="../admin/">Espace administration</a></li>
                <li><a href="#">Documentation API</a></li>
                <li><a href="#">StatEduc</a></li>
                <li><a href="#">Portail du gouvernement</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> République du Burundi — Ministère de l'Éducation Nationale</span>
        <span>SIGE v<?= APP_VERSION ?> · Mode : <?= e(DATA_SOURCE_MODE) ?></span>
    </div>
</footer>

<!-- ─── Scripts ─── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Leaflet.js pour la carte géographique -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Leaflet MarkerCluster -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>window.SIGE_BASE = '<?= PUBLIC_BASE_URL ?>';</script>
<script src="<?= ASSETS_BASE_URL ?>/js/portal.js"></script>

<script>
// Surcharge de loadEtablissementsDetail pour intégrer les filtres UI de la page
// Cette fonction remplace celle du portal.js (chargé avant ce script)
function loadEtablissementsDetail() {
    const params = {
        action: 'detail',
        province: document.getElementById('etab-province')?.value || '',
        secteur: document.getElementById('etab-secteur')?.value || '',
        search: document.getElementById('etab-search')?.value || '',
    };
    showLoading('etab-stats');
    showLoading('etab-charts');
    showLoading('etab-table-body');
    ajax((window.SIGE_BASE||'') + '/api/etablissements.php', params).then(data => {
        // Réutiliser la logique du portal.js
        const s = data.synthese;
        document.getElementById('etab-stats').innerHTML = `
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="fas fa-school"></i></div>
                <div><div class="stat-value">${fmtNum(s.total_etablissements)}</div><div class="stat-label">Total établissements</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon green"><i class="fas fa-landmark"></i></div>
                <div><div class="stat-value">${fmtNum(s.par_secteur.public)}</div><div class="stat-label">Publics</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon sky"><i class="fas fa-bolt"></i></div>
                <div><div class="stat-value">${fmtPct(s.infra_pct_electricite)}</div><div class="stat-label">Avec électricité</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="fas fa-tint"></i></div>
                <div><div class="stat-value">${fmtPct(s.infra_pct_eau)}</div><div class="stat-label">Avec eau potable</div></div>
            </div>`;

        document.getElementById('etab-charts').innerHTML = `
            <div class="chart-card"><h4><i class="fas fa-chart-pie"></i> Par secteur</h4><div class="chart-wrapper"><canvas id="chart-etab-secteur"></canvas></div></div>
            <div class="chart-card"><h4><i class="fas fa-chart-pie"></i> Urbain / Rural</h4><div class="chart-wrapper"><canvas id="chart-etab-milieu"></canvas></div></div>
            <div class="chart-card"><h4><i class="fas fa-tools"></i> Infrastructures (%)</h4><div class="chart-wrapper"><canvas id="chart-etab-infra"></canvas></div></div>
            <div class="chart-card"><h4><i class="fas fa-chart-bar"></i> Par province (Top 10)</h4><div class="chart-wrapper"><canvas id="chart-etab-province"></canvas></div></div>`;

        destroyChart('chart-etab-secteur');
        SIGE.charts['chart-etab-secteur'] = new Chart(document.getElementById('chart-etab-secteur'), {
            type: 'pie',
            data: { labels: ['Public','Privé','Conventionné'], datasets: [{ data: [s.par_secteur.public, s.par_secteur.prive, s.par_secteur.conventionne], backgroundColor: ['#1e88e5','#e53935','#43a047'], borderWidth: 2, borderColor:'#fff' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
        destroyChart('chart-etab-milieu');
        SIGE.charts['chart-etab-milieu'] = new Chart(document.getElementById('chart-etab-milieu'), {
            type: 'doughnut',
            data: { labels: ['Urbain','Semi-urbain','Rural'], datasets: [{ data: [s.par_milieu.urbain, s.par_milieu['semi-urbain']||450, s.par_milieu.rural], backgroundColor: ['#29b6f6','#fb8c00','#43a047'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
        });
        destroyChart('chart-etab-infra');
        SIGE.charts['chart-etab-infra'] = new Chart(document.getElementById('chart-etab-infra'), {
            type: 'bar',
            data: { labels: ['Électricité','Eau potable','Internet','Salles utilisées'], datasets: [{ label: '%', data: [s.infra_pct_electricite, s.infra_pct_eau, s.infra_pct_internet, s.infra_pct_salles], backgroundColor: ['#fb8c00','#1e88e5','#43a047','#29b6f6'], borderRadius: 8 }] },
            options: { ...chartOptions('%'), scales: { y: { max: 100 } } }
        });
        const top10 = data.par_province.slice(0,10);
        destroyChart('chart-etab-province');
        SIGE.charts['chart-etab-province'] = new Chart(document.getElementById('chart-etab-province'), {
            type: 'bar',
            data: { labels: top10.map(p=>p.libelle), datasets: [{ label: 'Établissements', data: top10.map(p=>p.total), backgroundColor: '#1e88e5', borderRadius: 6 }] },
            options: { ...chartOptions(''), indexAxis:'y' }
        });

        document.getElementById('etab-table-body').innerHTML = data.liste.map(e => `
            <tr>
                <td><strong>${escHtml(e.nom)}</strong></td>
                <td>${escHtml(e.province)}</td>
                <td><span class="badge ${e.secteur_key}">${escHtml(e.secteur)}</span></td>
                <td><span class="badge ${e.milieu}">${escHtml(e.milieu)}</span></td>
                <td>
                    ${e.electricite ? '<i class="fas fa-bolt" style="color:var(--orange)" title="Électricité"></i>' : '<i class="fas fa-times" style="color:var(--gray-300)"></i>'}
                    ${e.eau_potable ? ' <i class="fas fa-tint" style="color:var(--blue)" title="Eau"></i>' : ''}
                    ${e.acces_internet ? ' <i class="fas fa-wifi" style="color:var(--green)" title="Internet"></i>' : ''}
                </td>
                <td>${fmtNum(e.nb_salles_classe)}</td>
            </tr>`).join('');
    }).catch(console.error);
};
</script>

<!-- ─── Script Carte Leaflet ─── -->
<script>
/* =========================================================
   CARTE SCOLAIRE LEAFLET — SIGE Burundi
   683 établissements géolocalisés (source: Atlas Coline XLSX)
   ========================================================= */
var SIGE_MAP = {
    map: null,
    clusterGroup: null,
    allFeatures: [],
    filteredFeatures: [],
    currentPage: 1,
    pageSize: 50,
    searchTimer: null,
    initialized: false
};

// Initialiser la carte Leaflet (appelée à la première ouverture de la section)
function initSigeMap() {
    if (SIGE_MAP.initialized) return;
    SIGE_MAP.initialized = true;

    // Créer la carte centrée sur le Burundi
    SIGE_MAP.map = L.map('sige-map', {
        center: [-3.3731, 29.9189],
        zoom: 8,
        zoomControl: true,
        attributionControl: true
    });

    // Fond de carte OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors | SIGE Burundi',
        maxZoom: 18
    }).addTo(SIGE_MAP.map);

    // Groupe de clustering des marqueurs
    SIGE_MAP.clusterGroup = L.markerClusterGroup({
        chunkedLoading: true,
        maxClusterRadius: 50,
        showCoverageOnHover: false,
        iconCreateFunction: function(cluster) {
            var count = cluster.getChildCount();
            var size = count < 10 ? 'small' : count < 50 ? 'medium' : 'large';
            return L.divIcon({
                html: '<div class="cluster-icon cluster-' + size + '">' + count + '</div>',
                className: '',
                iconSize: null
            });
        }
    });
    SIGE_MAP.map.addLayer(SIGE_MAP.clusterGroup);

    // Charger les données GeoJSON
    loadCarteData();
}

// Créer une icône personnalisée selon la couleur
function createMarkerIcon(color, milieu) {
    var isUrban = milieu === 'urbain';
    var shape = isUrban ? 'square' : 'circle';
    return L.divIcon({
        html: '<div class="sige-marker" style="background:' + color + ';' + (isUrban ? 'border-radius:4px' : 'border-radius:50%') + '"></div>',
        className: '',
        iconSize: [14, 14],
        iconAnchor: [7, 7],
        popupAnchor: [0, -8]
    });
}

// Charger les données GeoJSON depuis l'API
function loadCarteData() {
    var params = buildCarteParams();
    var url = (window.SIGE_BASE||'') + '/api/carte.php?format=geojson&' + new URLSearchParams(params).toString();

    fetch(url)
        .then(r => r.json())
        .then(geojson => {
            SIGE_MAP.allFeatures = geojson.features || [];
            renderCarteMarkers(SIGE_MAP.allFeatures);
            updateCarteTable(SIGE_MAP.allFeatures);
            
            // Masquer le loading overlay
            var loadingEl = document.getElementById('map-loading');
            if (loadingEl) loadingEl.style.display = 'none';
        })
        .catch(err => {
            console.error('Erreur chargement carte:', err);
            var loadingEl = document.getElementById('map-loading');
            if (loadingEl) {
                loadingEl.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x" style="color:#e53935;margin-bottom:.5rem"></i><div>Erreur de chargement</div>';
            }
        });

    // Charger les stats pour les filtres et KPIs
    fetch((window.SIGE_BASE||'') + '/api/carte.php?format=stats')
        .then(r => r.json())
        .then(stats => {
            // KPIs
            animCounter(document.getElementById('carte-kpi-total'), stats.total_avec_gps || 0);
            animCounter(document.getElementById('carte-kpi-urbain'), stats.milieux?.urbain || 0);
            animCounter(document.getElementById('carte-kpi-rural'), stats.milieux?.rural || 0);
            animCounter(document.getElementById('carte-kpi-provinces'), stats.provinces?.length || 0);

            // Peupler le select province
            var sel = document.getElementById('carte-province');
            if (sel && stats.provinces) {
                stats.provinces.forEach(p => {
                    var opt = document.createElement('option');
                    opt.value = p.code;
                    opt.textContent = p.nom + ' (' + p.count + ')';
                    sel.appendChild(opt);
                });
            }
        })
        .catch(console.error);
}

// Animation counter simple
function animCounter(el, target) {
    if (!el) return;
    var start = 0, dur = 800, step = 16;
    var inc = target / (dur / step);
    var timer = setInterval(function() {
        start += inc;
        if (start >= target) { start = target; clearInterval(timer); }
        el.textContent = Math.round(start).toLocaleString('fr-FR');
    }, step);
}

// Rendre les marqueurs sur la carte
function renderCarteMarkers(features) {
    SIGE_MAP.clusterGroup.clearLayers();

    var bounds = [];
    features.forEach(function(f) {
        var props = f.properties;
        var coords = f.geometry.coordinates; // [lon, lat]
        var latLng = [coords[1], coords[0]];

        var marker = L.marker(latLng, {
            icon: createMarkerIcon(props.color, props.milieu)
        });

        // Popup enrichi
        marker.bindPopup([
            '<div class="sige-popup">',
            '<div class="sige-popup-title"><i class="fas fa-school"></i> ' + escapeHtml(props.nom) + '</div>',
            '<table class="sige-popup-table">',
            '<tr><td><i class="fas fa-map"></i> Province</td><td><strong>' + escapeHtml(props.province) + '</strong></td></tr>',
            '<tr><td><i class="fas fa-city"></i> Commune</td><td>' + escapeHtml(props.commune) + '</td></tr>',
            '<tr><td><i class="fas fa-mountain"></i> Colline</td><td>' + escapeHtml(props.colline) + '</td></tr>',
            '<tr><td><i class="fas fa-layer-group"></i> Secteur</td><td><span style="background:' + props.color + ';color:#fff;padding:2px 8px;border-radius:10px;font-size:.78rem">' + escapeHtml(props.secteur || 'N/A') + '</span></td></tr>',
            '<tr><td><i class="fas fa-tag"></i> Statut</td><td>' + escapeHtml(props.statut || 'N/A') + '</td></tr>',
            '<tr><td><i class="fas fa-map-pin"></i> Milieu</td><td>' + (props.milieu === 'urbain' ? '<span style="color:#1e88e5">🏙 Urbain</span>' : '<span style="color:#43a047">🌿 Rural</span>') + '</td></tr>',
            props.annee_creation ? '<tr><td><i class="fas fa-calendar"></i> Création</td><td>' + props.annee_creation + '</td></tr>' : '',
            '</table>',
            '<div style="margin-top:.5rem;font-size:.75rem;color:#888"><i class="fas fa-crosshairs"></i> ' + coords[1].toFixed(5) + ', ' + coords[0].toFixed(5) + '</div>',
            '</div>'
        ].join(''), { maxWidth: 280 });

        marker.on('click', function() {
            highlightCarteRow(props.id);
        });

        SIGE_MAP.clusterGroup.addLayer(marker);
        bounds.push(latLng);
    });

    // Ajuster la vue sur les marqueurs (seulement au premier chargement)
    if (bounds.length > 0 && !SIGE_MAP.hasBounded) {
        try { SIGE_MAP.map.fitBounds(L.latLngBounds(bounds).pad(0.05)); } catch(e) {}
        SIGE_MAP.hasBounded = true;
    }

    document.getElementById('carte-count-label').textContent = features.length.toLocaleString('fr-FR') + ' établissement(s) affiché(s)';
}

// Mettre en surbrillance une ligne du tableau
function highlightCarteRow(id) {
    document.querySelectorAll('#carte-table-body tr').forEach(r => r.classList.remove('row-highlight'));
    var row = document.querySelector('#carte-table-body tr[data-id="' + id + '"]');
    if (row) {
        row.classList.add('row-highlight');
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Mettre à jour le tableau de résultats
function updateCarteTable(features) {
    var tbody = document.getElementById('carte-table-body');
    var pag   = document.getElementById('carte-pagination');
    if (!tbody) return;

    SIGE_MAP.filteredFeatures = features;
    SIGE_MAP.currentPage = 1;
    renderCartePage();
}

function renderCartePage() {
    var features = SIGE_MAP.filteredFeatures;
    var tbody = document.getElementById('carte-table-body');
    var pag   = document.getElementById('carte-pagination');
    var page  = SIGE_MAP.currentPage;
    var size  = SIGE_MAP.pageSize;
    var start = (page - 1) * size;
    var pageData = features.slice(start, start + size);

    if (!pageData.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#888">Aucun établissement trouvé avec ces filtres.</td></tr>';
        if (pag) pag.textContent = '';
        return;
    }

    tbody.innerHTML = pageData.map(f => {
        var p = f.properties;
        return '<tr data-id="' + p.id + '" onclick="flyToMarker(' + f.geometry.coordinates[1] + ',' + f.geometry.coordinates[0] + ',' + p.id + ')" style="cursor:pointer">' +
            '<td><strong>' + escapeHtml(p.nom) + '</strong></td>' +
            '<td>' + escapeHtml(p.province) + '</td>' +
            '<td>' + escapeHtml(p.commune) + '</td>' +
            '<td>' + escapeHtml(p.colline) + '</td>' +
            '<td><span style="background:' + p.color + ';color:#fff;padding:2px 8px;border-radius:10px;font-size:.78rem">' + escapeHtml(p.secteur || '—') + '</span></td>' +
            '<td>' + (p.milieu === 'urbain' ? '🏙 Urbain' : '🌿 Rural') + '</td>' +
            '<td style="font-size:.8rem">' + escapeHtml(p.statut || '—') + '</td>' +
            '<td style="font-size:.78rem;color:#888">' + f.geometry.coordinates[1].toFixed(4) + ', ' + f.geometry.coordinates[0].toFixed(4) + '</td>' +
            '</tr>';
    }).join('');

    // Pagination
    var totalPages = Math.ceil(features.length / size);
    if (pag) {
        var info = 'Page ' + page + ' / ' + totalPages + ' · ' + features.length.toLocaleString('fr-FR') + ' résultats';
        var prev = page > 1 ? '<button onclick="carteChangePage(' + (page-1) + ')" style="margin:0 .25rem;padding:.2rem .8rem;border:1px solid #ddd;border-radius:6px;cursor:pointer;background:#fff">‹ Préc.</button>' : '';
        var next = page < totalPages ? '<button onclick="carteChangePage(' + (page+1) + ')" style="margin:0 .25rem;padding:.2rem .8rem;border:1px solid #ddd;border-radius:6px;cursor:pointer;background:#fff">Suiv. ›</button>' : '';
        pag.innerHTML = prev + '<span style="margin:0 .75rem">' + info + '</span>' + next;
    }
}

function carteChangePage(p) {
    SIGE_MAP.currentPage = p;
    renderCartePage();
    document.getElementById('sige-map').scrollIntoView({ behavior:'smooth', block:'nearest' });
}

// Survoler un marqueur depuis le tableau
function flyToMarker(lat, lon, id) {
    if (!SIGE_MAP.map) return;
    SIGE_MAP.map.flyTo([lat, lon], 14, { duration: 0.8 });
    setTimeout(function() { highlightCarteRow(id); }, 900);
}

// Construire les paramètres de filtre
function buildCarteParams() {
    return {
        province: document.getElementById('carte-province')?.value || '',
        secteur: document.getElementById('carte-secteur')?.value || '',
        milieu: document.getElementById('carte-milieu')?.value || '',
        q: document.getElementById('carte-search')?.value || ''
    };
}

// Appliquer les filtres
function applyCarteFilters() {
    if (!SIGE_MAP.initialized) return;
    var params = buildCarteParams();
    var url = (window.SIGE_BASE||'') + '/api/carte.php?format=geojson&' + new URLSearchParams(params).toString();

    // Mise à jour lien export
    var exportBtn = document.getElementById('carte-export-btn');
    if (exportBtn) {
        exportBtn.href = (window.SIGE_BASE||'') + '/api/export.php?module=carte&format=csv&' + new URLSearchParams(params).toString();
    }

    fetch(url)
        .then(r => r.json())
        .then(geojson => {
            var features = geojson.features || [];
            renderCarteMarkers(features);
            updateCarteTable(features);
        })
        .catch(console.error);
}

// Debounce pour la recherche
function debounceCarteSearch() {
    clearTimeout(SIGE_MAP.searchTimer);
    SIGE_MAP.searchTimer = setTimeout(applyCarteFilters, 400);
}

// Réinitialiser les filtres
function resetCarteFilters() {
    document.getElementById('carte-province').value = '';
    document.getElementById('carte-secteur').value = '';
    document.getElementById('carte-milieu').value = '';
    document.getElementById('carte-search').value = '';
    applyCarteFilters();
}

// Échapper HTML pour les popups
function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Surcharger showSection pour initialiser la carte à la demande
var _origShowSection = window.showSection;
window.showSection = function(section) {
    // Appeler la fonction originale (gère .detail-section active + loaders)
    if (typeof _origShowSection === 'function') _origShowSection(section);

    // Mettre à jour la classe active dans la navbar
    document.querySelectorAll('.navbar-menu a[data-section]').forEach(function(a) {
        a.classList.toggle('active', a.dataset.section === section);
    });

    // Initialiser / rafraîchir la carte Leaflet
    if (section === 'carte') {
        setTimeout(function() {
            if (!SIGE_MAP.initialized) {
                initSigeMap();
            } else {
                // La carte existe déjà : recalculer la taille (le div était hidden)
                SIGE_MAP.map.invalidateSize();
            }
        }, 200);
    }
};
</script>

<style>
/* Marqueurs Leaflet personnalisés */
.sige-marker {
    width: 14px; height: 14px;
    border: 2px solid rgba(255,255,255,0.9);
    box-shadow: 0 1px 4px rgba(0,0,0,0.3);
    cursor: pointer;
    transition: transform .15s;
}
.sige-marker:hover { transform: scale(1.4); }

/* Cluster icons */
.cluster-icon {
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%; font-weight: 700; color: #fff;
    border: 2px solid rgba(255,255,255,0.8);
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
}
.cluster-small  { width:30px; height:30px; font-size:.78rem; background:#1e88e5; }
.cluster-medium { width:38px; height:38px; font-size:.85rem; background:#fb8c00; }
.cluster-large  { width:46px; height:46px; font-size:.95rem; background:#e53935; }

/* Popup carte */
.sige-popup { min-width: 220px; }
.sige-popup-title {
    font-weight: 700; font-size: .95rem; color: #1a237e;
    border-bottom: 2px solid #e3f2fd; padding-bottom: .4rem; margin-bottom: .5rem;
}
.sige-popup-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.sige-popup-table td { padding: .2rem .3rem; }
.sige-popup-table td:first-child { color: #666; font-size: .78rem; width: 80px; }
.sige-popup-table td i { margin-right: 4px; color: #1e88e5; }

/* Ligne surlignée dans le tableau */
tr.row-highlight { background: #e3f2fd !important; }
</style>

</body>
</html>
