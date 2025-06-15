<?php
// admin/types_dossiers.php
require_once __DIR__ . '/../includes/header.php';

// Seuls les administrateurs peuvent accéder à cette page
if (!hasRequiredRole('administrateur')) {
    redirect('/ton_projet_gestion/dashboard.php');
}

$message = '';
$action = $_GET['action'] ?? 'liste';
$id = (int)($_GET['id'] ?? 0);
$type_dossier = null;

// Gérer les actions (Ajout, Modification, Suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'ajouter_submit') {
        $nom_type = trim($_POST['nom_type'] ?? '');
        $description = trim($_POST['description'] ?? null);

        if (empty($nom_type)) {
            $message = '<div class="alert alert-danger" role="alert">Le nom du type de dossier est obligatoire.</div>';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO types_dossiers (nom_type, description) VALUES (:nom_type, :description)");
                $stmt->bindParam(':nom_type', $nom_type);
                $stmt->bindParam(':description', $description);
                $stmt->execute();
                $last_id = $pdo->lastInsertId();
                logActivity($pdo, 'Création type dossier', 'types_dossiers', $last_id, 'Type de dossier "' . $nom_type . '" ajouté.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Type de dossier ajouté avec succès !</div>';
                redirect('/ton_projet_gestion/admin/types_dossiers.php');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = '<div class="alert alert-danger" role="alert">Ce type de dossier existe déjà.</div>';
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Erreur lors de l\'ajout : ' . $e->getMessage() . '</div>';
                }
            }
        }
    } elseif ($action === 'modifier_submit') {
        $id = (int)($_POST['id'] ?? 0);
        $nom_type = trim($_POST['nom_type'] ?? '');
        $description = trim($_POST['description'] ?? null);

        if (empty($nom_type)) {
            $message = '<div class="alert alert-danger" role="alert">Le nom du type de dossier est obligatoire.</div>';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE types_dossiers SET nom_type = :nom_type, description = :description WHERE id = :id");
                $stmt->bindParam(':nom_type', $nom_type);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount()) {
                    logActivity($pdo, 'Modification type dossier', 'types_dossiers', $id, 'Type de dossier ID ' . $id . ' modifié.');
                    $_SESSION['message'] = '<div class="alert alert-success" role="alert">Type de dossier mis à jour avec succès !</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-info" role="alert">Aucune modification apportée ou type de dossier introuvable.</div>';
                }
                redirect('/ton_projet_gestion/admin/types_dossiers.php');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = '<div class="alert alert-danger" role="alert">Ce type de dossier existe déjà.</div>';
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Erreur lors de la mise à jour : ' . $e->getMessage() . '</div>';
                }
            }
        }
    }
} elseif ($action === 'supprimer' && $id > 0) {
    try {
        // Vérifier si le type de dossier est utilisé par des dossiers
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM dossiers WHERE type_dossier_id = :id");
        $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtCheck->execute();
        if ($stmtCheck->fetchColumn() > 0) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Impossible de supprimer ce type de dossier car il est associé à des dossiers existants.</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM types_dossiers WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount()) {
                logActivity($pdo, 'Suppression type dossier', 'types_dossiers', $id, 'Type de dossier ID ' . $id . ' supprimé.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Type de dossier supprimé avec succès.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur lors de la suppression ou type de dossier introuvable.</div>';
            }
        }
        redirect('/ton_projet_gestion/admin/types_dossiers.php');
    } catch (PDOException $e) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur BD lors de la suppression : ' . $e->getMessage() . '</div>';
        redirect('/ton_projet_gestion/admin/types_dossiers.php');
    }
}

// Charger les données pour l'affichage ou le pré-remplissage du formulaire
try {
    if ($action === 'modifier' && $id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM types_dossiers WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $type_dossier = $stmt->fetch();
        if (!$type_dossier) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Type de dossier introuvable.</div>';
            redirect('/ton_projet_gestion/admin/types_dossiers.php');
        }
    }
    $stmtListe = $pdo->query("SELECT * FROM types_dossiers ORDER BY nom_type ASC");
    $types_dossiers_liste = $stmtListe->fetchAll();
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger" role="alert">Erreur lors du chargement des données : ' . $e->getMessage() . '</div>';
    $types_dossiers_liste = [];
}
?>

<h1 class="mb-4">Gestion des Types de Dossiers</h1>

<?php
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}
echo $message; // Afficher les messages d'erreur immédiats du POST
?>

<div class="card p-4 mb-4">
    <h3 class="card-title"><?php echo ($action === 'modifier' && $type_dossier) ? 'Modifier le Type de Dossier' : 'Ajouter un Nouveau Type de Dossier'; ?></h3>
    <form action="types_dossiers.php?action=<?php echo ($action === 'modifier' && $type_dossier) ? 'modifier_submit' : 'ajouter_submit'; ?>" method="POST">
        <?php if ($action === 'modifier' && $type_dossier): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($type_dossier['id']); ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="nom_type" class="form-label">Nom du Type de Dossier <span class="text-danger">*</span> :</label>
            <input type="text" class="form-control" id="nom_type" name="nom_type" required value="<?php echo htmlspecialchars($type_dossier['nom_type'] ?? $_POST['nom_type'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description :</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($type_dossier['description'] ?? $_POST['description'] ?? ''); ?></textarea>
        </div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>
                <?php echo ($action === 'modifier' && $type_dossier) ? 'Mettre à jour' : 'Ajouter'; ?>
            </button>
            <?php if ($action === 'modifier' && $type_dossier): ?>
                <a href="types_dossiers.php" class="btn btn-secondary ms-2"><i class="fas fa-times-circle me-1"></i>Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h3 class="mt-4 mb-3">Liste des Types de Dossiers</h3>
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
            <?php if (empty($types_dossiers_liste)): ?>
                <tr>
                    <td colspan="4" class="text-center">Aucun type de dossier trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($types_dossiers_liste as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                        <td><?php echo htmlspecialchars($item['nom_type']); ?></td>
                        <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="types_dossiers.php?action=modifier&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info me-1" title="Modifier"><i class="fas fa-edit"></i></a>
                            <a href="types_dossiers.php?action=supprimer&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce type de dossier ? Cela n\'est possible que si aucun dossier n\'y est associé.');"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>