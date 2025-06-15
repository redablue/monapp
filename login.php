<?php
// login.php
session_start();
require_once __DIR__ . '/config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mot_de_passe)) {
        $message = '<div class="alert alert-danger" role="alert">Veuillez remplir tous les champs.</div>';
    } else {
        $stmt = $pdo->prepare("SELECT id, nom, prenom, email, mot_de_passe, role, actif FROM employes WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $employe = $stmt->fetch();

        if ($employe && password_verify($mot_de_passe, $employe['mot_de_passe'])) {
            if ($employe['actif']) {
                $_SESSION['user_id'] = $employe['id'];
                $_SESSION['user_name'] = $employe['prenom'] . ' ' . $employe['nom'];
                $_SESSION['user_role'] = $employe['role'];

                header('Location: /fidaous/dashboard.php');
                exit();
            } else {
                $message = '<div class="alert alert-warning" role="alert">Votre compte est inactif. Veuillez contacter l\'administrateur.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger" role="alert">Email ou mot de passe incorrect.</div>';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion Cabinet Comptable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/fidaous/assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2 class="text-center mb-4"><i class="fas fa-lock me-2"></i>Connexion</h2>
    <?php echo $message; ?>
    <form action="login.php" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Email :</label>
            <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
        </div>
        <div class="mb-3">
            <label for="mot_de_passe" class="form-label">Mot de passe :</label>
            <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password">
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">Se connecter</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>