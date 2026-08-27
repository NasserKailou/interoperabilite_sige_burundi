<?php
/**
 * SIGE Burundi — Admin — Gestion des utilisateurs
 */
$pageTitle  = 'Gestion des utilisateurs';
$pageIcon   = 'fas fa-users-cog';
$activePage = 'utilisateurs';
include __DIR__ . '/layout.php';

Auth::requireRole('admin', 'dashboard.php');

// Données mock d'utilisateurs (en prod → requête DB)
$utilisateurs = [
    ['id'=>1, 'nom'=>'Administrateur SIGE', 'email'=>'admin@sige.bi', 'role'=>'superadmin', 'actif'=>1, 'derniere_connexion'=>date('Y-m-d H:i:s')],
    ['id'=>2, 'nom'=>'Nkurunziza Jean',     'email'=>'jean.nk@mineac.bi', 'role'=>'admin',      'actif'=>1, 'derniere_connexion'=>date('Y-m-d', strtotime('-2 days'))],
    ['id'=>3, 'nom'=>'Hakizimana Marie',    'email'=>'marie.hk@mineac.bi','role'=>'editeur',    'actif'=>1, 'derniere_connexion'=>date('Y-m-d', strtotime('-1 day'))],
    ['id'=>4, 'nom'=>'Ndayishimiye Paul',   'email'=>'paul.nd@mineac.bi', 'role'=>'lecteur',    'actif'=>1, 'derniere_connexion'=>date('Y-m-d', strtotime('-5 days'))],
    ['id'=>5, 'nom'=>'Uwimana Christine',   'email'=>'christine@sige.bi', 'role'=>'lecteur',    'actif'=>0, 'derniere_connexion'=>null],
];
$csrf = csrf_token();
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i> Liste des utilisateurs</h3>
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">
                    <i class="fas fa-plus mr-1"></i> Nouvel utilisateur
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($utilisateurs as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div style="width:32px;height:32px;background:#1e88e5;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:.7rem;font-weight:700;flex-shrink:0">
                                        <?= strtoupper(substr($u['nom'], 0, 2)) ?>
                                    </div>
                                    <strong class="ml-2"><?= e($u['nom']) ?></strong>
                                </div>
                            </td>
                            <td><?= e($u['email']) ?></td>
                            <td>
                                <?php
                                $roleColors = ['superadmin'=>'danger','admin'=>'warning','editeur'=>'info','lecteur'=>'secondary'];
                                $roleCls = $roleColors[$u['role']] ?? 'secondary';
                                ?>
                                <span class="badge badge-<?= $roleCls ?>">
                                    <?= e(ucfirst($u['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $u['actif'] ? 'success' : 'secondary' ?>">
                                    <i class="fas fa-<?= $u['actif'] ? 'check' : 'times' ?> mr-1"></i>
                                    <?= $u['actif'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td>
                                <?= $u['derniere_connexion'] ? e($u['derniere_connexion']) : '<em class="text-muted">Jamais</em>' ?>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-outline-primary mr-1" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($u['id'] != 1): // Protéger le compte admin ?>
                                <button class="btn btn-xs btn-outline-danger" title="Désactiver">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Matrice des rôles ─── -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i> Matrice des permissions</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" style="font-size:.85rem">
                        <thead class="thead-dark">
                            <tr>
                                <th>Fonctionnalité</th>
                                <th class="text-center">Lecteur</th>
                                <th class="text-center">Éditeur</th>
                                <th class="text-center">Admin</th>
                                <th class="text-center">Super Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $check = '<i class="fas fa-check-circle text-success"></i>';
                        $cross = '<i class="fas fa-times-circle text-muted"></i>';
                        $perms = [
                            ['Consulter le portail public',           $check,$check,$check,$check],
                            ['Accéder au tableau de bord admin',     $check,$check,$check,$check],
                            ['Consulter les données SIGE',           $check,$check,$check,$check],
                            ['Exporter les données',                  $cross,$check,$check,$check],
                            ['Gérer les connecteurs',                 $cross,$cross,$check,$check],
                            ['Consulter les logs',                    $cross,$cross,$check,$check],
                            ['Gérer les référentiels',                $cross,$cross,$check,$check],
                            ['Gérer les utilisateurs',                $cross,$cross,$check,$check],
                            ['Configurer les API réelles',           $cross,$cross,$cross,$check],
                            ['Supprimer des données',                 $cross,$cross,$cross,$check],
                        ];
                        foreach ($perms as [$feature, $l, $e, $a, $sa]):
                        ?>
                        <tr>
                            <td><?= $feature ?></td>
                            <td class="text-center"><?= $l ?></td>
                            <td class="text-center"><?= $e ?></td>
                            <td class="text-center"><?= $a ?></td>
                            <td class="text-center"><?= $sa ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal ajout utilisateur -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;overflow:hidden">
            <div class="modal-header" style="background:linear-gradient(135deg,#1565c0,#1e88e5);color:white">
                <h5 class="modal-title text-white">
                    <i class="fas fa-user-plus mr-2"></i> Nouvel utilisateur
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="utilisateurs_save.php">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input type="text" name="nom" class="form-control" required placeholder="Prénom Nom">
                    </div>
                    <div class="form-group">
                        <label>Adresse email</label>
                        <input type="email" name="email" class="form-control" required placeholder="prenom.nom@mineac.bi">
                    </div>
                    <div class="form-group">
                        <label>Rôle</label>
                        <select name="role" class="form-control">
                            <option value="lecteur">Lecteur</option>
                            <option value="editeur">Éditeur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe provisoire</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                        <small class="text-muted">Minimum 8 caractères</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Créer l'utilisateur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
