CREATE TABLE utilisateurs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(80) NOT NULL,
    prenom VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('client','agent','inventaire') NOT NULL DEFAULT 'client',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories_equipement (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    nom VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    tarif_journalier DECIMAL(10,2) NOT NULL,
    quantite_totale INT UNSIGNED NOT NULL,
    quantite_maintenance INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_endommagee INT UNSIGNED NOT NULL DEFAULT 0,
    seuil_alerte INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_equipement_categorie FOREIGN KEY (categorie_id) REFERENCES categories_equipement(id),
    CONSTRAINT ck_stock CHECK (quantite_maintenance + quantite_endommagee <= quantite_totale),
    CONSTRAINT ck_tarif CHECK (tarif_journalier > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    agent_id INT UNSIGNED NULL,
    equipement_id INT UNSIGNED NOT NULL,
    quantite INT UNSIGNED NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    statut ENUM('en_attente','confirmee','en_cours','retour_a_controler','terminee','refusee','annulee') NOT NULL DEFAULT 'en_attente',
    tarif_applique DECIMAL(10,2) NOT NULL,
    frais_additionnels DECIMAL(10,2) NOT NULL DEFAULT 0,
    date_retour DATE NULL,
    retour_maintenance INT UNSIGNED NOT NULL DEFAULT 0,
    retour_endommage INT UNSIGNED NOT NULL DEFAULT 0,
    remarques_retour TEXT NOT NULL,
    montant_paye DECIMAL(12,2) NOT NULL DEFAULT 0,
    date_paiement DATETIME NULL,
    snapshot LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_location_client FOREIGN KEY (client_id) REFERENCES utilisateurs(id),
    CONSTRAINT fk_location_agent FOREIGN KEY (agent_id) REFERENCES utilisateurs(id),
    CONSTRAINT fk_location_equipement FOREIGN KEY (equipement_id) REFERENCES equipements(id),
    CONSTRAINT ck_dates CHECK (date_fin >= date_debut),
    CONSTRAINT ck_quantite CHECK (quantite > 0),
    CONSTRAINT ck_retour CHECK (retour_maintenance + retour_endommage <= quantite),
    INDEX ix_location_disponibilite (equipement_id, statut, date_debut, date_fin),
    INDEX ix_location_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
