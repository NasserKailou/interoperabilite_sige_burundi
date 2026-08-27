<?php
/**
 * SIGE Burundi — Admin — Gestion des connecteurs d'interopérabilité
 */
$pageTitle  = 'Connecteurs d\'interopérabilité';
$pageIcon   = 'fas fa-plug';
$activePage = 'connecteurs';
include __DIR__ . '/layout.php';

$connector = ConnectorFactory::getConnector();
$statusTest = $connector->testConnexion();

// Action test depuis formulaire
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $testResult = $connector->testConnexion();
    log_event('info', 'ADMIN', 'Test connecteurs effectué', ['mode' => DATA_SOURCE_MODE]);
}
$csrf = csrf_token();
?>

<!-- ─── Mode actif ─── -->
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-<?= DATA_SOURCE_MODE === 'mock' ? 'success' : 'info' ?> alert-dismissible" style="border-radius:10px;border:none">
            <h5><i class="fas fa-<?= DATA_SOURCE_MODE === 'mock' ? 'database' : 'cloud' ?> mr-2"></i>
                Mode actuel : <strong><?= strtoupper(DATA_SOURCE_MODE) ?></strong>
            </h5>
            <?php if (DATA_SOURCE_MODE === 'mock'): ?>
            <p class="mb-0">Les données sont lues depuis les fichiers JSON locaux (<code>mock_data/</code>).
            Pour activer les API réelles, modifier <code>define('DATA_SOURCE_MODE', 'api')</code> dans <code>includes/config.php</code>.</p>
            <?php else: ?>
            <p class="mb-0">Les données sont récupérées depuis les API réelles. Vérifier la connectivité ci-dessous.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ─── Cartes connecteurs ─── -->
<div class="row">
<?php
$connecteursConfig = [
    [
        'nom'        => 'StatEduc',
        'description'=> 'Système de recensement scolaire pluriannuel (cœur du SIGE)',
        'icon'       => 'fas fa-database',
        'couleur'    => '#1e88e5',
        'fichier_mock'=> 'eleves.json + etablissements.json',
        'endpoint_key'=> 'statEduc',
        'systeme'    => 'statEduc',
    ],
    [
        'nom'        => 'SIGE-RH',
        'description'=> 'Système de gestion des ressources humaines éducatives',
        'icon'       => 'fas fa-users-cog',
        'couleur'    => '#43a047',
        'fichier_mock'=> 'rh.json',
        'endpoint_key'=> 'sige_rh',
        'systeme'    => 'sige_rh',
    ],
    [
        'nom'        => 'Examens & concours',
        'description'=> 'Résultats du Concours National (CN8) et de l\'Examen d\'État',
        'icon'       => 'fas fa-file-alt',
        'couleur'    => '#e53935',
        'fichier_mock'=> 'examens.json',
        'endpoint_key'=> 'examens',
        'systeme'    => 'examens',
    ],
    [
        'nom'        => 'Carte scolaire',
        'description'=> 'Géolocalisation et données générales des établissements',
        'icon'       => 'fas fa-map-marked-alt',
        'couleur'    => '#fb8c00',
        'fichier_mock'=> 'etablissements.json + referentiels.json',
        'endpoint_key'=> 'carte',
        'systeme'    => 'carte',
    ],
    [
        'nom'        => 'Référentiels communs',
        'description'=> 'Provinces, communes, années, secteurs — socle d\'interopérabilité',
        'icon'       => 'fas fa-book',
        'couleur'    => '#8e24aa',
        'fichier_mock'=> 'referentiels.json',
        'endpoint_key'=> 'referentiels',
        'systeme'    => 'referentiels',
    ],
];

