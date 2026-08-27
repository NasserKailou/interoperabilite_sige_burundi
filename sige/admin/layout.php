<?php
/**
 * SIGE Burundi — Layout AdminLTE (inclure en début de page admin)
 * Usage : include __DIR__ . '/layout.php'; avant le contenu
 *
 * Variables attendues :
 *   $pageTitle  : titre de la page (string)
 *   $pageIcon   : icône Font Awesome (string, ex: 'fas fa-dashboard')
 *   $activePage : identifiant de la page active pour le menu (string)
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin('login.php');
$user = Auth::currentUser();

$pageTitle  = $pageTitle  ?? 'Tableau de bord';
$pageIcon   = $pageIcon   ?? 'fas fa-tachometer-alt';
$activePage = $activePage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Administration SIGE Burundi</title>

    <!-- AdminLTE + Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        body, h1, h2, h3, h4, h5, .brand-text, .nav-link, .content-header h1 {
            font-family: 'Nunito', sans-serif !important;
        }
        /* ── Sidebar couleur SIGE ── */
        .main-sidebar, .sidebar {
            background: linear-gradient(180deg, #1565c0 0%, #0d47a1 100%) !important;
        }
        .brand-link {
            background: rgba(0,0,0,.15) !important;
            border-bottom: 1px solid rgba(255,255,255,.1) !important;
        }
        .brand-text { color: white !important; font-weight: 800 !important; }
        .nav-sidebar .nav-link {
            color: rgba(255,255,255,.85) !important;
            border-radius: 8px !important;
            margin: 2px 8px !important;
            font-size: .875rem;
            font-weight: 600;
        }
        .nav-sidebar .nav-link:hover,
        .nav-sidebar .nav-link.active {
            background: rgba(255,255,255,.15) !important;
            color: white !important;
        }
        .nav-sidebar .nav-link .nav-icon { color: rgba(255,255,255,.7) !important; }
        .nav-sidebar .nav-link.active .nav-icon { color: white !important; }
        .nav-sidebar .nav-header {
            color: rgba(255,255,255,.5) !important;
            font-size: .68rem !important;
            letter-spacing: 1px !important;
            padding: 1rem 1rem .4rem !important;
        }
        /* ── Navbar ── */
        .main-header.navbar {
            background: white !important;
            border-bottom: 3px solid #1e88e5 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,.08) !important;
        }
        .main-header .nav-link { color: #3c4043 !important; }
        /* ── Content ── */
        .content-wrapper { background: #f8f9fa !important; }
        .content-header h1 { font-size: 1.3rem !important; font-weight: 800 !important; color: #3c4043; }
        .content-header .breadcrumb { background: transparent !important; }
        /* ── Cards ── */
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
        .card-header { border-radius: 12px 12px 0 0 !important; font-weight: 700; }
        /* ── Info boxes ── */
        .info-box { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
        .info-box-icon { border-radius: 12px 0 0 12px !important; }
        /* ── Small box ── */
        .small-box { border-radius: 12px !important; }
        .small-box:hover { transform: translateY(-2px); transition: all .2s; }
        /* ── Tables ── */
        .table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
        /* ── Badges ── */
        .badge-sige-mock   { background: #e8f5e9; color: #2e7d32; font-size: .72rem; }
        .badge-sige-api    { background: #e3f2fd; color: #1565c0; font-size: .72rem; }
        .badge-sige-error  { background: #ffebee; color: #b71c1c; font-size: .72rem; }
        /* ── Status indicator ── */
        .connector-status { display: inline-flex; align-items: center; gap: 5px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot.green  { background: #43a047; animation: pulse-green 2s infinite; }
        .dot.red    { background: #e53935; }
        .dot.orange { background: #fb8c00; animation: pulse-orange 2s infinite; }
        @keyframes pulse-green  { 0%,100%{box-shadow:0 0 0 0 rgba(67,160,71,.4)} 50%{box-shadow:0 0 0 6px rgba(67,160,71,0)} }
        @keyframes pulse-orange { 0%,100%{box-shadow:0 0 0 0 rgba(251,140,0,.4)} 50%{box-shadow:0 0 0 6px rgba(251,140,0,0)} }
        /* ── Log entry ── */
        .log-entry { font-size: .8rem; font-family: monospace; border-bottom: 1px solid #f1f3f4; padding: .4rem .5rem; }
        .log-entry:hover { background: #f8f9fa; }
        .log-entry.error   { border-left: 3px solid #e53935; }
        .log-entry.warning { border-left: 3px solid #fb8c00; }
        .log-entry.info    { border-left: 3px solid #1e88e5; }
        /* ── Sidebar brand ── */
        .sidebar-brand-logo {
            width: 36px; height: 36px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

<!-- ─── NAVBAR ─── -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= PUBLIC_BASE_URL ?: '../public/' ?>" class="nav-link text-muted" style="font-size:.85rem">
                <i class="fas fa-external-link-alt"></i> Portail public
            </a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <span class="nav-link">
                <span class="badge badge-sige-mock px-2 py-1 rounded-pill">
                    <i class="fas fa-database"></i> Mode : <?= e(DATA_SOURCE_MODE) ?>
                </span>
            </span>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;background:#1e88e5;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:.7rem;font-weight:700">
                        <?= strtoupper(substr($user['nom'], 0, 2)) ?>
                    </div>
                    <span class="d-none d-sm-inline ml-1" style="font-size:.85rem;font-weight:600">
                        <?= e($user['nom']) ?>
                    </span>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user mr-2"></i> Mon profil
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
                </a>
            </div>
        </li>
    </ul>
</nav>

<!-- ─── SIDEBAR ─── -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="dashboard.php" class="brand-link">
        <div class="sidebar-brand-logo d-inline-block align-top ml-1">
            <i class="fas fa-graduation-cap text-white"></i>
        </div>
        <span class="brand-text font-weight-light ml-2">SIGE Burundi</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex" style="border-bottom:1px solid rgba(255,255,255,.1)">
            <div class="image">
                <div style="width:34px;height:34px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700">
                    <?= strtoupper(substr($user['nom'], 0, 2)) ?>
                </div>
            </div>
            <div class="info ml-2">
                <a href="#" class="d-block text-white" style="font-weight:700;font-size:.85rem">
                    <?= e($user['nom']) ?>
                </a>
                <small class="text-white-50" style="font-size:.72rem">
                    <i class="fas fa-shield-alt mr-1"></i><?= e(ucfirst($user['role'])) ?>
                </small>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                <li class="nav-header">TABLEAU DE BORD</li>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Vue d'ensemble</p>
                    </a>
                </li>

                <li class="nav-header">DONNÉES SIGE</li>
                <li class="nav-item">
                    <a href="eleves.php" class="nav-link <?= $activePage === 'eleves' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Données élèves</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="rh.php" class="nav-link <?= $activePage === 'rh' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chalkboard-teacher"></i>
                        <p>Ressources humaines</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="examens.php" class="nav-link <?= $activePage === 'examens' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Examens &amp; concours</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="etablissements.php" class="nav-link <?= $activePage === 'etablissements' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-school"></i>
                        <p>Établissements</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../public/index.php#carte" class="nav-link" target="_blank">
                        <i class="nav-icon fas fa-map-marked-alt"></i>
                        <p>Carte scolaire <i class="fas fa-external-link-alt fa-xs ml-1 opacity-75"></i></p>
                    </a>
                </li>

                <li class="nav-header">INTEROPÉRABILITÉ</li>
                <li class="nav-item">
                    <a href="connecteurs.php" class="nav-link <?= $activePage === 'connecteurs' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-plug"></i>
                        <p>Connecteurs</p>
                        <span class="badge badge-sige-mock ml-auto rounded-pill px-2">
                            <?= e(DATA_SOURCE_MODE) ?>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="referentiels.php" class="nav-link <?= $activePage === 'referentiels' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Référentiels communs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logs.php" class="nav-link <?= $activePage === 'logs' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <p>Journal des échanges</p>
                    </a>
                </li>

                <li class="nav-header">ADMINISTRATION</li>
                <li class="nav-item">
                    <a href="utilisateurs.php" class="nav-link <?= $activePage === 'utilisateurs' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>Utilisateurs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:#ef9a9a"></i>
                        <p>Déconnexion</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<!-- ─── Contenu (ouverture) ─── -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        <i class="<?= e($pageIcon) ?> mr-2" style="color:#1e88e5"></i>
                        <?= e($pageTitle) ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                        <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
<?php /* Le contenu de la page est injecté ici */ ?>
