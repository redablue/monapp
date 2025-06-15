<?php
// employes/modifier.php
require_once __DIR__ . '/../includes/header.php';

if (!hasRequiredRole('administrateur')) {
    redirect('/fidaous/dashboard.php');
}

$employe = null;
$message = '';
$employe_id = null;

if (isset($_GET['id'])) {
    $employe_id = (int)$_GET['id'];

    try {
        $stmtEmploye = $pdo->prepare("SELECT id, nom, prenom, email, role, poste, date_embauche, telephone, adresse, actif FROM employes WHERE id = :id");
        $stmtEmploye->bindParam(':id', $employe_id, PDO::PARAM_INT);
        $stmtEmploye->execute();
        $employe = $stmtEmploye->fetch();

        if (!$employe) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Employé introuvable.</div>';
            redirect('/fidaous/employes/liste.php');
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur lors du chargement des données de l\'employé : ' . $e->getMessage() . '</div>';
        redirect('/fidaous/employes/liste.php');
    }
} else {
    $_SESSION['message'] = '<div class="alert alert-danger" role="alert">ID employé manquant.</div>';
    redirect('/fidaous/employes/liste.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $employe) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirm_mot_de_passe = $_POST['confirm_mot_de_passe'] ?? '';
    $role = $_POST['role'] ?? $employe['role'];
    $poste = trim($_POST['poste'] ?? null);
    $date_embauche = trim($_POST['date_embauche'] ?? null);
    $telephone = trim($_POST['telephone'] ?? null);
    $adresse = trim($_POST['adresse'] ?? null);
    $actif = isset($_POST['actif']) ? TRUE : FALSE;

    if (empty($nom) || empty($prenom) || empty($email)) {
        $message = '<div class="alert alert-danger" role="alert">Veuillez remplir les champs Nom, Prénom et Email.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger" role="alert">L\'adresse email n\'est pas valide.</div>';
    } elseif (!empty($mot_de_passe) && $mot_de_passe !== $confirm_mot_de_passe) {
        $message = '<div class="alert alert-danger" role="alert">Les mots de passe ne correspondent pas.</div>';
    } elseif (!empty($mot_de_passe) && strlen($mot_de_passe) < 8) {
        $message = '<div class="alert alert-danger" role="alert">Le nouveau mot de passe doit contenir au moins 8 caractères.</div>';
    } else {
        try {
            $stmtCheckEmail = $pdo->prepare("SELECT COUNT(*) FROM employes WHERE email = :email AND id != :id");
            $stmtCheckEmail->bindParam(':email', $email);
            $stmtCheckEmail->bindParam(':id', $employe_id, PDO::PARAM_INT);
            $stmtCheckEmail->execute();
            if ($stmtCheckEmail->fetchColumn() > 0) {
                $message = '<div class="alert alert-danger" role="alert">Cet email est déjà utilisé par un autre employé.</div>';
            } else {
                $sql = "UPDATE employes SET
                            nom = :nom,
                            prenom = :prenom,
                            email = :email,
                            role = :role,
                            poste = :poste,
                            date_embauche = :date_embauche,
                            telephone = :telephone,
                            adresse = :adresse,
                            actif = :actif";
                if (!empty($mot_de_passe)) {
                    $sql .= ", mot_de_passe = :mot_de_passe";
                }
                $sql .= " WHERE id = :id";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':poste', $poste);
                $stmt->bindParam(':date_embauche', $date_embauche);
                $stmt->bindParam(':telephone', $telephone);
                $stmt->bindParam(':adresse', $adresse);
                $stmt->bindParam(':actif', $actif, PDO::PARAM_BOOL);
                if (!empty($mot_de_passe)) {
                    $hashed_password = hashPassword($mot_de_passe);
                    $stmt->bindParam(':mot_de_passe', $hashed_password);
                }
                $stmt->bindParam(':id', $employe_id, PDO::PARAM_INT);

                $stmt->execute();

                if ($stmt->rowCount()) {
                     logActivity($pdo, 'Modification employé', 'employes', $employe_id, 'Employé "' . $prenom . ' ' . $nom . '" mis à jour.');
                    $_SESSION['message'] = '<div class="alert alert-success" role="alert">Employé mis à jour avec succès !</div>';
                } else {
                     $_SESSION['message'] = '<div class="alert alert-info" role="alert">Aucune modification n\'a été apportée à l\'employé.</div>';
                }
                redirect('/fidaous/employes/liste.php');
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger" role="alert">Erreur lors de la mise à jour de l\'employé : ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<h1 class="mb-4">Modifier l'Employé : <?php echo htmlspecialchars($employe['prenom'] . ' ' . $employe['nom'] ?? 'N/A'); ?></h1>

<?php echo $message; ?>

<div class="card p-4">
    <form action="modifier.php?id=<?php echo $employe_id; ?>" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span> :</label>
                <input type="text" class="form-control" id="prenom" name="prenom" required value="<?php echo htmlspecialchars($employe['prenom'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="nom" class="form-label">Nom <span class="text-danger">*</span> :</label>
                <input type="text" class="form-control" id="nom" name="nom" required value="<?php echo htmlspecialchars($employe['nom'] ?? ''); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span> :</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($employe['email'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="role" class="form-label">Rôle <span class="text-danger">*</span> :</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="collaborateur" <?php echo ($employe['role'] == 'collaborateur') ? 'selected' : ''; ?>>Collaborateur</option>
                    <option value="gestionnaire" <?php echo ($employe['role'] == 'gestionnaire') ? 'selected' : ''; ?>>Gestionnaire</option>
                    <option value="administrateur" <?php echo ($employe['role'] == 'administrateur') ? 'selected' : ''; ?>>Administrateur</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="mot_de_passe" class="form-label">Nouveau Mot de passe (laisser vide si inchangé) :</label>
                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe">
            </div>
            <div class="col-md-6 mb-3">
                <label for="confirm_mot_de_passe" class="form-label">Confirmer le nouveau mot de passe :</label>
                <input type="password" class="form-control" id="confirm_mot_de_passe" name="confirm_mot_de_passe">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="poste" class="form-label">Poste :</label>
                <input type="text" class="form-control" id="poste" name="poste" value="<?php echo htmlspecialchars($employe['poste'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="date_embauche" class="form-label">Date d'embauche :</label>
                <input type="date" class="form-control" id="date_embauche" name="date_embauche" value="<?php echo htmlspecialchars($employe['date_embauche'] ?? ''); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="telephone" class="form-label">Téléphone :</label>
                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($employe['telephone'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="adresse" class="form-label">Adresse :</label>
                <textarea class="form-control" id="adresse" name="adresse" rows="2"><?php echo htmlspecialchars($employe['adresse'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="actif" name="actif" value="1" <?php echo ($employe['actif']) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="actif">Compte actif</label>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="liste.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour à la liste</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-sync-alt me-1"></i>Mettre à jour l'Employé</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>