<?php
// admin/regimes_fiscaux.php
require_once __DIR__ . '/../includes/header.php';

// Seuls les administrateurs peuvent accéder à cette page
if (!hasRequiredRole('administrateur')) {
    redirect('/fidaous/dashboard.php'); // Chemin mis à jour
}

$message = '';
$action = $_GET['action'] ?? 'liste';
$id = (int)($_GET['id'] ?? 0);
$regime_fiscal = null;

// Gérer les actions (Ajout, Modification, Suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'ajouter_submit') {
        $nom_regime = trim($_POST['nom_regime'] ?? '');
        $description = trim($_POST['description'] ?? null);

        if (empty($nom_regime)) {
            $message = '<div class="alert alert-danger" role="alert">Le nom du régime fiscal est obligatoire.</div>';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO regimes_fiscaux (nom_regime, description) VALUES (:nom_regime, :description)");
                $stmt->bindParam(':nom_regime', $nom_regime);
                $stmt->bindParam(':description', $description);
                $stmt->execute();
                $last_id = $pdo->lastInsertId();
                logActivity($pdo, 'Création régime fiscal', 'regimes_fiscaux', $last_id, 'Régime fiscal "' . $nom_regime . '" ajouté.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Régime fiscal ajouté avec succès !</div>';
                redirect('/fidaous/admin/regimes_fiscaux.php'); // Chemin mis à jour
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = '<div class="alert alert-danger" role="alert">Ce régime fiscal existe déjà.</div>';
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Erreur lors de l\'ajout : ' . $e->getMessage() . '</div>';
                }
            }
        }
    } elseif ($action === 'modifier_submit') {
        $id = (int)($_POST['id'] ?? 0);
        $nom_regime = trim($_POST['nom_regime'] ?? '');
        $description = trim($_POST['description'] ?? null);

        if (empty($nom_regime)) {
            $message = '<div class="alert alert-danger" role="alert">Le nom du régime fiscal est obligatoire.</div>';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE regimes_fiscaux SET nom_regime = :nom_regime, description = :description WHERE id = :id");
                $stmt->bindParam(':nom_regime', $nom_regime);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount()) {
                    logActivity($pdo, 'Modification régime fiscal', 'regimes_fiscaux', $id, 'Régime fiscal ID ' . $id . ' modifié.');
                    $_SESSION['message'] = '<div class="alert alert-success" role="alert">Régime fiscal mis à jour avec succès !</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-info" role="alert">Aucune modification apportée ou régime fiscal introuvable.</div>';
                }
                redirect('/fidaous/admin/regimes_fiscaux.php'); // Chemin mis à jour
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = '<div class="alert alert-danger" role="alert">Ce régime fiscal existe déjà.</div>';
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Erreur lors de la mise à jour : ' . $e->getMessage() . '</div>';
                }
            }
        }
    }
} elseif ($action === 'supprimer' && $id > 0) {
    try {
        // Vérifier si le régime fiscal est utilisé par des clients
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE regime_fiscal_id = :id");
        $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtCheck->execute();
        if ($stmtCheck->fetchColumn() > 0) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Impossible de supprimer ce régime fiscal car il est associé à des clients.</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM regimes_fiscaux WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount()) {
                logActivity($pdo, 'Suppression régime fiscal', 'regimes_fiscaux', $id, 'Régime fiscal ID ' . $id . ' supprimé.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Régime fiscal supprimé avec succès.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur lors de la suppression ou régime fiscal introuvable.</div>';
            }
        }
        redirect('/fidaous/admin/regimes_fiscaux.php'); // Chemin mis à jour
    } catch (PDOException $e) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur BD lors de la suppression : ' . $e->getMessage() . '</div>';
        redirect('/fidaous/admin/regimes_fiscaux.php'); // Chemin mis à jour
    }
}

// Charger les données pour l'affichage ou le pré-remplissage du formulaire
try {
    if ($action === 'modifier' && $id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM regimes_fiscaux WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $regime_fiscal = $stmt->fetch();
        if (!$regime_fiscal) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Régime fiscal introuvable.</div>';
            redirect('/fidaous/admin/regimes_fiscaux.php'); // Chemin mis à jour
        }
    }
    $stmtListe = $pdo->query("SELECT * FROM regimes_fiscaux ORDER BY nom_regime ASC");
    $regimes_fiscaux_liste = $stmtListe->fetchAll();
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger" role="alert">Erreur lors du chargement des données : ' . $e->getMessage() . '</div>';
    $regimes_fiscaux_liste = [];
}
?>

<h1 class="mb-4">Gestion des Régimes Fiscaux</h1>

<?php
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}
echo $message; // Afficher les messages d'erreur immédiats du POST
?>

<div class="card p-4 mb-4">
    <h3 class="card-title"><?php echo ($action === 'modifier' && $regime_fiscal) ? 'Modifier le Régime Fiscal' : 'Ajouter un Nouveau Régime Fiscal'; ?></h3>
    <form action="regimes_fiscaux.php?action=<?php echo ($action === 'modifier' && $regime_fiscal) ? 'modifier_submit' : 'ajouter_submit'; ?>" method="POST">
        <?php if ($action === 'modifier' && $regime_fiscal): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($regime_fiscal['id']); ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="nom_regime" class="form-label">Nom du Régime Fiscal <span class="text-danger">*</span> :</label>
            <input type="text" class="form-control" id="nom_regime" name="nom_regime" required value="<?php echo htmlspecialchars($regime_fiscal['nom_regime'] ?? $_POST['nom_regime'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description :</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($regime_fiscal['description'] ?? $_POST['description'] ?? ''); ?></textarea>
        </div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>
                <?php echo ($action === 'modifier' && $regime_fiscal) ? 'Mettre à jour' : 'Ajouter'; ?>
            </button>
            <?php if ($action === 'modifier' && $regime_fiscal): ?>
                <a href="regimes_fiscaux.php" class="btn btn-secondary ms-2"><i class="fas fa-times-circle me-1"></i>Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h3 class="mt-4 mb-3">Liste des Régimes Fiscaux</h3>
<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($regimes_fiscaux_liste)): ?>
                <tr>
                    <td colspan="4" class="text-center">Aucun régime fiscal trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($regimes_fiscaux_liste as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                        <td><?php echo htmlspecialchars($item['nom_regime']); ?></td>
                        <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="regimes_fiscaux.php?action=modifier&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info me-1" title="Modifier"><i class="fas fa-edit"></i></a>
                            <a href="regimes_fiscaux.php?action=supprimer&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce régime fiscal ? Cela n\'est possible que si aucun client n\'y est associé.');"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>