<?php
// clients/modifier.php
require_once __DIR__ . '/../includes/header.php';

if (!hasRequiredRole('gestionnaire')) {
    redirect('/fidaous/dashboard.php');
}

$client = null;
$message = '';
$formes_juridiques = [];
$regimes_fiscaux = [];
$employes = [];

if (isset($_GET['id'])) {
    $client_id = (int)$_GET['id'];

    try {
        $stmtClient = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
        $stmtClient->bindParam(':id', $client_id, PDO::PARAM_INT);
        $stmtClient->execute();
        $client = $stmtClient->fetch();

        if (!$client) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Client introuvable.</div>';
            redirect('/fidaous/clients/liste.php');
        }

        $stmtFJ = $pdo->query("SELECT id, nom_forme FROM formes_juridiques ORDER BY nom_forme ASC");
        $formes_juridiques = $stmtFJ->fetchAll();

        $stmtRF = $pdo->query("SELECT id, nom_regime FROM regimes_fiscaux ORDER BY nom_regime ASC");
        $regimes_fiscaux = $stmtRF->fetchAll();

        $stmtEmployes = $pdo->query("SELECT id, prenom, nom FROM employes WHERE actif = TRUE ORDER BY prenom ASC");
        $employes = $stmtEmployes->fetchAll();

    } catch (PDOException $e) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur lors du chargement des données : ' . $e->getMessage() . '</div>';
        redirect('/fidaous/clients/liste.php');
    }
} else {
    $_SESSION['message'] = '<div class="alert alert-danger" role="alert">ID client manquant.</div>';
    redirect('/fidaous/clients/liste.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $client) {
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
            $sql = "UPDATE clients SET
                        nom_entreprise = :nom_entreprise,
                        ice = :ice,
                        rc = :rc,
                        patente = :patente,
                        cnss = :cnss,
                        if_fiscale = :if_fiscale,
                        forme_juridique_id = :forme_juridique_id,
                        regime_fiscal_id = :regime_fiscal_id,
                        adresse = :adresse,
                        telephone = :telephone,
                        email = :email,
                        employe_id = :employe_id
                    WHERE id = :id";
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
            $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);

            $stmt->execute();

            if ($stmt->rowCount()) {
                 logActivity($pdo, 'Modification client', 'clients', $client_id, 'Client "' . $nom_entreprise . '" mis à jour.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Client mis à jour avec succès !</div>';
            } else {
                 $_SESSION['message'] = '<div class="alert alert-info" role="alert">Aucune modification n\'a été apportée au client.</div>';
            }
            redirect('/fidaous/clients/liste.php');

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger" role="alert">Erreur: Un identifiant fiscal (ICE, RC, Patente, CNSS, IF) est déjà utilisé par un autre client.</div>';
            } else {
                $message = '<div class="alert alert-danger" role="alert">Erreur lors de la mise à jour du client : ' . $e->getMessage() . '</div>';
            }
        }
    }
}
?>

<h1 class="mb-4">Modifier le Client : <?php echo htmlspecialchars($client['nom_entreprise'] ?? 'N/A'); ?></h1>

<?php echo $message; ?>

<div class="card p-4">
    <form action="modifier.php?id=<?php echo $client_id; ?>" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nom_entreprise" class="form-label">Nom de l'entreprise <span class="text-danger">*</span> :</label>
                <input type="text" class="form-control" id="nom_entreprise" name="nom_entreprise" required value="<?php echo htmlspecialchars($client['nom_entreprise'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="employe_id" class="form-label">Employé Responsable :</label>
                <select class="form-select" id="employe_id" name="employe_id">
                    <option value="">Sélectionner un employé</option>
                    <?php foreach ($employes as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo ($client['employe_id'] == $emp['id']) ? 'selected' : ''; ?>>
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
                <input type="text" class="form-control" id="ice" name="ice" value="<?php echo htmlspecialchars($client['ice'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="rc" class="form-label">RC (Registre de Commerce) :</label>
                <input type="text" class="form-control" id="rc" name="rc" value="<?php echo htmlspecialchars($client['rc'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="patente" class="form-label">Patente (Taxe Professionnelle) :</label>
                <input type="text" class="form-control" id="patente" name="patente" value="<?php echo htmlspecialchars($client['patente'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="cnss" class="form-label">CNSS (Caisse Nationale de Sécurité Sociale) :</label>
                <input type="text" class="form-control" id="cnss" name="cnss" value="<?php echo htmlspecialchars($client['cnss'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="if_fiscale" class="form-label">IF Fiscale (Identifiant Fiscal) :</label>
                <input type="text" class="form-control" id="if_fiscale" name="if_fiscale" value="<?php echo htmlspecialchars($client['if_fiscale'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="forme_juridique_id" class="form-label">Forme Juridique :</label>
                <select class="form-select" id="forme_juridique_id" name="forme_juridique_id">
                    <option value="">Sélectionner une forme juridique</option>
                    <?php foreach ($formes_juridiques as $fj): ?>
                        <option value="<?php echo $fj['id']; ?>" <?php echo ($client['forme_juridique_id'] == $fj['id']) ? 'selected' : ''; ?>>
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
                        <option value="<?php echo $rf['id']; ?>" <?php echo ($client['regime_fiscal_id'] == $rf['id']) ? 'selected' : ''; ?>>
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
                <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php echo htmlspecialchars($client['adresse'] ?? ''); ?></textarea>
            </div>
            <div class="col-md-3 mb-3">
                <label for="telephone" class="form-label">Téléphone :</label>
                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($client['telephone'] ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="email" class="form-label">Email :</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="liste.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour à la liste</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-sync-alt me-1"></i>Mettre à jour le Client</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>