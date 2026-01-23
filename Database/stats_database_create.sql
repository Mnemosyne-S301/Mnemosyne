-- ==============================================================================
-- FICHIER : 01_create_tables.sql
-- CRÉATION DE LA STRUCTURE DES TABLES DE STATISTIQUES
-- ==============================================================================

-- 1. NETTOYAGE
DROP TABLE IF EXISTS nb_eleve_par_formation;
DROP TABLE IF EXISTS nb_ue_par_formation_semestre;
DROP TABLE IF EXISTS res_ue_par_annee_par_eleve;
DROP TABLE IF EXISTS nb_ue_valide_par_annee_par_eleve;
DROP TABLE IF EXISTS repartition_notes_par_parcours;
DROP TABLE IF EXISTS nb_rcue_valide_par_annee_par_eleve;
DROP TABLE IF EXISTS repartition_rcue_par_parcours;


-- ==============================================================================
-- PARTIE 1 : STATISTIQUES GENERALES
-- ==============================================================================

-- 2. RECENSEMENT DES ETUDIANTS

CREATE TABLE nb_eleve_par_formation (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    
    dep VARCHAR(255),
    formation VARCHAR(255),
    parcours VARCHAR(255),
    annee_scolaire INT, -- Ajouté pour pouvoir suivre l'évolution par année
    nombre_etudiants INT,
    
    -- Sécurité : On ne veut qu'une seule ligne par promo/année
    UNIQUE KEY idx_unique_promo (dep, formation, parcours, annee_scolaire)
);

CREATE TABLE nb_ue_par_formation_semestre(
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dep VARCHAR(255),
    formation VARCHAR(255),
    parcours VARCHAR(255),
    annee_scolaire INT,
    semestre_num INT,
    nb_ue INT
    UNIQUE KEY idx_unique_ue(dep,formation,id_formsemetre)
    
);

-- ==============================================================================
-- PARTIE 2 : ANALYSE DES UE (UNITES D'ENSEIGNEMENT)
-- ==============================================================================

-- 3. CONSOLIDATION DES RESULTATS UE
CREATE TABLE res_ue_par_annee_par_eleve (
    dep VARCHAR(255),
    formation VARCHAR(255),
    parcours VARCHAR(255),
    annee_scolaire INT,
    etudiant_id INT, -- INT simple comme demandé
    resultat_ue VARCHAR(50),
    
    -- Ici la clé primaire sert de Unique Key : 
    -- Un étudiant ne peut avoir qu'un seul résultat cumulé pour une année et un type de note
    PRIMARY KEY (etudiant_id, annee_scolaire, resultat_ue) 
);

-- 4. COMPTAGE DES UE VALIDEES PAR ETUDIANT
CREATE TABLE nb_ue_valide_par_annee_par_eleve (
    dep VARCHAR(255),
    formation VARCHAR(255),
    parcours VARCHAR(255),
    annee_scolaire INT,
    etudiant_id INT,
    nb_ue_validees INT,
    
    -- Un étudiant n'a qu'un seul score "nombre d'UE" par année
    PRIMARY KEY (etudiant_id, annee_scolaire)
);

-- 5. REPARTITION DES RESULTATS UE (Pour les graphiques)
CREATE TABLE repartition_notes_par_parcours (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dep VARCHAR(255),
    formation VARCHAR(255),
    parcours VARCHAR(255),
    annee_scolaire INT,
    nb_ue_validees INT, -- Le score (ex: 6 UE)
    nb_eleves INT,      -- Combien d'élèves ont eu ce score
    
    UNIQUE KEY idx_repart_unique (dep, formation, parcours, annee_scolaire, nb_ue_validees)
);


-- ==============================================================================
-- PARTIE 3 : ANALYSE DES RCUE (COMPETENCES)
-- ==============================================================================

-- 6. COMPTAGE DES RCUE VALIDES PAR ETUDIANT
CREATE TABLE nb_rcue_valide_par_annee_par_eleve (
    dep VARCHAR(255),
    formation VARCHAR(255),
    parcours VARCHAR(255),
    annee_scolaire INT,
    etudiant_id INT,
    nb_rcue_valides INT,
    
    PRIMARY KEY (etudiant_id, annee_scolaire)
);

-- 7. REPARTITION DES RESULTATS RCUE
CREATE TABLE repartition_rcue_par_parcours (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dep VARCHAR(255),
    formation VARCHAR(255),
    parcours VARCHAR(255),
    annee_scolaire INT,
    nb_rcue_valides INT,
    nb_eleves INT,
    
    UNIQUE KEY idx_repart_rcue_unique (dep, formation, parcours, annee_scolaire, nb_rcue_valides)
);
