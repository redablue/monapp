<?php
// dashboard.php
require_once __DIR__ . '/includes/header.php';

$totalClients = 0;
$totalDossiers = 0;
$tachesEnCours = 0;

try {
    $stmtClients = $pdo->query("SELECT COUNT(*) FROM clients");
    $totalClients = $stmtClients->fetchColumn();

    $stmtDossiers = $pdo->query("SELECT COUNT(*) FROM dossiers");
    $totalDossiers = $stmtDossiers->fetchColumn();

    $stmtTaches = $pdo->query("SELECT COUNT(*) FROM taches WHERE statut IN ('a_faire', 'en_cours')");
    $tachesEnCours = $stmtTaches->fetchColumn();

    $stmtLogs = $pdo->query("SELECT l.*, e.prenom, e.nom FROM logs_activite l LEFT JOIN employes e ON l.employe_id = e.id ORDER BY date_heure DESC LIMIT 5");
    $recentLogs = $stmtLogs->fetchAll();

    $stmtDossiersEcheance = $pdo->prepare("SELECT d.*, c.nom_entreprise, td.nom_type FROM dossiers d JOIN clients c ON d.client_id = c.id JOIN types_dossiers td ON d.type_dossier_id = td.id WHERE d.date_echeance BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND d.statut IN ('en_attente', 'en_cours') ORDER BY d.date_echeance ASC LIMIT 5");
    $stmtDossiersEcheance->execute();
    $dossiersEcheanceProche = $stmtDossiersEcheance->fetchAll();

} catch (PDOException $e) {
    echo '<div class="alert alert-danger" role="alert">Erreur lors de la récupération des statistiques : ' . $e->getMessage() . '</div>';
    $totalClients = $totalDossiers = $tachesEnCours = 0;
    $recentLogs = [];
    $dossiersEcheanceProche = [];
}

?>

<h1 class="mb-4">Tableau de Bord <small class="text-muted">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></small></h1>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-users"></i> Clients</h5>
                        <p class="card-text fs-2"><?php echo $totalClients; ?></p>
                    </div>
                    <i class="fas fa-user-friends fa-3x"></i>
                </div>
                <a href="/fidaous/clients/liste.php" class="text-white stretched-link text-decoration-none">Voir les clients <i class="fas fa-arrow-circle-right ms-2"></i></a> </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-folder-open"></i> Dossiers</h5>
                        <p class="card-text fs-2"><?php echo $totalDossiers; ?></p>
                    </div>
                    <i class="fas fa-briefcase fa-3x"></i>
                </div>
                <a href="/fidaous/dossiers/liste.php" class="text-white stretched-link text-decoration-none">Voir les dossiers <i class="fas fa-arrow-circle-right ms-2"></i></a> </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-tasks"></i> Tâches en cours</h5>
                        <p class="card-text fs-2"><?php echo $tachesEnCours; ?></p>
                    </div>
                    <i class="fas fa-clipboard-list fa-3x"></i>
                </div>
                <a href="/fidaous/taches/liste.php" class="text-white stretched-link text-decoration-none">Voir les tâches <i class="fas fa-arrow-circle-right ms-2"></i></a> </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Dossiers à échéance proche</h5>
            </div>
            <div class="card-body">
                <?php if (empty($dossiersEcheanceProche)): ?>
                    <p class="card-text">Aucun dossier avec une échéance proche pour le moment.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($dossiersEcheanceProche as $dossier): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center <?php echo (strtotime($dossier['date_echeance']) < strtotime('today')) ? 'list-group-item-danger' : 'list-group-item-warning'; ?>">
                                <div>
                                    <strong><?php echo htmlspecialchars($dossier['titre']); ?></strong> pour <?php echo htmlspecialchars($dossier['nom_entreprise']); ?>
                                    <br><small class="text-muted">Type: <?php echo htmlspecialchars($dossier['nom_type']); ?></small>
                                </div>
                                <span class="badge bg-secondary rounded-pill">Échéance: <?php echo date('d/m/Y', strtotime($dossier['date_echeance'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Dernières activités</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentLogs)): ?>
                    <p class="card-text">Aucune activité récente.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentLogs as $log): ?>
                            <li class="list-group-item">
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($log['date_heure'])); ?> - </small>
                                <strong><?php echo htmlspecialchars($log['prenom'] . ' ' . $log['nom']); ?></strong>
                                : <?php echo htmlspecialchars($log['description']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>