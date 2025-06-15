<?php
// dossiers/liste.php
require_once __DIR__ . '/../includes/header.php';

if (!hasRequiredRole('collaborateur')) {
    redirect('/fidaous/dashboard.php');
}

$search = $_GET['search'] ?? '';
$client_filter = $_GET['client_id'] ?? '';
$type_dossier_filter = $_GET['type_dossier_id'] ?? '';
$status_filter = $_GET['statut'] ?? 'all';
$echeance_filter = $_GET['echeance'] ?? 'all';

$clients_list = [];
try {
    $stmtClients = $pdo->query("SELECT id, nom_entreprise FROM clients ORDER BY nom_entreprise ASC");
    $clients_list = $stmtClients->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur si nécessaire
}

$types_dossiers_list = [];
try {
    $stmtTypes = $pdo->query("SELECT id, nom_type FROM types_dossiers ORDER BY nom_type ASC");
    $types_dossiers_list = $stmtTypes->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur si nécessaire
}

$sql = "SELECT d.*, c.nom_entreprise, td.nom_type, e.prenom AS employe_prenom, e.nom AS employe_nom
        FROM dossiers d
        JOIN clients c ON d.client_id = c.id
        JOIN types_dossiers td ON d.type_dossier_id = td.id
        LEFT JOIN employes e ON d.employe_responsable_id = e.id
        WHERE (d.titre LIKE :search OR d.description LIKE :search OR c.nom_entreprise LIKE :search)";

if (!empty($client_filter)) {
    $sql .= " AND d.client_id = :client_filter";
}
if (!empty($type_dossier_filter)) {
    $sql .= " AND d.type_dossier_id = :type_dossier_filter";
}
if ($status_filter != 'all') {
    $sql .= " AND d.statut = :status_filter";
}

if ($echeance_filter == 'proche') {
    $sql .= " AND d.date_echeance BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND d.statut IN ('en_attente', 'en_cours')";
} elseif ($echeance_filter == 'retard') {
    $sql .= " AND d.date_echeance < CURDATE() AND d.statut IN ('en_attente', 'en_cours')";
}

if ($_SESSION['user_role'] == 'collaborateur') {
    $sql .= " AND (d.employe_responsable_id = :current_user_id OR c.employe_id = :current_user_id)";
}

$sql .= " ORDER BY d.date_echeance ASC, d.date_creation DESC";


