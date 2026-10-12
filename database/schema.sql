-- Base de données : application_gestion_materielle
-- Script SQL complet pour MySQL / MariaDB

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS application_gestion_materielle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE application_gestion_materielle;

-- --------------------------------------------------------
-- Table : roles
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des rôles par défaut
INSERT IGNORE INTO roles (nom, description) VALUES
('Administrateur', 'Accès complet à toutes les fonctionnalités'),
('Gestionnaire', 'Gestion des matériels, affectations et rapports');

-- --------------------------------------------------------
-- Table : users
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL DEFAULT 2,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    dernier_connexion TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : categories
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : services
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    responsable VARCHAR(100),
    localisation VARCHAR(100),
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : materiels
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS materiels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_interne VARCHAR(50) NOT NULL UNIQUE,
    designation VARCHAR(200) NOT NULL,
    categorie_id INT,
    marque VARCHAR(100),
    modele VARCHAR(100),
    numero_serie VARCHAR(100),
    quantite_totale INT NOT NULL DEFAULT 0,
    quantite_disponible INT NOT NULL DEFAULT 0,
    quantite_affectee INT NOT NULL DEFAULT 0,
    etat VARCHAR(50) NOT NULL DEFAULT 'Disponible',
    date_entree DATE NOT NULL,
    provenance VARCHAR(200),
    observations TEXT,
    code_barre VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_materiels_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index sur code_interne pour recherche rapide
CREATE INDEX idx_materiels_code_interne ON materiels(code_interne);
-- Index sur code_barre
CREATE INDEX idx_materiels_code_barre ON materiels(code_barre);
-- Index sur categorie
CREATE INDEX idx_materiels_categorie ON materiels(categorie_id);

-- --------------------------------------------------------
-- Table : entrees_stock
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS entrees_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materiel_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    date_entree DATE NOT NULL,
    fournisseur VARCHAR(200),
    observations TEXT,
    utilisateur_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_entrees_materiel FOREIGN KEY (materiel_id) REFERENCES materiels(id) ON DELETE CASCADE,
    CONSTRAINT fk_entrees_user FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : affectations
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS affectations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_interne VARCHAR(50) NOT NULL,
    materiel_id INT NOT NULL,
    service_id INT NOT NULL,
    quantite_affectee INT NOT NULL DEFAULT 1,
    date_affectation DATE NOT NULL,
    utilisateur_id INT NOT NULL,
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_affectations_materiel FOREIGN KEY (materiel_id) REFERENCES materiels(id) ON DELETE CASCADE,
    CONSTRAINT fk_affectations_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    CONSTRAINT fk_affectations_user FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : retours
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS retours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    affectation_id INT NOT NULL,
    materiel_id INT NOT NULL,
    quantite_retournee INT NOT NULL DEFAULT 1,
    etat_retour VARCHAR(50) NOT NULL,
    date_retour DATE NOT NULL,
    observations TEXT,
    utilisateur_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_retours_affectation FOREIGN KEY (affectation_id) REFERENCES affectations(id) ON DELETE CASCADE,
    CONSTRAINT fk_retours_materiel FOREIGN KEY (materiel_id) REFERENCES materiels(id) ON DELETE CASCADE,
    CONSTRAINT fk_retours_user FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : mouvements
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materiel_id INT NOT NULL,
    type_operation ENUM('entree', 'affectation', 'retour', 'reglare') NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    ancien_etat VARCHAR(50),
    nouvel_etat VARCHAR(50),
    service_id INT,
    utilisateur_id INT NOT NULL,
    date_mouvement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observations TEXT,
    CONSTRAINT fk_mouvements_materiel FOREIGN KEY (materiel_id) REFERENCES materiels(id) ON DELETE CASCADE,
    CONSTRAINT fk_mouvements_user FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : historique_materiels
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS historique_materiels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materiel_id INT NOT NULL,
    evenement VARCHAR(50) NOT NULL,
    date_heure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilisateur_id INT,
    quantite INT DEFAULT 0,
    service_id INT,
    reference_operation VARCHAR(100),
    observations TEXT,
    CONSTRAINT fk_historique_materiel FOREIGN KEY (materiel_id) REFERENCES materiels(id) ON DELETE CASCADE,
    CONSTRAINT fk_historique_user FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index pour recherche historique
CREATE INDEX idx_historique_materiel ON historique_materiels(materiel_id, date_heure);
CREATE INDEX idx_historique_evenement ON historique_materiels(evenement);

-- --------------------------------------------------------
-- Vues utiles
-- --------------------------------------------------------

