<?php
// generer_mdp.php
$mot_de_passe_clair = 'MonSuperMDPsecret123'; // *** CHOISIS UN MOT DE PASSE SIMPLE POUR LE TEST, MAIS SÉCURISÉ POUR LA PRODUCTION ***
$mot_de_passe_hache = password_hash($mot_de_passe_clair, PASSWORD_BCRYPT);

echo "Mot de passe clair que tu dois taper : <strong>" . htmlspecialchars($mot_de_passe_clair) . "</strong><br>";
echo "Mot de passe HACHÉ à insérer dans la base de données : <strong>" . htmlspecialchars($mot_de_passe_hache) . "</strong><br>";
echo "<br>Copie la deuxième chaîne et colle-la dans la colonne 'mot_de_passe' de ton utilisateur dans la table 'employes'.";
?>