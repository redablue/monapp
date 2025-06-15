<?php
// includes/header.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Redirection si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    redirect('/fidaous/login.php');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Cabinet Comptable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/fidaous/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/fidaous/dashboard.php">
            <i class="fas fa-building me-2"></i>Mon Cabinet
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/fidaous/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Tableau de Bord</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/fidaous/clients/liste.php"><i class="fas fa-users me-1"></i>Clients</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/fidaous/dossiers/liste.php"><i class="fas fa-folder-open me-1"></i>Dossiers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/fidaous/taches/liste.php"><i class="fas fa-tasks me-1"></i>Tâches</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/fidaous/documents/liste.php"><i class="fas fa-file-alt me-1"></i>Documents</a>
                </li>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'administrateur'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-tools me-1"></i>Administration
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="/fidaous/admin/formes_juridiques.php">Formes Juridiques</a></li>
                            <li><a class="dropdown-item" href="/fidaous/admin/regimes_fiscaux.php">Régimes Fiscaux</a></li>
                            <li><a class="dropdown-item" href="/fidaous/admin/types_dossiers.php">Types de Dossiers</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/fidaous/admin/logs_activite.php">Logs d'Activité</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/fidaous/employes/liste.php"><i class="fas fa-user-tie me-1"></i>Gestion Employés</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_name'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-1"></i>Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/fidaous/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/fidaous/login.php"><i class="fas fa-sign-in-alt me-1"></i>Connexion</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-4">
    ```

#### `includes/footer.php`

```php
<?php
// includes/footer.php
?>
</main> <footer class="footer mt-auto py-3 bg-light">
    <div class="container text-center">
        <span class="text-muted">&copy; <?php echo date("Y"); ?> Mon Cabinet Comptable. Tous droits réservés.</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="/fidaous/assets/js/script.js"></script>

</body>
</html>