-- Vue : état actuel des matériels
CREATE OR REPLACE VIEW v_etat_materiels AS
SELECT
    m.id,
    m.code_interne,
    m.designation,
    c.nom AS categorie,
    m.etat,
    m.quantite_totale,
    m.quantite_disponible,
    m.quantite_affectee,
    s.nom AS service_nom,
    GROUP_CONCAT(DISTINCT a.id) AS affectations_actives
FROM materiels m
LEFT JOIN categories c ON m.categorie_id = c.id
LEFT JOIN (
    SELECT materiel_id, SUM(quantite_affectee) as total_affectee, service_id
    FROM affectations
    WHERE id IN (
        SELECT MAX(id) FROM affectations GROUP BY materiel_id
    )
    GROUP BY materiel_id
) a ON m.id = a.materiel_id
LEFT JOIN services s ON a.service_id = s.id
GROUP BY m.id;

-- Vue : récapitulatif des quantités
CREATE OR REPLACE VIEW v_quantites_restock AS
SELECT
    m.id,
    m.code_interne,
    m.designation,
    c.nom AS categorie,
    m.etat,
    m.quantite_totale,
    m.quantite_disponible,
    m.quantite_affectee,
    (m.quantite_totale - m.quantite_affectee) as calcul_disponible
FROM materiels m
LEFT JOIN categories c ON m.categorie_id = c.id;

-- --------------------------------------------------------
-- Procédures stockées utiles
-- --------------------------------------------------------

-- Procédure : Ajouter une entrée de stock
DELIMITER //
CREATE PROCEDURE sp_ajouter_entree(
    IN p_materiel_id INT,
    IN p_quantite INT,
    IN p_fournisseur VARCHAR(200),
    IN p_utilisateur_id INT,
    IN p_observations TEXT
)
BEGIN
    DECLARE v_disponible_actuelle INT;

    -- Démarrage transaction
    START TRANSACTION;

    -- Enregistrer l'entrée
    INSERT INTO entrees_stock (materiel_id, quantite, date_entree, fournisseur, observations, utilisateur_id)
    VALUES (p_materiel_id, p_quantite, CURDATE(), p_fournisseur, p_observations, p_utilisateur_id);

    -- Mettre à jour la quantité disponible
    UPDATE materiels
    SET quantite_disponible = quantite_disponible + p_quantite,
        quantite_totale = quantite_totale + p_quantite,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_materiel_id;

    -- Enregistrer dans l'historique
    INSERT INTO historique_materiels (materiel_id, evenement, utilisateur_id, quantite, service_id, observations)
    VALUES (p_materiel_id, 'entree', p_utilisateur_id, p_quantite, NULL, p_observations);

    -- Enregistrer le mouvement
    INSERT INTO mouvements (materiel_id, type_operation, quantite, utilisateur_id, observations)
    VALUES (p_materiel_id, 'entree', p_quantite, p_utilisateur_id, p_observations);

    COMMIT;
END //
DELIMITER ;

-- Procédure : Effectuer une affectation
DELIMITER //
CREATE PROCEDURE sp_faire_affectation(
    IN p_materiel_id INT,
    IN p_service_id INT,
    IN p_quantite INT,
    IN p_utilisateur_id INT,
    IN p_observations TEXT
)
BEGIN
    DECLARE v_disponible INT;
    DECLARE v_total INT;

    -- Démarrage transaction
    START TRANSACTION;

    -- Vérifier la disponibilité
    SELECT quantite_disponible, quantite_totale INTO v_disponible, v_total
    FROM materiels
    WHERE id = p_materiel_id
    FOR UPDATE;

    IF p_quantite > v_disponible THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT 'Quantité demandée supérieure à la quantité disponible';
    END IF;

    -- Enregistrer l'affectation
    INSERT INTO affectations (code_interne, materiel_id, service_id, quantite_affectee, date_affectation, utilisateur_id, observations)
    VALUES (
        (SELECT code_interne FROM materiels WHERE id = p_materiel_id),
        p_materiel_id, p_service_id, p_quantite, CURDATE(), p_utilisateur_id, p_observations
    );

    -- Mettre à jour les quantités
    UPDATE materiels
    SET quantite_disponible = quantite_disponible - p_quantite,
        quantite_affectee = quantite_affectee + p_quantite,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_materiel_id;

    -- Enregistrer dans l'historique
    INSERT INTO historique_materiels (materiel_id, evenement, utilisateur_id, quantite, service_id, observations)
    VALUES (p_materiel_id, 'affectation', p_utilisateur_id, p_quantite, p_service_id, p_observations);

    -- Enregistrer le mouvement
    INSERT INTO mouvements (materiel_id, type_operation, quantite, utilisateur_id, observations)
    VALUES (p_materiel_id, 'affectation', p_quantite, p_utilisateur_id, p_observations);

    COMMIT;
END //
DELIMITER ;

