<?php
/**
 * SIGE Burundi — Admin — Journal des échanges
 */
$pageTitle  = 'Journal des échanges';
$pageIcon   = 'fas fa-list-alt';
$activePage = 'logs';
include __DIR__ . '/layout.php';

// Lire les logs du jour
$logFile   = LOGS_PATH . '/sige_' . date('Y-m-d') . '.log';
$logEntries = [];
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logEntries = array_reverse(array_slice($lines, -200)); // 200 dernières lignes
}

// Simuler des entrées mock si le fichier est vide
if (empty($logEntries)) {
    $now = date('Y-m-d H:i:s');
    $logEntries = [
        "[$now] [INFO] [MockConnector] Fichier JSON chargé : referentiels.json",
        "[$now] [INFO] [MockConnector] Fichier JSON chargé : eleves.json",
        "[$now] [INFO] [MockConnector] Fichier JSON chargé : rh.json",
        "[$now] [INFO] [MockConnector] Fichier JSON chargé : examens.json",
        "[$now] [INFO] [MockConnector] Fichier JSON chargé : etablissements.json",
        "[$now] [INFO] [ConnectorFactory] Connecteur initialisé : mock",
        "[$now] [INFO] [AUTH] Mode démo — session initialisée",
    ];
}
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-stream mr-2"></i> Flux d'échanges récents</h3>
                <div>
                    <span class="badge badge-info mr-2">
                        Fichier : sige_<?= date('Y-m-d') ?>.log
                    </span>
                    <span class="badge badge-secondary"><?= count($logEntries) ?> entrées</span>
                </div>
            </div>
            <div class="card-body p-0" style="max-height:520px;overflow-y:auto">
                <?php if (empty($logEntries)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p>Aucune entrée de journal pour aujourd'hui.</p>
                </div>
                <?php else: ?>
                <?php foreach ($logEntries as $entry):
                    $level = 'info';
                    if (str_contains($entry, '[ERROR]')) $level = 'error';
                    elseif (str_contains($entry, '[WARNING]')) $level = 'warning';
                    elseif (str_contains($entry, '[DEBUG]')) $level = 'debug';
                ?>
                <div class="log-entry <?= $level ?>">
                    <?= e($entry) ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Simulation flux inter-systèmes -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i> Flux d'interopérabilité — Simulation</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Horodatage</th>
                            <th>Source</th>
                            <th>Cible</th>
                            <th>Opération</th>
                            <th>Statut</th>
                            <th>Durée</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $fluxSimules = [
                        ['StatEduc', 'Interop', 'Synchronisation référentiels', 'success', '45ms'],
                        ['SIGE-RH',  'Interop', 'Chargement personnel par établissement', 'success', '128ms'],
                        ['StatEduc', 'Interop', 'Récupération effectifs 2028/2029', 'success', '89ms'],
                        ['Examens',  'Interop', 'Résultats CN8 — toutes provinces', 'success', '67ms'],
                        ['Carte',    'Interop', 'Données établissements', 'success', '52ms'],
                        ['Interop',  'Admin',   'Génération tableau de bord', 'success', '12ms'],
                        ['StatEduc', 'RH',      'Contrôle cohérence code_etab', 'warning', '—'],
                        ['API réelle','Interop', 'Endpoint non configuré', 'error', '—'],
                    ];
                    $time = time();
                    foreach ($fluxSimules as $i => [$src, $cible, $op, $status, $duree]):
                        $ts = date('Y-m-d H:i:s', $time - $i * 180);
                    ?>
                    <tr>
                        <td><small class="text-muted"><?= $ts ?></small></td>
                        <td><span class="badge badge-info"><?= e($src) ?></span></td>
                        <td><span class="badge badge-secondary"><?= e($cible) ?></span></td>
                        <td style="font-size:.85rem"><?= e($op) ?></td>
                        <td>
                            <span class="badge badge-<?= ['success'=>'success','warning'=>'warning','error'=>'danger'][$status] ?>">
                                <i class="fas fa-<?= ['success'=>'check','warning'=>'exclamation','error'=>'times'][$status] ?> mr-1"></i>
                                <?= ucfirst($status) ?>
                            </span>
                        </td>
                        <td><small><?= e($duree) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
