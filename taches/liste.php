<?php
// employes/liste.php
require_once __DIR__ . '/../includes/header.php';

if (!hasRequiredRole('administrateur')) {
    redirect('/fidaous/dashboard.php');
}

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

$sql = "SELECT id, nom, prenom, email, role, poste, actif FROM employes WHERE (nom LIKE :search OR prenom LIKE :search OR email LIKE :search OR poste LIKE :search)";

if ($status_filter == 'active') {
    $sql .= " AND actif = TRUE";
} elseif ($status_filter == 'inactive') {
    $sql .= " AND actif = FALSE";
}

$sql .= " ORDER BY nom ASC, prenom ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%');
$stmt->execute();
$employes = $stmt->fetchAll();

// Gestion de l'activation/désactivation de l'employé
if (isset($_GET['action']) && ($_GET['action'] == 'desactiver' || $_GET['action'] == 'activer') && isset($_GET['id'])) {
    if (hasRequiredRole('administrateur')) {
        $employe_id = (int)$_GET['id'];
        $new_status = ($_GET['action'] == 'activer') ? TRUE : FALSE;
        $action_label = ($_GET['action'] == 'activer') ? 'activé' : 'désactivé';

        if ($employe_id == $_SESSION['user_id'] && $new_status == FALSE) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Vous ne pouvez pas désactiver votre propre compte.</div>';
            redirect('/fidaous/employes/liste.php');
        }

        try {
            $stmtUpdate = $pdo->prepare("UPDATE employes SET actif = :actif WHERE id = :id");
            $stmtUpdate->bindParam(':actif', $new_status, PDO::PARAM_BOOL);
            $stmtUpdate->bindParam(':id', $employe_id, PDO::PARAM_INT);
            $stmtUpdate->execute();

            if ($stmtUpdate->rowCount()) {
                logActivity($pdo, 'Statut employé modifié', 'employes', $employe_id, 'Employé ' . $employe_id . ' ' . $action_label . '.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Employé ' . $action_label . ' avec succès.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur lors de la mise à jour du statut de l\'employé ou employé introuvable.</div>';
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur BD lors de la mise à jour du statut : ' . $e->getMessage() . '</div>';
        }
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Vous n\'avez pas la permission de modifier le statut des employés.</div>';
    }
    redirect('/fidaous/employes/liste.php');
}

?>

<h1 class="mb-4">Gestion des Employés</h1>

<?php
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<div class="row mb-3">
    <div class="col-md-7">
        <form action="liste.php" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Rechercher un employé..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status" class="form-select me-2">
                <option value="all" <?php echo ($status_filter == 'all') ? 'selected' : ''; ?>>Tous les statuts</option>
                <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Actifs</option>
                <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactifs</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Rechercher</button>
        </form>
    </div>
    <div class="col-md-5 text-end">
        <?php if (hasRequiredRole('administrateur')): ?>
            <a href="/fidaous/employes/ajouter.php" class="btn btn-success"><i class="fas fa-user-plus me-1"></i>Ajouter un Employé</a>
        <?php endif; ?>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead class="table-dark">
            <tr>
                <th>Nom Complet</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Poste</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($employes)): ?>
                <tr>
                    <td colspan="6" class="text-center">Aucun employé trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($employes as $employe): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employe['prenom'] . ' ' . $employe['nom']); ?></td>
                        <td><?php echo htmlspecialchars($employe['email']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($employe['role'])); ?></td>
                        <td><?php echo htmlspecialchars($employe['poste'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($employe['actif']): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/fidaous/employes/modifier.php?id=<?php echo $employe['id']; ?>" class="btn btn-sm btn-info me-1" title="Modifier"><i class="fas fa-edit"></i></a>
                            <?php if (hasRequiredRole('administrateur')): ?>
                                <?php if ($employe['actif']): ?>
                                    <a href="liste.php?action=desactiver&id=<?php echo $employe['id']; ?>" class="btn btn-sm btn-warning" title="Désactiver" onclick="return confirm('Êtes-vous sûr de vouloir désactiver cet employé ? Il ne pourra plus se connecter.');"><i class="fas fa-user-slash"></i></a>
                                <?php else: ?>
                                    <a href="liste.php?action=activer&id=<?php echo $employe['id']; ?>" class="btn btn-sm btn-success" title="Activer" onclick="return confirm('Êtes-vous sûr de vouloir activer cet employé ?');"><i class="fas fa-user-check"></i></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>