$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%');
if (!empty($client_filter)) {
    $stmt->bindParam(':client_filter', $client_filter, PDO::PARAM_INT);
}
if (!empty($type_dossier_filter)) {
    $stmt->bindParam(':type_dossier_filter', $type_dossier_filter, PDO::PARAM_INT);
}
if ($status_filter != 'all') {
    $stmt->bindParam(':status_filter', $status_filter);
}
if ($_SESSION['user_role'] == 'collaborateur') {
    $stmt->bindParam(':current_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}
$stmt->execute();
$dossiers = $stmt->fetchAll();

// Gestion de la suppression de dossier
if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
    if (hasRequiredRole('gestionnaire')) {
        $dossier_id = (int)$_GET['id'];
        try {
            $stmtDelete = $pdo->prepare("DELETE FROM dossiers WHERE id = :id");
            $stmtDelete->bindParam(':id', $dossier_id, PDO::PARAM_INT);
            $stmtDelete->execute();

            if ($stmtDelete->rowCount()) {
                logActivity($pdo, 'Suppression dossier', 'dossiers', $dossier_id, 'Dossier ' . $dossier_id . ' supprimé.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Dossier supprimé avec succès.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur lors de la suppression du dossier ou dossier introuvable.</div>';
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur BD lors de la suppression du dossier : ' . $e->getMessage() . '</div>';
        }
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Vous n\'avez pas la permission de supprimer des dossiers.</div>';
    }
    redirect('/fidaous/dossiers/liste.php');
}
?>

<h1 class="mb-4">Gestion des Dossiers</h1>

<?php
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<div class="card p-3 mb-4">
    <h5 class="card-title mb-3"><i class="fas fa-filter me-2"></i>Filtres et Recherche</h5>
    <form action="liste.php" method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Rechercher par titre, description, client..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="client_id" class="form-select">
                <option value="">Tous les clients</option>
                <?php foreach ($clients_list as $client): ?>
                    <option value="<?php echo $client['id']; ?>" <?php echo ($client_filter == $client['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['nom_entreprise']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="type_dossier_id" class="form-select">
                <option value="">Tous les types de dossiers</option>
                <?php foreach ($types_dossiers_list as $type): ?>
                    <option value="<?php echo $type['id']; ?>" <?php echo ($type_dossier_filter == $type['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['nom_type']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="statut" class="form-select">
                <option value="all" <?php echo ($status_filter == 'all') ? 'selected' : ''; ?>>Tous les statuts</option>
                <option value="en_attente" <?php echo ($status_filter == 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                <option value="en_cours" <?php echo ($status_filter == 'en_cours') ? 'selected' : ''; ?>>En cours</option>
                <option value="termine" <?php echo ($status_filter == 'termine') ? 'selected' : ''; ?>>Terminé</option>
                <option value="en_retard" <?php echo ($status_filter == 'en_retard') ? 'selected' : ''; ?>>En retard</option>
                <option value="annule" <?php echo ($status_filter == 'annule') ? 'selected' : ''; ?>>Annulé</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="echeance" class="form-select">
                <option value="all" <?php echo ($echeance_filter == 'all') ? 'selected' : ''; ?>>Toutes les échéances</option>
                <option value="proche" <?php echo ($echeance_filter == 'proche') ? 'selected' : ''; ?>>Échéance proche (7 jours)</option>
                <option value="retard" <?php echo ($echeance_filter == 'retard') ? 'selected' : ''; ?>>En retard</option>
            </select>
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Appliquer Filtres</button>
        </div>
        <div class="col-md-auto">
            <?php if (hasRequiredRole('gestionnaire')): ?>
                <a href="/fidaous/dossiers/ajouter.php" class="btn btn-success"><i class="fas fa-plus-circle me-1"></i>Ajouter un Dossier</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead class="table-dark">
            <tr>
                <th>Titre</th>
                <th>Client</th>
                <th>Type</th>
                <th>Échéance</th>
                <th>Statut</th>
                <th>Responsable</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($dossiers)): ?>
                <tr>
                    <td colspan="7" class="text-center">Aucun dossier trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($dossiers as $dossier):
                    $row_class = '';
                    if ($dossier['statut'] != 'termine' && strtotime($dossier['date_echeance']) < strtotime('today')) {
                        $row_class = 'table-danger';
                    } elseif ($dossier['statut'] != 'termine' && strtotime($dossier['date_echeance']) <= strtotime('+7 days')) {
                        $row_class = 'table-warning';
                    }
                ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td><?php echo htmlspecialchars($dossier['titre']); ?></td>
                        <td><?php echo htmlspecialchars($dossier['nom_entreprise']); ?></td>
                        <td><?php echo htmlspecialchars($dossier['nom_type']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($dossier['date_echeance'])); ?></td>
                        <td>
                            <?php
                            $badge_class = '';
                            switch ($dossier['statut']) {
                                case 'en_attente': $badge_class = 'bg-secondary'; break;
                                case 'en_cours': $badge_class = 'bg-primary'; break;
                                case 'termine': $badge_class = 'bg-success'; break;
                                case 'en_retard': $badge_class = 'bg-danger'; break;
                                case 'annule': $badge_class = 'bg-dark'; break;
                                default: $badge_class = 'bg-info'; break;
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($dossier['statut']))); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($dossier['employe_prenom'] . ' ' . $dossier['employe_nom'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="/fidaous/dossiers/modifier.php?id=<?php echo $dossier['id']; ?>" class="btn btn-sm btn-info me-1" title="Modifier"><i class="fas fa-edit"></i></a>
                            <?php if (hasRequiredRole('gestionnaire')): ?>
                                <a href="liste.php?action=supprimer&id=<?php echo $dossier['id']; ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce dossier et toutes les tâches/documents associés ?');"><i class="fas fa-trash-alt"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>