-- Procédure : Enregistrer un retour
DELIMITER //
CREATE PROCEDURE sp_enregistrer_retour(
    IN p_affectation_id INT,
    IN p_materiel_id INT,
    IN p_quantite_retournee INT,
    IN p_etat_retour VARCHAR(50),
    IN p_utilisateur_id INT,
    IN p_observations TEXT
)
BEGIN
    DECLARE v_quantite_affectee INT;
    DECLARE v_quantite_disponible INT;

    -- Démarrage transaction
    START TRANSACTION;

    -- Récupérer la quantité affectée pour cet affectation
    SELECT quantite_affectee INTO v_quantite_affectee
    FROM affectations
    WHERE id = p_affectation_id;

    IF p_quantite_retournee > v_quantite_affectee THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT 'Quantité de retour supérieure à la quantité affectée';
    END IF;

    -- Mettre à jour l'affectation
    UPDATE affectations
    SET quantite_affectee = quantite_affectee - p_quantite_retournee,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_affectation_id;

    -- Enregistrer le retour
    INSERT INTO retours (affectation_id, materiel_id, quantite_retournee, etat_retour, date_retour, utilisateur_id, observations)
    VALUES (p_affectation_id, p_materiel_id, p_quantite_retournee, p_etat_retour, CURDATE(), p_utilisateur_id, p_observations);

    -- Déterminer si le matériel revient en stock ou en état particulier
    -- Si l'état n'est pas "bon" ou "neuf", on le marque spécifiquement
    IF p_etat_retour IN ('En panne', 'Mauvais état', 'État moyen') THEN
        -- Vérifier l'état actuel du matériel
        UPDATE materiels
        SET etat = p_etat_retour,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_materiel_id;
    ELSE
        -- Remise en stock : augmenter la quantité disponible
        UPDATE materiels
        SET quantite_disponible = quantite_disponible + p_quantite_retournee,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_materiel_id;
    END IF;

    -- Enregistrer dans l'historique
    INSERT INTO historique_materiels (materiel_id, evenement, utilisateur_id, quantite, service_id, observations)
    VALUES (p_materiel_id, 'retour', p_utilisateur_id, p_quantite_retournee, NULL, p_observations);

    -- Enregistrer le mouvement
    INSERT INTO mouvements (materiel_id, type_operation, quantite, utilisateur_id, observations)
    VALUES (p_materiel_id, 'retour', p_quantite_retournee, p_utilisateur_id, p_observations);

    COMMIT;
END //
DELIMITER ;

-- Procédure : Obtenir l'historique complet d'un matériel
DELIMITER //
CREATE PROCEDURE sp_get_historique_materiel(IN p_materiel_id INT)
BEGIN
    SELECT
        hm.id,
        hm.date_heure,
        hm.evenement,
        hm.quantite,
        u.username as utilisateur,
        hm.observations,
        s.nom as service,
        hm.reference_operation
    FROM historique_materiels hm
    LEFT JOIN users u ON hm.utilisateur_id = u.id
    LEFT JOIN services s ON hm.service_id = s.id
    WHERE hm.materiel_id = p_materiel_id
    ORDER BY hm.date_heure DESC;
END //
DELIMITER ;

-- Procédure : Vérifier l'état d'un matériel
DELIMITER //
CREATE PROCEDURE sp_verifier_etat_materiel(IN p_materiel_id INT)
BEGIN
    SELECT
        m.id,
        m.code_interne,
        m.designation,
        m.etat,
        m.quantite_disponible,
        m.quantite_affectee,
        CASE
            WHEN m.quantite_affectee >= m.quantite_totale THEN 'Totalement affecté'
            WHEN m.quantite_disponible <= 0 THEN 'Rupture de stock'
            WHEN m.etat = 'En panne' THEN 'Indisponible (panne)'
            ELSE 'Disponible'
        END as statut_actuel
    FROM materiels m
    WHERE m.id = p_materiel_id;
END //
DELIMITER ;

-- --------------------------------------------------------
-- Fonction : Générer un code interne unique
-- --------------------------------------------------------

CREATE FUNCTION fn_generer_code_interne() RETURNS VARCHAR(50)
DETERMINISTIC
BEGIN
    DECLARE v_code VARCHAR(50);
    DECLARE v_existe INT;

    boucle: LOOP
        SET v_code = CONCAT('MAT-', LPAD(FLOOR(RAND() * 90000) + 1000, 5, '0'));

        SELECT COUNT(*) INTO v_existe
        FROM materiels
        WHERE code_interne = v_code;

        IF v_existe = 0 THEN
            LEAVE boucle;
        END IF;
    END LOOP;

    RETURN v_code;
END;

// Assurez-vous que le fichier se termine par un point de nouvelle ligne