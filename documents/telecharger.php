<?php
// documents/telecharger.php
session_start(); // Démarre la session pour vérifier les permissions
require_once __DIR__ . '/../config/database.php'; // Connexion DB
require_once __DIR__ . '/../includes/functions.php'; // Pour hasRequiredRole

// Vérifier l'authentification et l'ID du document
if (!isset($_SESSION['user_id'])) {
    redirect('/fidaous/login.php'); // Chemin mis à jour
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = '<div class="alert alert-danger" role="alert">ID document manquant.</div>';
    redirect('/fidaous/documents/liste.php'); // Chemin mis à jour
}

$document_id = (int)$_GET['id'];

try {
    // Récupérer les informations du document, du dossier associé et du client
    $stmt = $pdo->prepare("SELECT doc.chemin_fichier, doc.nom_fichier, doc.type_mime, d.employe_responsable_id, c.employe_id
                            FROM documents doc
                            JOIN dossiers d ON doc.dossier_id = d.id
                            JOIN clients c ON d.client_id = c.id
                            WHERE doc.id = :id");
    $stmt->bindParam(':id', $document_id, PDO::PARAM_INT);
    $stmt->execute();
    $document_data = $stmt->fetch();

    if (!$document_data) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Document introuvable.</div>';
        redirect('/fidaous/documents/liste.php'); // Chemin mis à jour
    }

    $file_path = $document_data['chemin_fichier'];
    $file_name = $document_data['nom_fichier'];
    $file_mime_type = $document_data['type_mime'];
    $dossier_responsable_id = $document_data['employe_responsable_id'];
    $client_responsable_id = $document_data['employe_id'];


    // Vérifier les permissions de l'utilisateur connecté pour le téléchargement
    $allowed = false;
    if (hasRequiredRole('gestionnaire')) { // Administrateur ou gestionnaire : accès à tous les documents
        $allowed = true;
    } elseif ($_SESSION['user_role'] == 'collaborateur') {
        // Collaborateur peut télécharger si :
        // 1. Il est le responsable du dossier associé au document
        // 2. Il est le responsable du client associé au dossier du document
        if ($_SESSION['user_id'] == $dossier_responsable_id || $_SESSION['user_id'] == $client_responsable_id) {
            $allowed = true;
        }
    }

    if (!$allowed) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Vous n\'avez pas la permission de télécharger ce document.</div>';
        redirect('/fidaous/documents/liste.php'); // Chemin mis à jour
    }

    // Vérifier si le fichier existe physiquement
    if (!file_exists($file_path)) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Le fichier physique n\'existe pas sur le serveur.</div>';
        redirect('/fidaous/documents/liste.php'); // Chemin mis à jour
    }

    // Journaliser l'activité de téléchargement
    logActivity($pdo, 'Téléchargement document', 'documents', $document_id, 'Document "' . $file_name . '" téléchargé.');

    // Préparer les en-têtes HTTP pour le téléchargement
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $file_mime_type);
    header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));

    // Effacer le tampon de sortie pour éviter des problèmes avec les en-têtes
    ob_clean();
    flush();

    // Lire et envoyer le fichier
    readfile($file_path);
    exit();

} catch (PDOException $e) {
    $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Erreur lors de la récupération des informations du document : ' . $e->getMessage() . '</div>';
    redirect('/fidaous/documents/liste.php'); // Chemin mis à jour
}
?>