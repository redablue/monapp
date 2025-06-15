-- SQL Script pour la base de données 'gestion_de_cabinet_comptable'
-- Compatible MySQL et MariaDB

-- Suppression des bases de données si elles existent (pour une régénération propre)
DROP DATABASE IF EXISTS `gestion_entreprise_ma`;
DROP DATABASE IF EXISTS `gestion_de_cabinet_comptable`;

-- Création de la base de données avec le nom correct
CREATE DATABASE IF NOT EXISTS `gestion_de_cabinet_comptable` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gestion_de_cabinet_comptable`;

-- Table pour les Formes Juridiques (spécifiques au Maroc)
CREATE TABLE `formes_juridiques` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_forme` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les Régimes Fiscaux (spécifiques au Maroc)
CREATE TABLE `regimes_fiscaux` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_regime` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les Employés
CREATE TABLE `employes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `role` ENUM('administrateur', 'gestionnaire', 'collaborateur') DEFAULT 'collaborateur' NOT NULL,
  `poste` VARCHAR(100),
  `date_embauche` DATE,
  `telephone` VARCHAR(20),
  `adresse` TEXT,
  `actif` BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les Clients
CREATE TABLE `clients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_entreprise` VARCHAR(255) NOT NULL,
  `ice` VARCHAR(50) UNIQUE,
  `rc` VARCHAR(50) UNIQUE,
  `patente` VARCHAR(50) UNIQUE,
  `cnss` VARCHAR(50) UNIQUE,
  `if_fiscale` VARCHAR(50) UNIQUE,
  `forme_juridique_id` INT(11),
  `regime_fiscal_id` INT(11),
  `adresse` TEXT,
  `telephone` VARCHAR(20),
  `email` VARCHAR(255),
  `employe_id` INT(11), -- Employé responsable du client
  `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`forme_juridique_id`) REFERENCES `formes_juridiques`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`regime_fiscal_id`) REFERENCES `regimes_fiscaux`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`employe_id`) REFERENCES `employes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les Types de Dossiers
CREATE TABLE `types_dossiers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_type` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les Dossiers
CREATE TABLE `dossiers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `client_id` INT(11) NOT NULL,
  `type_dossier_id` INT(11) NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `date_echeance` DATE NOT NULL,
  `statut` ENUM('en_attente', 'en_cours', 'termine', 'en_retard', 'annule') DEFAULT 'en_attente' NOT NULL,
  `commentaires` TEXT,
  `employe_responsable_id` INT(11), -- Employé assigné au dossier
  PRIMARY KEY (`id`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`type_dossier_id`) REFERENCES `types_dossiers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employe_responsable_id`) REFERENCES `employes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les Tâches
CREATE TABLE `taches` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `dossier_id` INT(11) NOT NULL,
  `employe_id` INT(11) NOT NULL, -- Employé assigné à la tâche
  `titre` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `date_debut` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `date_echeance` DATETIME NOT NULL,
  `statut` ENUM('a_faire', 'en_cours', 'terminee', 'en_retard') DEFAULT 'a_faire' NOT NULL,
  `priorite` ENUM('basse', 'moyenne', 'haute', 'urgente') DEFAULT 'moyenne' NOT NULL,
  `temps_estime_heures` DECIMAL(5,2),
  `temps_reel_heures` DECIMAL(5,2),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dossier_id`) REFERENCES `dossiers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employe_id`) REFERENCES `employes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les Documents
CREATE TABLE `documents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `dossier_id` INT(11) NOT NULL,
  `nom_fichier` VARCHAR(255) NOT NULL,
  `chemin_fichier` VARCHAR(512) NOT NULL,
  `type_mime` VARCHAR(100),
  `taille_ko` INT(11),
  `date_upload` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `uploader_id` INT(11), -- Employé qui a uploadé le document
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dossier_id`) REFERENCES `dossiers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploader_id`) REFERENCES `employes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les Logs d'activité
CREATE TABLE `logs_activite` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employe_id` INT(11),
  `action` VARCHAR(255) NOT NULL,
  `table_affectee` VARCHAR(100),
  `id_affecte` INT(11),
  `description` TEXT,
  `date_heure` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `adresse_ip` VARCHAR(45),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employe_id`) REFERENCES `employes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Insertion des données initiales pour formes_juridiques
INSERT INTO `formes_juridiques` (`nom_forme`, `description`) VALUES
('SA', 'Société Anonyme'),
('SARL', 'Société à Responsabilité Limitée'),
('SARL AU', 'Société à Responsabilité Limitée Associé Unique'),
('SNC', 'Société en Nom Collectif'),
('SCS', 'Société en Commandite Simple'),
('SCA', 'Société en Commandite par Actions'),
('SAS', 'Société par Actions Simplifiée'),
('EI', 'Entreprise Individuelle');

-- Insertion des données initiales pour regimes_fiscaux
INSERT INTO `regimes_fiscaux` (`nom_regime`, `description`) VALUES
('RNR', 'Régime du Résultat Net Réel'),
('RNS', 'Régime du Résultat Net Simplifié'),
('Forfaitaire', 'Régime du Bénéfice Forfaitaire'),
('Auto-Entrepreneur', 'Régime de l''Auto-Entrepreneur');

-- Insertion des données initiales pour types_dossiers
INSERT INTO `types_dossiers` (`nom_type`, `description`) VALUES
('Déclarations TVA (mensuelles)', 'Déclarations mensuelles de la Taxe sur la Valeur Ajoutée'),
('Déclarations IR/IS (annuelles)', 'Déclarations annuelles de l''Impôt sur le Revenu ou l''Impôt sur les Sociétés'),
('Déclarations CNSS (trimestrielles)', 'Déclarations trimestrielles à la Caisse Nationale de Sécurité Sociale'),
('Bilans comptables', 'Préparation et dépôt des bilans comptables annuels'),
('Liasses fiscales', 'Préparation et dépôt des liasses fiscales annuelles'),
('Déclarations Patente', 'Déclarations et paiement de la Taxe Professionnelle (Patente)'),
('Audits comptables', 'Réalisation d''audits comptables et financiers'),
('Constitutions de sociétés', 'Assistance à la création et l''immatriculation de nouvelles sociétés');