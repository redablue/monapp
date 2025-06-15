<?php
// clients/liste.php
require_once __DIR__ . '/../includes/header.php';

if (!hasRequiredRole('gestionnaire')) {
    redirect('/fidaous/dashboard.php');
}

$search = $_GET['search'] ?? '';

$sql = "SELECT c.*, fj.nom_forme, rf.nom_regime, e.prenom, e.nom
        FROM clients c
        LEFT JOIN formes_juridiques fj ON c.forme_juridique_id = fj.id
        LEFT JOIN regimes_fiscaux rf ON c.regime_fiscal_id = rf.id
        LEFT JOIN employes e ON c.employe_id = e.id
        WHERE c.nom_entreprise LIKE :search
        OR c.ice LIKE :search
        OR c.rc LIKE :search
        OR c.patente LIKE :search
        OR c.cnss LIKE :search
        OR c.if_fiscale LIKE :search
        ORDER BY c.nom_entreprise ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%');
$stmt->execute();
$clients = $stmt->fetchAll();

// --- LOGIQUE DE GESTION DE LA SUPPRESSION DE CLIENT ---
if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
    if (hasRequiredRole('administrateur')) {
        $client_id = (int)$_GET['id'];

        try {
            $pdo->beginTransaction();

            $stmtCheckDossiers = $pdo->prepare("SELECT COUNT(*) FROM dossiers WHERE client_id = :client_id");
            $stmtCheckDossiers->bindParam(':client_id', $client_id, PDO::PARAM_INT);
            $stmtCheckDossiers->execute();
            if ($stmtCheckDossiers->fetchColumn() > 0) {
                $_SESSION['message'] = '<div class="alert alert-warning" role="alert">Impossible de supprimer ce client car il a des dossiers associés. Veuillez supprimer ou réaffecter les dossiers avant.</div>';
                $pdo->rollBack();
                redirect('/fidaous/clients/liste.php');
            }
            
            $stmtDelete = $pdo->prepare("DELETE FROM clients WHERE id = :id");
            $stmtDelete->bindParam(':id', $client_id, PDO::PARAM_INT);
            $stmtDelete->execute();

            if ($stmtDelete->rowCount()) {
                logActivity($pdo, 'Suppression client', 'clients', $client_id, 'Client ID ' . $client_id . ' supprimé.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Client supprimé avec succès.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur lors de la suppression du client ou client introuvable.</div>';
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur BD lors de la suppression du client : ' . $e->getMessage() . '</div>';
        }
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Vous n\'avez pas la permission de supprimer des clients.</div>';
    }
    redirect('/fidaous/clients/liste.php');
}
?>

<h1 class="mb-4">Gestion des Clients</h1>

<?php
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<div class="row mb-3">
    <div class="col-md-6">
        <form action="liste.php" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Rechercher un client..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Rechercher</button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasRequiredRole('gestionnaire')): ?>
            <a href="/fidaous/clients/ajouter.php" class="btn btn-success"><i class="fas fa-plus-circle me-1"></i>Ajouter un Client</a>
        <?php endif; ?>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead class="table-dark">
            <tr>
                <th>Nom Entreprise</th>
                <th>ICE</th>
                <th>RC</th>
                <th>Patente</th>
                <th>CNSS</th>
                <th>IF Fiscale</th>
                <th>Forme Juridique</th>
                <th>Régime Fiscal</th>
                <th>Responsable</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clients)): ?>
                <tr>
                    <td colspan="10" class="text-center">Aucun client trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($client['nom_entreprise']); ?></td>
                        <td><?php echo htmlspecialchars($client['ice'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($client['rc'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($client['patente'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($client['cnss'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($client['if_fiscale'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($client['nom_forme'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($client['nom_regime'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="/fidaous/clients/modifier.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info me-1" title="Modifier"><i class="fas fa-edit"></i></a>
                            <?php if (hasRequiredRole('administrateur')): ?>
                                <a href="liste.php?action=supprimer&id=<?php echo $client['id']; ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client et toutes les données associées (dossiers, tâches, documents) ? Cette action est irréversible si la BDD n\'est pas configurée pour CASCADE.');"><i class="fas fa-trash-alt"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>