foreach ($connecteursConfig as $conn):
    $endpointUrl = API_ENDPOINTS[$conn['endpoint_key']] ?? '';
    $fileKey     = array_key_first(array_filter(
        array_keys($statusTest['fichiers'] ?? []),
        fn($k) => str_starts_with($k, explode(' + ', $conn['fichier_mock'])[0])
    )) ?: '';
    $mockOk = true;
    foreach (array_map('trim', explode('+', $conn['fichier_mock'])) as $f) {
        $f = trim(str_replace(' ', '', $f));
        if (isset($statusTest['fichiers'][$f]) && !$statusTest['fichiers'][$f]['ok']) {
            $mockOk = false;
        }
    }
?>
<div class="col-lg-6 col-xl-4 mb-4">
    <div class="card h-100" style="border-top: 4px solid <?= e($conn['couleur']) ?>">
        <div class="card-body">
            <div class="d-flex align-items-start mb-3">
                <div style="width:48px;height:48px;background:<?= e($conn['couleur']) ?>20;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="<?= e($conn['icon']) ?>" style="color:<?= e($conn['couleur']) ?>;font-size:1.3rem"></i>
                </div>
                <div class="ml-3">
                    <h5 style="font-weight:800;margin-bottom:.25rem"><?= e($conn['nom']) ?></h5>
                    <p style="font-size:.8rem;color:#9aa0a6;margin-bottom:0"><?= e($conn['description']) ?></p>
                </div>
            </div>

            <!-- Mode & statut -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#5f6368">Mode actif</span>
                    <span class="badge badge-sige-<?= DATA_SOURCE_MODE === 'mock' ? 'mock' : 'api' ?> rounded-pill px-2 py-1">
                        <?= strtoupper(DATA_SOURCE_MODE) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#5f6368">Statut</span>
                    <span class="connector-status">
                        <div class="dot <?= DATA_SOURCE_MODE === 'mock' ? 'green' : ($endpointUrl ? 'green' : 'red') ?>"></div>
                        <?php if (DATA_SOURCE_MODE === 'mock'): ?>
                        <small class="text-success font-weight-bold">Opérationnel (mock)</small>
                        <?php elseif ($endpointUrl): ?>
                        <small class="text-success font-weight-bold">Endpoint configuré</small>
                        <?php else: ?>
                        <small class="text-danger font-weight-bold">Non configuré</small>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Fichiers mock -->
            <div class="mb-3 p-2" style="background:#f8f9fa;border-radius:8px">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#9aa0a6;margin-bottom:.35rem">
                    <i class="fas fa-file-code mr-1"></i> Fichiers mock JSON
                </div>
                <?php foreach (array_map('trim', explode('+', $conn['fichier_mock'])) as $f): ?>
                <?php $fClean = trim($f); $fPath = MOCK_DATA_PATH . '/' . $fClean; $fOk = file_exists($fPath); ?>
                <div class="d-flex align-items-center justify-content-between py-1">
                    <code style="font-size:.78rem"><?= e($fClean) ?></code>
                    <div>
                        <?php if ($fOk): ?>
                        <span class="badge badge-success" style="font-size:.68rem">
                            <i class="fas fa-check"></i> <?= number_format(filesize($fPath) / 1024, 1) ?> Ko
                        </span>
                        <?php else: ?>
                        <span class="badge badge-danger" style="font-size:.68rem">
                            <i class="fas fa-times"></i> Introuvable
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Endpoint API réelle -->
            <div class="mb-3 p-2" style="background:#f8f9fa;border-radius:8px">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#9aa0a6;margin-bottom:.35rem">
                    <i class="fas fa-cloud mr-1"></i> API réelle (quand disponible)
                </div>
                <?php if ($endpointUrl): ?>
                <code style="font-size:.78rem;word-break:break-all"><?= e($endpointUrl) ?></code>
                <?php else: ?>
                <span style="font-size:.78rem;color:#fb8c00">
                    <i class="fas fa-clock mr-1"></i> 
                    Non configuré — renseigner dans <code>config.php</code> (API_ENDPOINTS['<?= e($conn['endpoint_key']) ?>'])
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer d-flex gap-2" style="background:#fafafa">
            <button class="btn btn-sm btn-outline-primary" onclick="testConnecteur('<?= e($conn['systeme']) ?>')">
                <i class="fas fa-plug mr-1"></i> Tester
            </button>
            <?php if (DATA_SOURCE_MODE === 'mock'): ?>
            <a href="<?= '../mock_data/' . explode(' ', $conn['fichier_mock'])[0] ?>"
               class="btn btn-sm btn-outline-secondary" download>
                <i class="fas fa-download mr-1"></i> JSON
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- ─── Test global ─── -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-heartbeat mr-2" style="color:#e53935"></i> Diagnostic de santé des sources de données</h3>
                <div class="card-tools">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-sync mr-1"></i> Actualiser le diagnostic
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                <?php foreach ($statusTest['fichiers'] ?? [] as $fichier => $info): ?>
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-center p-2" style="background:#f8f9fa;border-radius:8px;border-left:4px solid <?= $info['ok'] ? '#43a047' : '#e53935' ?>">
                        <i class="fas fa-<?= $info['ok'] ? 'check-circle' : 'times-circle' ?> mr-2" style="color:<?= $info['ok'] ? '#43a047' : '#e53935' ?>;font-size:1.1rem"></i>
                        <div>
                            <code style="font-size:.8rem"><?= e($fichier) ?></code>
                            <div style="font-size:.72rem;color:#9aa0a6">
                                <?= e($info['message']) ?>
                                <?php if ($info['ok']): ?> · <?= number_format($info['taille'] / 1024, 1) ?> Ko<?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <div class="text-center mt-2">
                    <span class="badge badge-<?= $statusTest['ok'] ? 'success' : 'danger' ?> px-3 py-2">
                        <i class="fas fa-<?= $statusTest['ok'] ? 'check' : 'times' ?> mr-1"></i>
                        <?= $statusTest['ok'] ? 'Toutes les sources opérationnelles' : 'Anomalies détectées' ?>
                    </span>
                    <small class="text-muted ml-2">Vérifié le <?= e($statusTest['timestamp']) ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Guide de branchement ─── -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header" style="background:#f8f9fa">
                <h3 class="card-title">
                    <i class="fas fa-book-open mr-2" style="color:#8e24aa"></i>
                    Guide de branchement des API réelles
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-2"><i class="fas fa-list-ol mr-1 text-primary"></i> Étapes de branchement</h6>
                        <ol style="font-size:.88rem;line-height:2">
                            <li>Renseigner les URLs dans <code>includes/config.php</code> → <code>API_ENDPOINTS</code></li>
                            <li>Ajouter les tokens d'authentification → <code>API_TOKENS</code></li>
                            <li>Modifier <code>define('DATA_SOURCE_MODE', 'api')</code></li>
                            <li>Vérifier la connectivité via ce tableau de bord</li>
                            <li>Implémenter les méthodes dans <code>connectors/ApiConnector.php</code></li>
                            <li>Tester chaque endpoint (bouton "Tester" sur chaque carte)</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-2"><i class="fas fa-code mr-1 text-success"></i> Architecture connecteur</h6>
                        <pre style="background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:8px;font-size:.75rem;overflow-x:auto">// config.php
define('DATA_SOURCE_MODE', 'api'); // ← modifier

// API_ENDPOINTS
'statEduc' => 'https://api.statEduc.bi/v1'

// connectors/ApiConnector.php
public function getSyntheseEleves(int $annee): array {
    // POINT DE BRANCHEMENT ↑
    return $this->httpGet('statEduc', '/eleves/synthese',
                          ['annee' => $annee]);
}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testConnecteur(systeme) {
    alert('Test du connecteur "' + systeme + '" : ' +
          (<?= json_encode(DATA_SOURCE_MODE) ?> === 'mock'
           ? 'Fichier JSON local accessible et valide ✓'
           : 'Appel HTTP vers l\'API en cours…'));
}
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
