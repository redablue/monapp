<?php
// admin/logs_activite.php
require_once __DIR__ . '/../includes/header.php';

// Seuls les administrateurs peuvent accéder à cette page
if (!hasRequiredRole('administrateur')) {
    redirect('/fidaous/dashboard.php'); // Chemin mis à jour
}

$search = $_GET['search'] ?? '';
$employe_filter = $_GET['employe_id'] ?? '';
$table_filter = $_GET['table_affectee'] ?? '';

// Récupérer la liste des employés pour le filtre
$employes_list = [];
try {
    $stmtEmployes = $pdo->query("SELECT id, prenom, nom FROM employes ORDER BY prenom ASC");
    $employes_list = $stmtEmployes->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur si nécessaire
}

// Récupérer les noms de tables uniques affectées pour le filtre
$tables_affectees = [];
try {
    $stmtTables = $pdo->query("SELECT DISTINCT table_affectee FROM logs_activite WHERE table_affectee IS NOT NULL ORDER BY table_affectee ASC");
    $tables_affectees = $stmtTables->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Gérer l'erreur si nécessaire
}


$sql = "SELECT l.*, e.prenom, e.nom
        FROM logs_activite l
        LEFT JOIN employes e ON l.employe_id = e.id
        WHERE (l.action LIKE :search OR l.description LIKE :search OR l.adresse_ip LIKE :search)";

if (!empty($employe_filter)) {
    $sql .= " AND l.employe_id = :employe_filter";
}
if (!empty($table_filter)) {
    $sql .= " AND l.table_affectee = :table_filter";
}

$sql .= " ORDER BY l.date_heure DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search . '%');
if (!empty($employe_filter)) {
    $stmt->bindParam(':employe_filter', $employe_filter, PDO::PARAM_INT);
}
if (!empty($table_filter)) {
    $stmt->bindParam(':table_filter', $table_filter);
}
$stmt->execute();
$logs = $stmt->fetchAll();

?>

<h1 class="mb-4">Historique des Activités</h1>

<?php
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<div class="card p-3 mb-4">
    <h5 class="card-title mb-3"><i class="fas fa-filter me-2"></i>Filtres et Recherche</h5>
    <form action="logs_activite.php" method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Rechercher par action, description, IP..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="employe_id" class="form-select">
                <option value="">Tous les employés</option>
                <?php foreach ($employes_list as $employe): ?>
                    <option value="<?php echo $employe['id']; ?>" <?php echo ($employe_filter == $employe['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($employe['prenom'] . ' ' . $employe['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="table_affectee" class="form-select">
                <option value="">Toutes les tables</option>
                <?php foreach ($tables_affectees as $table): ?>
                    <option value="<?php echo htmlspecialchars($table); ?>" <?php echo ($table_filter == $table) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $table))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Appliquer Filtres</button>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID Log</th>
                <th>Date & Heure</th>
                <th>Employé</th>
                <th>Action</th>
                <th>Table Affectée</th>
                <th>ID Affecté</th>
                <th>Description</th>
                <th>Adresse IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="8" class="text-center">Aucun log d'activité trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['id']); ?></td>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['date_heure'])); ?></td>
                        <td><?php echo htmlspecialchars($log['prenom'] . ' ' . $log['nom'] ?? 'Système'); ?></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $log['table_affectee'] ?? 'N/A'))); ?></td>
                        <td><?php echo htmlspecialchars($log['id_affecte'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                        <td><?php echo htmlspecialchars($log['adresse_ip']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>