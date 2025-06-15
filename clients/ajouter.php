<?php
// clients/ajouter.php
require_once __DIR__ . '/../includes/header.php';

if (!hasRequiredRole('gestionnaire')) {
    redirect('/fidaous/dashboard.php');
}

$message = '';
$formes_juridiques = [];
$regimes_fiscaux = [];
$employes = [];

try {
    $stmtFJ = $pdo->query("SELECT id, nom_forme FROM formes_juridiques ORDER BY nom_forme ASC");
    $formes_juridiques = $stmtFJ->fetchAll();

    $stmtRF = $pdo->query("SELECT id, nom_regime FROM regimes_fiscaux ORDER BY nom_regime ASC");
    $regimes_fiscaux = $stmtRF->fetchAll();

    $stmtEmployes = $pdo->query("SELECT id, prenom, nom FROM employes WHERE actif = TRUE ORDER BY prenom ASC");
    $employes = $stmtEmployes->fetchAll();

} catch (PDOException $e) {
    $message = '<div class="alert alert-danger" role="alert">Erreur lors du chargement des listes : ' . $e->getMessage() . '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_entreprise = trim($_POST['nom_entreprise'] ?? '');
    $ice = trim($_POST['ice'] ?? null);
    $rc = trim($_POST['rc'] ?? null);
    $patente = trim($_POST['patente'] ?? null);
    $cnss = trim($_POST['cnss'] ?? null);
    $if_fiscale = trim($_POST['if_fiscale'] ?? null);
    $forme_juridique_id = $_POST['forme_juridique_id'] ?? null;
    $regime_fiscal_id = $_POST['regime_fiscal_id'] ?? null;
    $adresse = trim($_POST['adresse'] ?? null);
    $telephone = trim($_POST['telephone'] ?? null);
    $email = trim($_POST['email'] ?? null);
    $employe_id = $_POST['employe_id'] ?? null;

    if (empty($nom_entreprise)) {
        $message = '<div class="alert alert-danger" role="alert">Le nom de l\'entreprise est obligatoire.</div>';
    } else {
        try {
            $sql = "INSERT INTO clients (nom_entreprise, ice, rc, patente, cnss, if_fiscale, forme_juridique_id, regime_fiscal_id, adresse, telephone, email, employe_id)
                    VALUES (:nom_entreprise, :ice, :rc, :patente, :cnss, :if_fiscale, :forme_juridique_id, :regime_fiscal_id, :adresse, :telephone, :email, :employe_id)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':nom_entreprise', $nom_entreprise);
            $stmt->bindParam(':ice', $ice);
            $stmt->bindParam(':rc', $rc);
            $stmt->bindParam(':patente', $patente);
            $stmt->bindParam(':cnss', $cnss);
            $stmt->bindParam(':if_fiscale', $if_fiscale);
            $stmt->bindParam(':forme_juridique_id', $forme_juridique_id, PDO::PARAM_INT);
            $stmt->bindParam(':regime_fiscal_id', $regime_fiscal_id, PDO::PARAM_INT);
            $stmt->bindParam(':adresse', $adresse);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':employe_id', $employe_id, PDO::PARAM_INT);

            $stmt->execute();
            $last_id = $pdo->lastInsertId();

            logActivity($pdo, 'Création client', 'clients', $last_id, 'Nouveau client "' . $nom_entreprise . '" ajouté.');
            $_SESSION['message'] = '<div class="alert alert-success" role="alert">Client ajouté avec succès !</div>';
            redirect('/fidaous/clients/liste.php');

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger" role="alert">Erreur: Un identifiant fiscal (ICE, RC, Patente, CNSS, IF) est déjà utilisé.</div>';
            } else {
                $message = '<div class="alert alert-danger" role="alert">Erreur lors de l\'ajout du client : ' . $e->getMessage() . '</div>';
            }
        }
    }
}
?>

<h1 class="mb-4">Ajouter un Nouveau Client</h1>

<?php echo $message; ?>

<div class="card p-4">
    <form action="ajouter.php" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nom_entreprise" class="form-label">Nom de l'entreprise <span class="text-danger">*</span> :</label>
                <input type="text" class="form-control" id="nom_entreprise" name="nom_entreprise" required value="<?php echo htmlspecialchars($_POST['nom_entreprise'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="employe_id" class="form-label">Employé Responsable :</label>
                <select class="form-select" id="employe_id" name="employe_id">
                    <option value="">Sélectionner un employé</option>
                    <?php foreach ($employes as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo (isset($_POST['employe_id']) && $_POST['employe_id'] == $emp['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['prenom'] . ' ' . $emp['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h3 class="mt-4 mb-3">Identifiants Fiscaux Marocains</h3>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="ice" class="form-label">ICE (Identifiant Commun de l'Entreprise) :</label>
                <input type="text" class="form-control" id="ice" name="ice" value="<?php echo htmlspecialchars($_POST['ice'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="rc" class="form-label">RC (Registre de Commerce) :</label>
                <input type="text" class="form-control" id="rc" name="rc" value="<?php echo htmlspecialchars($_POST['rc'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="patente" class="form-label">Patente (Taxe Professionnelle) :</label>
                <input type="text" class="form-control" id="patente" name="patente" value="<?php echo htmlspecialchars($_POST['patente'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="cnss" class="form-label">CNSS (Caisse Nationale de Sécurité Sociale) :</label>
                <input type="text" class="form-control" id="cnss" name="cnss" value="<?php echo htmlspecialchars($_POST['cnss'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="if_fiscale" class="form-label">IF Fiscale (Identifiant Fiscal) :</label>
                <input type="text" class="form-control" id="if_fiscale" name="if_fiscale" value="<?php echo htmlspecialchars($_POST['if_fiscale'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="forme_juridique_id" class="form-label">Forme Juridique :</label>
                <select class="form-select" id="forme_juridique_id" name="forme_juridique_id">
                    <option value="">Sélectionner une forme juridique</option>
                    <?php foreach ($formes_juridiques as $fj): ?>
                        <option value="<?php echo $fj['id']; ?>" <?php echo (isset($_POST['forme_juridique_id']) && $_POST['forme_juridique_id'] == $fj['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($fj['nom_forme']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="regime_fiscal_id" class="form-label">Régime Fiscal :</label>
                <select class="form-select" id="regime_fiscal_id" name="regime_fiscal_id">
                    <option value="">Sélectionner un régime fiscal</option>
                    <?php foreach ($regimes_fiscaux as $rf): ?>
                        <option value="<?php echo $rf['id']; ?>" <?php echo (isset($_POST['regime_fiscal_id']) && $_POST['regime_fiscal_id'] == $rf['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rf['nom_regime']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h3 class="mt-4 mb-3">Coordonnées</h3>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="adresse" class="form-label">Adresse :</label>
                <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
            </div>
            <div class="col-md-3 mb-3">
                <label for="telephone" class="form-label">Téléphone :</label>
                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="email" class="form-label">Email :</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="liste.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour à la liste</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Ajouter le Client</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>