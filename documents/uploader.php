<?php
// documents/uploader.php
require_once __DIR__ . '/../includes/header.php';

// Vérifier l'autorisation : seuls les gestionnaires et administrateurs peuvent uploader un document
if (!hasRequiredRole('gestionnaire')) {
    redirect('/fidaous/dashboard.php');
}

// ... le reste du code de la page ...

echo "DEBUG: User ID from session: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "DEBUG: User Name from session: " . ($_SESSION['user_name'] ?? 'NOT SET') . "<br>";
// Supprimez ces lignes après le diagnostic
// ... (le reste du code)
?>

// Vérifier l'autorisation : seuls les gestionnaires et administrateurs peuvent uploader un document
if (!hasRequiredRole('gestionnaire')) {
    redirect('/fidaous/dashboard.php'); // Chemin mis à jour
}

$message = '';
$dossiers = [];

// Définir le répertoire d'upload
// Assure-toi que ce dossier existe et est accessible en écriture par le serveur web
$upload_dir = __DIR__ . '/../uploads/documents/';

// Crée le dossier d'upload s'il n'existe pas
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true); // 0775 est une bonne permission, assure l'écriture pour le groupe
}

try {
    // Récupérer la liste des dossiers actifs pour associer le document
    $stmtDossiers = $pdo->query("SELECT id, titre FROM dossiers WHERE statut NOT IN ('termine', 'annule') ORDER BY titre ASC");
    $dossiers = $stmtDossiers->fetchAll();
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger" role="alert">Erreur lors du chargement des dossiers : ' . $e->getMessage() . '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dossier_id = $_POST['dossier_id'] ?? null;
    $nom_fichier_custom = trim($_POST['nom_fichier_custom'] ?? null);

    // Validation
    if (empty($dossier_id)) {
        $message = '<div class="alert alert-danger" role="alert">Veuillez sélectionner un dossier.</div>';
    } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        $message = '<div class="alert alert-danger" role="alert">Veuillez sélectionner un fichier à uploader. (Code d\'erreur: ' . ($_FILES['document_file']['error'] ?? 'N/A') . ')</div>';
    } else {
        $file_tmp_name = $_FILES['document_file']['tmp_name'];
        $file_name = $_FILES['document_file']['name'];
        $file_size = $_FILES['document_file']['size'];
        $file_type = $_FILES['document_file']['type'];

        // Utiliser le nom personnalisé si fourni, sinon le nom original du fichier
        $final_file_name = !empty($nom_fichier_custom) ? $nom_fichier_custom . '.' . pathinfo($file_name, PATHINFO_EXTENSION) : $file_name;
        // Rendre le nom de fichier sûr pour l'URL et le système de fichiers
        $final_file_name = preg_replace("/[^a-zA-Z0-9-_\.]/", "_", $final_file_name);
        $file_destination = $upload_dir . $final_file_name;

        // Assurer l'unicité du nom de fichier pour éviter les écrasements
        $i = 0;
        $original_final_file_name = $final_file_name;
        while (file_exists($file_destination)) {
            $i++;
            $parts = pathinfo($original_final_file_name);
            $final_file_name = $parts['filename'] . '_' . $i . '.' . $parts['extension'];
            $file_destination = $upload_dir . $final_file_name;
        }

        if (move_uploaded_file($file_tmp_name, $file_destination)) {
            try {
                $sql = "INSERT INTO documents (dossier_id, nom_fichier, chemin_fichier, type_mime, taille_ko, date_upload, uploader_id)
                        VALUES (:dossier_id, :nom_fichier, :chemin_fichier, :type_mime, :taille_ko, NOW(), :uploader_id)";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':dossier_id', $dossier_id, PDO::PARAM_INT);
                $stmt->bindParam(':nom_fichier', $final_file_name);
                $stmt->bindParam(':chemin_fichier', $file_destination);
                $stmt->bindParam(':type_mime', $file_type);
                $taille_ko = round($file_size / 1024);
                $stmt->bindParam(':taille_ko', $taille_ko, PDO::PARAM_INT);
                $stmt->bindParam(':uploader_id', $_SESSION['user_id'], PDO::PARAM_INT);

                $stmt->execute();
                $last_id = $pdo->lastInsertId();

                logActivity($pdo, 'Upload document', 'documents', $last_id, 'Document "' . $final_file_name . '" uploadé pour le dossier ID ' . $dossier_id . '.');
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Document uploadé avec succès !</div>';
                redirect('/fidaous/documents/liste.php'); // <<< CHEMIN MIS À JOUR

            } catch (PDOException $e) {
                // Si l'insertion échoue, supprimer le fichier uploadé pour éviter les orphelins
                if (file_exists($file_destination)) {
                    unlink($file_destination);
                }
                $message = '<div class="alert alert-danger" role="alert">Erreur lors de l\'enregistrement du document en base de données : ' . $e->getMessage() . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger" role="alert">Erreur lors du déplacement du fichier uploadé. Vérifiez les permissions du dossier ' . htmlspecialchars($upload_dir) . '.</div>';
        }
    }
}
?>

<h1 class="mb-4">Uploader un Document</h1>

<?php echo $message; ?>

<div class="card p-4">
    <form action="uploader.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="dossier_id" class="form-label">Dossier Associé <span class="text-danger">*</span> :</label>
            <select class="form-select" id="dossier_id" name="dossier_id" required>
                <option value="">Sélectionner un dossier</option>
                <?php foreach ($dossiers as $dossier): ?>
                    <option value="<?php echo $dossier['id']; ?>" <?php echo (isset($_POST['dossier_id']) && $_POST['dossier_id'] == $dossier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dossier['titre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="document_file" class="form-label">Sélectionner le Fichier <span class="text-danger">*</span> :</label>
            <input type="file" class="form-control" id="document_file" name="document_file" required>
            <div class="form-text">Taille maximale recommandée : 50Mo (configurable dans php.ini).</div>
        </div>

        <div class="mb-3">
            <label for="nom_fichier_custom" class="form-label">Nom du fichier affiché (optionnel) :</label>
            <input type="text" class="form-control" id="nom_fichier_custom" name="nom_fichier_custom" placeholder="Laisser vide pour utiliser le nom original du fichier" value="<?php echo htmlspecialchars($_POST['nom_fichier_custom'] ?? ''); ?>">
            <div class="form-text">Ex: "Contrat Client A - 2024" (l'extension sera ajoutée automatiquement)</div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="liste.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour à la liste</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-upload-alt me-1"></i>Uploader</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>