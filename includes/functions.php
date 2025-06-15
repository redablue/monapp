<?php
// includes/functions.php

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

function hasRequiredRole(string $required_role): bool {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    $user_role = $_SESSION['user_role'];
    $roles_hierarchy = [
        'collaborateur' => 1,
        'gestionnaire' => 2,
        'administrateur' => 3
    ];

    return isset($roles_hierarchy[$user_role]) && isset($roles_hierarchy[$required_role]) &&
           $roles_hierarchy[$user_role] >= $roles_hierarchy[$required_role];
}

function redirect(string $location): void {
    header('Location: ' . $location);
    exit();
}

function logActivity(PDO $pdo, string $action, string $table_affectee, int $id_affecte, string $description): void {
    if (!isset($_SESSION['user_id'])) {
        $employe_id = null;
    } else {
        $employe_id = $_SESSION['user_id'];
    }
    $adresse_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $stmt = $pdo->prepare("INSERT INTO logs_activite (employe_id, action, table_affectee, id_affecte, description, adresse_ip) VALUES (:employe_id, :action, :table_affectee, :id_affecte, :description, :adresse_ip)");
    $stmt->bindParam(':employe_id', $employe_id, PDO::PARAM_INT);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':table_affectee', $table_affectee);
    $stmt->bindParam(':id_affecte', $id_affecte, PDO::PARAM_INT);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':adresse_ip', $adresse_ip);
    $stmt->execute();
}