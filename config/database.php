<?php
// config/database.php

define('DB_HOST', 'localhost'); // L'hôte de votre base de données
define('DB_USER', 'root');     // Votre nom d'utilisateur MySQL/MariaDB
define('DB_PASS', 'changeme');         // Votre mot de passe MySQL/MariaDB (souvent vide '' pour XAMPP/WAMP par défaut)
define('DB_NAME', 'gestion_de_cabinet_comptable'); // Nom de la base de données

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}