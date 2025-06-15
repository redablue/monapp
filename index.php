<?php
// index.php
session_start(); // Démarre la session PHP

// Si l'utilisateur est déjà connecté, le rediriger vers le tableau de bord
if (isset($_SESSION['user_id'])) {
    header('Location: /fidaous/dashboard.php');
    exit();
} else {
    // Si l'utilisateur n'est pas connecté, le rediriger vers la page de connexion
    header('Location: /fidaous/login.php');
    exit();
}