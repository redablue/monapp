<?php
// documents/liste.php
require_once __DIR__ . '/../includes/header.php';

if (!hasRequiredRole('collaborateur')) {
    redirect('/fidaous/dashboard.php');
}

$search = $_GET['search'] ?? '';
$dossier_filter = $_GET['dossier_id'] ?? '';
$client_filter = $_GET['client_id'] ?? '';

$dossiers_list = [];
try {
    $stmtDossiers = $pdo->query("SELECT id, titre FROM dossiers ORDER BY titre ASC");
    $dossiers_list = $stmtDossiers->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur si nécessaire
}

$clients_list = [];
try {
    $stmtClients = $pdo->query("SELECT id, nom_entreprise FROM clients ORDER BY nom_entreprise ASC");
    $clients_list = $stmtClients->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur si nécessaire
}


$sql = "SELECT doc.*, d.titre AS dossier_titre, c.nom_entreprise, e.prenom AS uploader_prenom, e.nom AS uploader_nom
        FROM documents doc
        JOIN dossiers d ON doc.dossier_id = d.id
        JOIN clients c ON d.client_id = c.id
        LEFT JOIN employes e ON doc.uploader_id = e.id
        WHERE (doc.nom_fichier LIKE :search OR d.titre LIKE :search OR c.nom_entreprise LIKE :search)";

if (!empty($dossier_filter)) {
    $sql .= " AND doc.dossier_id = :dossier_filter";
}
if (!empty($client_filter)) {
    $sql .= " AND c.id = :client_filter";
}

if ($_SESSION['user_role'] == 'collaborateur') {
    $sql .= " AND (d.employe_responsable_id = :current_user_id OR c.employe_id = :current_user_id)";
}

$sql .= " ORDER BY doc.date_upload DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%');
if (!empty($dossier_filter)) {
    $stmt->bindParam(':dossier_filter', $dossier_filter, PDO::PARAM_INT);
}
if (!empty($client_filter)) {
    $stmt->bindParam(':client_filter', $client_filter, PDO::PARAM_INT);
}
if ($_SESSION['user_role'] == 'collaborateur') {
    $stmt->bindParam(':current_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}
$stmt->execute();
$documents = $stmt->fetchAll();

// Gestion de la suppression de document
if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
    if (hasRequiredRole('gestionnaire')) {
        $document_id = (int)$_GET['id'];
        try {
            $stmtFilePath = $pdo->prepare("SELECT chemin_fichier FROM documents WHERE id = :id");
            $stmtFilePath->bindParam(':id', $document_id, PDO::PARAM_INT);
            $stmtFilePath->execute();
            $file_path = $stmtFilePath->fetchColumn();

            $pdo->beginTransaction();

            $stmtDelete = $pdo->prepare("DELETE FROM documents WHERE id = :