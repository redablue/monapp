<?php
// admin/formes_juridiques.php
require_once __DIR__ . '/../includes/header.php';

// Seuls les administrateurs peuvent accéder à cette page
if (!hasRequiredRole('administrateur')) {
    redirect('/fidaous/dashboard.php'); // Chemin mis à jour
}

$message = '';
$action = $_GET['action'] ?? 'liste';
$id = (int)($_GET['id'] ?? 0);
$forme_juridique = null;

// Gérer les actions (Ajout, Modification, Suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'ajouter_submit') {
        $nom_forme = trim($_POST['nom_forme'] ?? '');
        $description = trim($_POST['description'] ?? null);

        if (empty($nom_forme)) {
            $message = '<div class="alert alert-danger" role="alert">Le nom de la forme juridique est obligatoire.</div>';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO formes_juridiques (nom_forme, description) VALUES (:nom_forme, :description)");
                $stmt->bindParam(':nom_forme', $nom_forme);
                $stmt->bindParam(':description', $description);
                $stmt->execute();
                $last_id = $pdo->lastInsertId();
                logActivity($pdo, 'Création forme juridique', 'formes_juridiques', $last_id, 'Forme juridique "' . $nom_forme . '" ajoutée.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Forme juridique ajoutée avec succès !</div>';
                redirect('/fidaous/admin/formes_juridiques.php'); // Chemin mis à jour
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = '<div class="alert alert-danger" role="alert">Cette forme juridique existe déjà.</div>';
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Erreur lors de l\'ajout : ' . $e->getMessage() . '</div>';
                }
            }
        }
    } elseif ($action === 'modifier_submit') {
        $id = (int)($_POST['id'] ?? 0);
        $nom_forme = trim($_POST['nom_forme'] ?? '');
        $description = trim($_POST['description'] ?? null);

        if (empty($nom_forme)) {
            $message = '<div class="alert alert-danger" role="alert">Le nom de la forme juridique est obligatoire.</div>';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE formes_juridiques SET nom_forme = :nom_forme, description = :description WHERE id = :id");
                $stmt->bindParam(':nom_forme', $nom_forme);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount()) {
                    logActivity($pdo, 'Modification forme juridique', 'formes_juridiques', $id, 'Forme juridique ID ' . $id . ' modifiée.');
                    $_SESSION['message'] = '<div class="alert alert-success" role="alert">Forme juridique mise à jour avec succès !</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-info" role="alert">Aucune modification apportée ou forme juridique introuvable.</div>';
                }
                redirect('/fidaous/admin/formes_juridiques.php'); // Chemin mis à jour
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = '<div class="alert alert-danger" role="alert">Cette forme juridique existe déjà.</div>';
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Erreur lors de la mise à jour : ' . $e->getMessage() . '</div>';
                }
            }
        }
    }
} elseif ($action === 'supprimer' && $id > 0) {
    try {
        // Vérifier si la forme juridique est utilisée par des clients
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE forme_juridique_id = :id");
        $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtCheck->execute();
        if ($stmtCheck->fetchColumn() > 0) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Impossible de supprimer cette forme juridique car elle est associée à des clients.</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM formes_juridiques WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount()) {
                logActivity($pdo, 'Suppression forme juridique', 'formes_juridiques', $id, 'Forme juridique ID ' . $id . ' supprimée.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Forme juridique supprimée avec succès.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur lors de la suppression ou forme juridique introuvable.</div>';
            }
        }
        redirect('/fidaous/admin/formes_juridiques.php'); // Chemin mis à jour
    } catch (PDOException $e) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur BD lors de la suppression : ' . $e->getMessage() . '</div>';
        redirect('/fidaous/admin/formes_juridiques.php'); // Chemin mis à jour
    }
}

// Charger les données pour l'affichage ou le pré-remplissage du formulaire
try {
    if ($action === 'modifier' && $id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM formes_juridiques WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $forme_juridique = $stmt->fetch();
        if (!$forme_juridique) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Forme juridique introuvable.</div>';
            redirect('/fidaous/admin/formes_juridiques.php'); // Chemin mis à jour
        }
    }
    $stmtListe = $pdo->query("SELECT * FROM formes_juridiques ORDER BY nom_forme ASC");
    $formes_juridiques_liste = $stmtListe->fetchAll();
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger" role="alert">Erreur lors du chargement des données : ' . $e->getMessage() . '</div>';
    $formes_juridiques_liste = [];
}
?>

<h1 class="mb-4">Gestion des Formes Juridiques</h1>

<?php
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}
echo $message; // Afficher les messages d'erreur immédiats du POST
?>

<div class="card p-4 mb-4">
    <h3 class="card-title"><?php echo ($action === 'modifier' && $forme_juridique) ? 'Modifier la Forme Juridique' : 'Ajouter une Nouvelle Forme Juridique'; ?></h3>
    <form action="formes_juridiques.php?action=<?php echo ($action === 'modifier' && $forme_juridique) ? 'modifier_submit' : 'ajouter_submit'; ?>" method="POST">
        <?php if ($action === 'modifier' && $forme_juridique): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($forme_juridique['id']); ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="nom_forme" class="form-label">Nom de la Forme Juridique <span class="text-danger">*</span> :</label>
            <input type="text" class="form-control" id="nom_forme" name="nom_forme" required value="<?php echo htmlspecialchars($forme_juridique['nom_forme'] ?? $_POST['nom_forme'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description :</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($forme_juridique['description'] ?? $_POST['description'] ?? ''); ?></textarea>
        </div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>
                <?php echo ($action === 'modifier' && $forme_juridique) ? 'Mettre à jour' : 'Ajouter'; ?>
            </button>
            <?php if ($action === 'modifier' && $forme_juridique): ?>
                <a href="formes_juridiques.php" class="btn btn-secondary ms-2"><i class="fas fa-times-circle me-1"></i>Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h3 class="mt-4 mb-3">Liste des Formes Juridiques</h3>
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
            <?php if (empty($formes_juridiques_liste)): ?>
                <tr>
                    <td colspan="4" class="text-center">Aucune forme juridique trouvée.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($formes_juridiques_liste as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                        <td><?php echo htmlspecialchars($item['nom_forme']); ?></td>
                        <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="formes_juridiques.php?action=modifier&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info me-1" title="Modifier"><i class="fas fa-edit"></i></a>
                            <a href="formes_juridiques.php?action=supprimer&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette forme juridique ? Cela n\'est possible que si aucun client n\'y est associé.');"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>