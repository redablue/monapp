<?php
// employes/ajouter.php
require_once __DIR__ . '/../includes/header.php';

if (!hasRequiredRole('administrateur')) {
    redirect('/fidaous/dashboard.php');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirm_mot_de_passe = $_POST['confirm_mot_de_passe'] ?? '';
    $role = $_POST['role'] ?? 'collaborateur';
    $poste = trim($_POST['poste'] ?? null);
    $date_embauche = trim($_POST['date_embauche'] ?? null);
    $telephone = trim($_POST['telephone'] ?? null);
    $adresse = trim($_POST['adresse'] ?? null);
    $actif = isset($_POST['actif']) ? TRUE : FALSE;

    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($confirm_mot_de_passe)) {
        $message = '<div class="alert alert-danger" role="alert">Veuillez remplir tous les champs obligatoires (Nom, Prénom, Email, Mot de passe).</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger" role="alert">L\'adresse email n\'est pas valide.</div>';
    } elseif ($mot_de_passe !== $confirm_mot_de_passe) {
        $message = '<div class="alert alert-danger" role="alert">Les mots de passe ne correspondent pas.</div>';
    } elseif (strlen($mot_de_passe) < 8) {
        $message = '<div class="alert alert-danger" role="alert">Le mot de passe doit contenir au moins 8 caractères.</div>';
    } else {
        try {
            $stmtCheckEmail = $pdo->prepare("SELECT COUNT(*) FROM employes WHERE email = :email");
            $stmtCheckEmail->bindParam(':email', $email);
            $stmtCheckEmail->execute();
            if ($stmtCheckEmail->fetchColumn() > 0) {
                $message = '<div class="alert alert-danger" role="alert">Cet email est déjà utilisé par un autre employé.</div>';
            } else {
                $hashed_password = hashPassword($mot_de_passe);

                $sql = "INSERT INTO employes (nom, prenom, email, mot_de_passe, role, poste, date_embauche, telephone, adresse, actif)
                        VALUES (:nom, :prenom, :email, :mot_de_passe, :role, :poste, :date_embauche, :telephone, :adresse, :actif)";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':mot_de_passe', $hashed_password);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':poste', $poste);
                $stmt->bindParam(':date_embauche', $date_embauche);
                $stmt->bindParam(':telephone', $telephone);
                $stmt->bindParam(':adresse', $adresse);
                $stmt->bindParam(':actif', $actif, PDO::PARAM_BOOL);

                $stmt->execute();
                $last_id = $pdo->lastInsertId();

                logActivity($pdo, 'Création employé', 'employes', $last_id, 'Nouvel employé "' . $prenom . ' ' . $nom . '" ajouté.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Employé ajouté avec succès !</div>';
                redirect('/fidaous/employes/liste.php');
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger" role="alert">Erreur lors de l\'ajout de l\'employé : ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<h1 class="mb-4">Ajouter un Nouvel Employé</h1>

<?php echo $message; ?>

<div class="card p-4">
    <form action="ajouter.php" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span> :</label>
                <input type="text" class="form-control" id="prenom" name="prenom" required value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="nom" class="form-label">Nom <span class="text-danger">*</span> :</label>
                <input type="text" class="form-control" id="nom" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span> :</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="role" class="form-label">Rôle <span class="text-danger">*</span> :</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="collaborateur" <?php echo (($_POST['role'] ?? '') == 'collaborateur') ? 'selected' : ''; ?>>Collaborateur</option>
                    <option value="gestionnaire" <?php echo (($_POST['role'] ?? '') == 'gestionnaire') ? 'selected' : ''; ?>>Gestionnaire</option>
                    <option value="administrateur" <?php echo (($_POST['role'] ?? '') == 'administrateur') ? 'selected' : ''; ?>>Administrateur</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="mot_de_passe" class="form-label">Mot de passe <span class="text-danger">*</span> :</label>
                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="confirm_mot_de_passe" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span> :</label>
                <input type="password" class="form-control" id="confirm_mot_de_passe" name="confirm_mot_de_passe" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="poste" class="form-label">Poste :</label>
                <input type="text" class="form-control" id="poste" name="poste" value="<?php echo htmlspecialchars($_POST['poste'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="date_embauche" class="form-label">Date d'embauche :</label>
                <input type="date" class="form-control" id="date_embauche" name="date_embauche" value="<?php echo htmlspecialchars($_POST['date_embauche'] ?? ''); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="telephone" class="form-label">Téléphone :</label>
                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="adresse" class="form-label">Adresse :</label>
                <textarea class="form-control" id="adresse" name="adresse" rows="2"><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="actif" name="actif" value="1" <?php echo ((isset($_POST['actif']) && $_POST['actif']) || !isset($_POST['actif'])) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="actif">Compte actif</label>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="liste.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour à la liste</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i>Ajouter l'Employé</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>