<?php
// dossiers/ajouter.php
require_once __DIR__ . '/../includes/header.php';

if (!hasRequiredRole('gestionnaire')) {
    redirect('/fidaous/dashboard.php');
}

$message = '';
$clients = [];
$types_dossiers = [];
$employes = [];

try {
    $stmtClients = $pdo->query("SELECT id, nom_entreprise FROM clients ORDER BY nom_entreprise ASC");
    $clients = $stmtClients->fetchAll();

    $stmtTypes = $pdo->query("SELECT id, nom_type FROM types_dossiers ORDER BY nom_type ASC");
    $types_dossiers = $stmtTypes->fetchAll();

    $stmtEmployes = $pdo->query("SELECT id, prenom, nom FROM employes WHERE actif = TRUE ORDER BY prenom ASC");
    $employes = $stmtEmployes->fetchAll();

} catch (PDOException $e) {
    $message = '<div class="alert alert-danger" role="alert">Erreur lors du chargement des listes : ' . $e->getMessage() . '</div>';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $type_dossier_id = $_POST['type_dossier_id'] ?? null;
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? null);
    $date_echeance = trim($_POST['date_echeance'] ?? '');
    $statut = $_POST['statut'] ?? 'en_attente';
    $commentaires = trim($_POST['commentaires'] ?? null);
    $employe_responsable_id = $_POST['employe_responsable_id'] ?? null;

    if (empty($client_id) || empty($type_dossier_id) || empty($titre) || empty($date_echeance)) {
        $message = '<div class="alert alert-danger" role="alert">Veuillez remplir tous les champs obligatoires (Client, Type de dossier, Titre, Date d\'échéance).</div>';
    } else {
        try {
            $sql = "INSERT INTO dossiers (client_id, type_dossier_id, titre, description, date_echeance, statut, commentaires, employe_responsable_id)
                    VALUES (:client_id, :type_dossier_id, :titre, :description, :date_echeance, :statut, :commentaires, :employe_responsable_id)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
            $stmt->bindParam(':type_dossier_id', $type_dossier_id, PDO::PARAM_INT);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':date_echeance', $date_echeance);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':commentaires', $commentaires);
            $stmt->bindParam(':employe_responsable_id', $employe_responsable_id, PDO::PARAM_INT);

            $stmt->execute();
            $last_id = $pdo->lastInsertId();

            logActivity($pdo, 'Création dossier', 'dossiers', $last_id, 'Nouveau dossier "' . $titre . '" créé.');
            $_SESSION['message'] = '<div class="alert alert-success" role="alert">Dossier ajouté avec succès !</div>';
            redirect('/fidaous/dossiers/liste.php');

        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger" role="alert">Erreur lors de l\'ajout du dossier : ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<h1 class="mb-4">Ajouter un Nouveau Dossier</h1>

<?php echo $message; ?>

<div class="card p-4">
    <form action="ajouter.php" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="client_id" class="form-label">Client <span class="text-danger">*</span> :</label>
                <select class="form-select" id="client_id" name="client_id" required>
                    <option value="">Sélectionner un client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['nom_entreprise']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="type_dossier_id" class="form-label">Type de Dossier <span class="text-danger">*</span> :</label>
                <select class="form-select" id="type_dossier_id" name="type_dossier_id" required>
                    <option value="">Sélectionner un type</option>
                    <?php foreach ($types_dossiers as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo (isset($_POST['type_dossier_id']) && $_POST['type_dossier_id'] == $type['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['nom_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="titre" class="form-label">Titre du Dossier <span class="text-danger">*</span> :</label>
            <input type="text" class="form-control" id="titre" name="titre" required value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description :</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="date_echeance" class="form-label">Date d'échéance <span class="text-danger">*</span> :</label>
                <input type="date" class="form-control" id="date_echeance" name="date_echeance" required value="<?php echo htmlspecialchars($_POST['date_echeance'] ?? date('Y-m-d')); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="statut" class="form-label">Statut <span class="text-danger">*</span> :</label>
                <select class="form-select" id="statut" name="statut" required>
                    <option value="en_attente" <?php echo (($_POST['statut'] ?? '') == 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                    <option value="en_cours" <?php echo (($_POST['statut'] ?? '') == 'en_cours') ? 'selected' : ''; ?>>En cours</option>
                    <option value="termine" <?php echo (($_POST['statut'] ?? '') == 'termine') ? 'selected' : ''; ?>>Terminé</option>
                    <option value="en_retard" <?php echo (($_POST['statut'] ?? '') == 'en_retard') ? 'selected' : ''; ?>>En retard</option>
                    <option value="annule" <?php echo (($_POST['statut'] ?? '') == 'annule') ? 'selected' : ''; ?>>Annulé</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="employe_responsable_id" class="form-label">Employé Responsable du Dossier :</label>
            <select class="form-select" id="employe_responsable_id" name="employe_responsable_id">
                <option value="">Non assigné</option>
                <?php foreach ($employes as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>" <?php echo (isset($_POST['employe_responsable_id']) && $_POST['employe_responsable_id'] == $emp['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($emp['prenom'] . ' ' . $emp['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="commentaires" class="form-label">Commentaires :</label>
            <textarea class="form-control" id="commentaires" name="commentaires" rows="3"><?php echo htmlspecialchars($_POST['commentaires'] ?? ''); ?></textarea>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="liste.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour à la liste</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Ajouter le Dossier</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>