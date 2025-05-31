CREATE DATABASE juridis;

USE juridis;

-- Table Personnes (classe mère)
CREATE TABLE personnes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    type_personne ENUM('client', 'avocat') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table Clients
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    numero_client VARCHAR(50) UNIQUE,
    date_inscription DATE DEFAULT (CURRENT_DATE),
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE CASCADE
);

-- Table Avocats
CREATE TABLE avocats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    numero_barreau VARCHAR(50) UNIQUE NOT NULL,
    specialisation VARCHAR(100),
    certifie BOOLEAN DEFAULT FALSE,
    max_dossiers_actifs INT DEFAULT 10,
    dossiers_actifs_actuels INT DEFAULT 0,
    date_inscription_barreau DATE,
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE CASCADE
);

-- Table Types de Dossiers
CREATE TABLE types_dossiers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    exige_avocat_certifie BOOLEAN DEFAULT FALSE
);

-- Table Dossiers Juridiques
CREATE TABLE dossiers_juridiques (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_dossier VARCHAR(50) UNIQUE NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    type_dossier_id INT NOT NULL,
    client_id INT NOT NULL,
    avocat_id INT,
    statut ENUM('ouvert', 'en_cours', 'cloture', 'archive') DEFAULT 'ouvert',
    date_ouverture DATE DEFAULT (CURRENT_DATE),
    date_cloture DATE NULL,
    priorite ENUM('basse', 'normale', 'haute', 'urgente') DEFAULT 'normale',
    toutes_etapes_validees BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (type_dossier_id) REFERENCES types_dossiers(id),
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (avocat_id) REFERENCES avocats(id)
);

-- Table Étapes
CREATE TABLE etapes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dossier_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    ordre_execution INT NOT NULL,
    statut ENUM('en_attente', 'en_cours', 'validee', 'annulee') DEFAULT 'en_attente',
    date_debut DATE,
    date_fin_prevue DATE,
    date_validation DATE,
    validee_par_avocat_id INT,
    obligatoire BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dossier_id) REFERENCES dossiers_juridiques(id) ON DELETE CASCADE,
    FOREIGN KEY (validee_par_avocat_id) REFERENCES avocats(id)
);

-- Table Documents
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dossier_id INT NOT NULL,
    nom_document VARCHAR(200) NOT NULL,
    chemin_fichier VARCHAR(500),
    type_document VARCHAR(100),
    taille_fichier INT,
    telecharge_par_type ENUM('client', 'avocat') NOT NULL,
    telecharge_par_id INT NOT NULL,
    date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dossier_id) REFERENCES dossiers_juridiques(id) ON DELETE CASCADE
);

-- Table Historique
CREATE TABLE historique (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dossier_id INT NOT NULL,
    type_action ENUM('creation', 'changement_avocat', 'ajout_document', 'validation_etape', 'changement_statut', 'cloture') NOT NULL,
    description TEXT NOT NULL,
    effectue_par_type ENUM('client', 'avocat', 'systeme') NOT NULL,
    effectue_par_id INT,
    ancienne_valeur TEXT,
    nouvelle_valeur TEXT,
    date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dossier_id) REFERENCES dossiers_juridiques(id) ON DELETE CASCADE
);

-- Table Étapes Types
CREATE TABLE etapes_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_dossier_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    ordre_execution INT NOT NULL,
    obligatoire BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (type_dossier_id) REFERENCES types_dossiers(id)
);
