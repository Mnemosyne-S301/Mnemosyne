SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS RegleScenario;
DROP TABLE IF EXISTS FluxCohorte;
DROP TABLE IF EXISTS CohorteEtudiant;
DROP TABLE IF EXISTS Cohorte;
DROP TABLE IF EXISTS AutorisationPassage;
DROP TABLE IF EXISTS ResultatRCUE_UE;
DROP TABLE IF EXISTS ResultatRCUE;
DROP TABLE IF EXISTS ResultatUE;
DROP TABLE IF EXISTS FormSemestreUE;
DROP TABLE IF EXISTS UE;
DROP TABLE IF EXISTS DecisionAnnee;
DROP TABLE IF EXISTS CodeDecision;
DROP TABLE IF EXISTS PositionAnnuelleEtudiant;
DROP TABLE IF EXISTS InscriptionSemestre;
DROP TABLE IF EXISTS Etudiant;
DROP TABLE IF EXISTS FormSemestreParcours;
DROP TABLE IF EXISTS FormSemestre;
DROP TABLE IF EXISTS ApprentissageCritique;
DROP TABLE IF EXISTS NiveauCompetence;
DROP TABLE IF EXISTS Competence;
DROP TABLE IF EXISTS ReferentielCompetence;
DROP TABLE IF EXISTS AnneeFormation;
DROP TABLE IF EXISTS Parcours;
DROP TABLE IF EXISTS Formation;
DROP TABLE IF EXISTS Departement;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. Structure IUT
-- ============================================================

CREATE TABLE Departement(
    dep_id INT PRIMARY KEY,
    acronyme VARCHAR(50) NOT NULL,
    nom_dep VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    visible BOOLEAN DEFAULT TRUE,
    date_creation DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Formation(
    formation_id BIGINT UNSIGNED PRIMARY KEY,
    acronyme VARCHAR(100) NOT NULL,
    titre VARCHAR(255) NOT NULL,
    titre_officiel VARCHAR(255),
    formation_code VARCHAR(100) NOT NULL,
    version TINYINT UNSIGNED,
    type_parcours INT UNSIGNED,
    commentaire VARCHAR(255) DEFAULT NULL,
    code_specialite VARCHAR(100) DEFAULT NULL,
    archived BOOLEAN DEFAULT FALSE,
    referentiel_competence_id BIGINT UNSIGNED DEFAULT NULL,
    dep_id INT NOT NULL,
    FOREIGN KEY (dep_id) REFERENCES Departement(dep_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Parcours(
    parcours_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    numero INT DEFAULT NULL,
    libelle VARCHAR(255) NOT NULL,
    formation_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (formation_id) REFERENCES Formation(formation_id) ON DELETE CASCADE,
    UNIQUE KEY uq_parcours_formation_code (formation_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE AnneeFormation(
    anneeformation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ordre TINYINT UNSIGNED NOT NULL,
    libelle VARCHAR(50),
    parcours_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (parcours_id) REFERENCES Parcours(parcours_id) ON DELETE CASCADE,
    UNIQUE KEY uq_annee_parcours_ordre (parcours_id, ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. Referentiel de competences BUT
-- ============================================================

CREATE TABLE ReferentielCompetence(
    referentiel_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dept_id INT,
    specialite VARCHAR(100),
    specialite_long VARCHAR(255),
    type_structure VARCHAR(100),
    type_departement VARCHAR(100),
    type_titre VARCHAR(100),
    version_orebut DATETIME,
    scodoc_date_loaded DATETIME,
    scodoc_orig_filename VARCHAR(255),
    FOREIGN KEY (dept_id) REFERENCES Departement(dep_id) ON DELETE SET NULL,
    UNIQUE KEY uq_ref_dept_specialite (dept_id, specialite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Competence(
    competence_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_orebut VARCHAR(255),
    titre VARCHAR(100) NOT NULL,
    titre_long VARCHAR(255),
    couleur VARCHAR(50),
    numero INT,
    referentiel_id BIGINT UNSIGNED,
    FOREIGN KEY (referentiel_id) REFERENCES ReferentielCompetence(referentiel_id) ON DELETE CASCADE,
    UNIQUE KEY uq_comp_ref_orebut (referentiel_id, id_orebut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE NiveauCompetence(
    niveaucompetence_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competence_id BIGINT UNSIGNED NOT NULL,
    anneeformation_id BIGINT UNSIGNED DEFAULT NULL,
    niveau INT,
    libelle VARCHAR(255),
    ordre INT,
    FOREIGN KEY (competence_id) REFERENCES Competence(competence_id) ON DELETE CASCADE,
    FOREIGN KEY (anneeformation_id) REFERENCES AnneeFormation(anneeformation_id) ON DELETE SET NULL,
    UNIQUE KEY uq_niveau_comp_ordre (competence_id, ordre, niveau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ApprentissageCritique(
    apprentissage_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    libelle VARCHAR(255),
    oid INT,
    niveaucompetence_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (niveaucompetence_id) REFERENCES NiveauCompetence(niveaucompetence_id) ON DELETE CASCADE,
    UNIQUE KEY uq_ac_niveau_code (niveaucompetence_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. Semestres ScoDoc
-- ============================================================

CREATE TABLE FormSemestre(
    formsemestre_id INT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    titre_court VARCHAR(100),
    titre_num VARCHAR(255),
    session_id VARCHAR(255),
    modalite VARCHAR(50),
    semestre_id INT,
    annee_scolaire INT UNSIGNED,
    date_debut DATE,
    date_fin DATE,
    etape_apo VARCHAR(255),
    elt_sem_apo VARCHAR(255),
    elt_annee_apo VARCHAR(255),
    etat BOOLEAN DEFAULT FALSE,
    capacite_accueil INT DEFAULT NULL,
    formation_id BIGINT UNSIGNED NOT NULL,
    dep_id INT NOT NULL,
    FOREIGN KEY (formation_id) REFERENCES Formation(formation_id) ON DELETE CASCADE,
    FOREIGN KEY (dep_id) REFERENCES Departement(dep_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE FormSemestreParcours(
    formsemestre_id INT NOT NULL,
    parcours_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(formsemestre_id, parcours_id),
    FOREIGN KEY (formsemestre_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE CASCADE,
    FOREIGN KEY (parcours_id) REFERENCES Parcours(parcours_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. Etudiants, inscriptions et positions annuelles
-- ============================================================

CREATE TABLE Etudiant(
    etudiant_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scodoc_etudid VARCHAR(255) NOT NULL,
    code_nip VARCHAR(255),
    code_ine VARCHAR(255),
    is_apc BOOLEAN DEFAULT TRUE,
    etat VARCHAR(50),
    nb_competences INT,
    UNIQUE KEY uq_etudiant_scodoc (scodoc_etudid),
    UNIQUE KEY uq_etudiant_nip (code_nip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE InscriptionSemestre(
    inscription_semestre_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etudiant_id BIGINT UNSIGNED NOT NULL,
    formsemestre_id INT NOT NULL,
    parcours_id BIGINT UNSIGNED DEFAULT NULL,
    annee_scolaire INT UNSIGNED,
    semestre_id INT,
    modalite VARCHAR(50),
    etat_inscription VARCHAR(50),
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (formsemestre_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE CASCADE,
    FOREIGN KEY (parcours_id) REFERENCES Parcours(parcours_id) ON DELETE SET NULL,
    UNIQUE KEY uq_inscription_etud_fs (etudiant_id, formsemestre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table centrale pour les cohortes et les statistiques
CREATE TABLE PositionAnnuelleEtudiant(
    position_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etudiant_id BIGINT UNSIGNED NOT NULL,
    annee_scolaire INT UNSIGNED NOT NULL,
    dep_id INT DEFAULT NULL,
    formation_id BIGINT UNSIGNED DEFAULT NULL,
    parcours_id BIGINT UNSIGNED DEFAULT NULL,
    anneeformation_id BIGINT UNSIGNED DEFAULT NULL,
    ordre_annee TINYINT UNSIGNED,
    modalite VARCHAR(50),
    formsemestre_reference_id INT DEFAULT NULL,
    code_decision_id BIGINT UNSIGNED DEFAULT NULL,
    statut_position VARCHAR(100),
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (dep_id) REFERENCES Departement(dep_id) ON DELETE SET NULL,
    FOREIGN KEY (formation_id) REFERENCES Formation(formation_id) ON DELETE SET NULL,
    FOREIGN KEY (parcours_id) REFERENCES Parcours(parcours_id) ON DELETE SET NULL,
    FOREIGN KEY (anneeformation_id) REFERENCES AnneeFormation(anneeformation_id) ON DELETE SET NULL,
    FOREIGN KEY (formsemestre_reference_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE SET NULL,
    UNIQUE KEY uq_position_etud_year_fs (etudiant_id, annee_scolaire, formsemestre_reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. Decisions et resultats
-- ============================================================

CREATE TABLE CodeDecision(
    codedecision_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    type_decision ENUM('ANNEE', 'UE', 'RCUE', 'AUTRE') NOT NULL,
    signification VARCHAR(255),
    categorie_flux VARCHAR(100),
    est_reussite BOOLEAN DEFAULT FALSE,
    est_sortie BOOLEAN DEFAULT FALSE,
    est_redoublement BOOLEAN DEFAULT FALSE,
    UNIQUE KEY uq_code_decision (code, type_decision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE DecisionAnnee(
    decision_annee_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etudiant_id BIGINT UNSIGNED NOT NULL,
    formsemestre_id INT NOT NULL,
    annee_scolaire INT UNSIGNED NOT NULL,
    ordre_annee TINYINT UNSIGNED,
    codedecision_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (formsemestre_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE CASCADE,
    FOREIGN KEY (codedecision_id) REFERENCES CodeDecision(codedecision_id) ON DELETE CASCADE,
    UNIQUE KEY uq_decision_etud_fs_year (etudiant_id, formsemestre_id, annee_scolaire)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE UE(
    ue_id INT PRIMARY KEY,
    code_ue VARCHAR(100),
    libelle VARCHAR(255),
    numero INT,
    competence_id BIGINT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (competence_id) REFERENCES Competence(competence_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE FormSemestreUE(
    formsemestre_id INT NOT NULL,
    ue_id INT NOT NULL,
    PRIMARY KEY(formsemestre_id, ue_id),
    FOREIGN KEY (formsemestre_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE CASCADE,
    FOREIGN KEY (ue_id) REFERENCES UE(ue_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ResultatUE(
    resultat_ue_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etudiant_id BIGINT UNSIGNED NOT NULL,
    ue_id INT NOT NULL,
    formsemestre_id INT NOT NULL,
    annee_scolaire INT UNSIGNED NOT NULL,
    codedecision_id BIGINT UNSIGNED NOT NULL,
    ects DECIMAL(5,2),
    moyenne DECIMAL(8,4),
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (ue_id) REFERENCES UE(ue_id) ON DELETE CASCADE,
    FOREIGN KEY (formsemestre_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE CASCADE,
    FOREIGN KEY (codedecision_id) REFERENCES CodeDecision(codedecision_id) ON DELETE CASCADE,
    UNIQUE KEY uq_resultat_ue (etudiant_id, ue_id, formsemestre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ResultatRCUE(
    resultat_rcue_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etudiant_id BIGINT UNSIGNED NOT NULL,
    formsemestre_id INT NOT NULL,
    competence_id BIGINT UNSIGNED DEFAULT NULL,
    annee_scolaire INT UNSIGNED NOT NULL,
    ordre_rcue INT NOT NULL,
    codedecision_id BIGINT UNSIGNED NOT NULL,
    moyenne DECIMAL(8,4),
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (formsemestre_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE CASCADE,
    FOREIGN KEY (competence_id) REFERENCES Competence(competence_id) ON DELETE SET NULL,
    FOREIGN KEY (codedecision_id) REFERENCES CodeDecision(codedecision_id) ON DELETE CASCADE,
    UNIQUE KEY uq_resultat_rcue (etudiant_id, formsemestre_id, ordre_rcue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ResultatRCUE_UE(
    resultat_rcue_id BIGINT UNSIGNED NOT NULL,
    ue_id INT NOT NULL,
    position_ue TINYINT UNSIGNED NOT NULL,
    moyenne_ue DECIMAL(8,4),
    codedecision_id BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY(resultat_rcue_id, ue_id, position_ue),
    FOREIGN KEY (resultat_rcue_id) REFERENCES ResultatRCUE(resultat_rcue_id) ON DELETE CASCADE,
    FOREIGN KEY (ue_id) REFERENCES UE(ue_id) ON DELETE CASCADE,
    FOREIGN KEY (codedecision_id) REFERENCES CodeDecision(codedecision_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE AutorisationPassage(
    autorisation_id BIGINT UNSIGNED PRIMARY KEY,
    etudiant_id BIGINT UNSIGNED NOT NULL,
    origin_formsemestre_id INT DEFAULT NULL,
    formation_code VARCHAR(100),
    semestre_id_autorise INT,
    date_autorisation DATETIME,
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (origin_formsemestre_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. Cohortes et flux Sankey
-- ============================================================

CREATE TABLE Cohorte(
    cohorte_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    annee_entree INT UNSIGNED NOT NULL,
    dep_id INT DEFAULT NULL,
    formation_id BIGINT UNSIGNED DEFAULT NULL,
    parcours_id BIGINT UNSIGNED DEFAULT NULL,
    modalite VARCHAR(50),
    libelle VARCHAR(255),
    FOREIGN KEY (dep_id) REFERENCES Departement(dep_id) ON DELETE SET NULL,
    FOREIGN KEY (formation_id) REFERENCES Formation(formation_id) ON DELETE SET NULL,
    FOREIGN KEY (parcours_id) REFERENCES Parcours(parcours_id) ON DELETE SET NULL,
    UNIQUE KEY uq_cohorte (annee_entree, formation_id, parcours_id, modalite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE CohorteEtudiant(
    cohorte_id BIGINT UNSIGNED NOT NULL,
    etudiant_id BIGINT UNSIGNED NOT NULL,
    date_entree DATE DEFAULT NULL,
    type_entree VARCHAR(100),
    PRIMARY KEY(cohorte_id, etudiant_id),
    FOREIGN KEY (cohorte_id) REFERENCES Cohorte(cohorte_id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE FluxCohorte(
    flux_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cohorte_id BIGINT UNSIGNED NOT NULL,
    annee_scolaire_source INT UNSIGNED NOT NULL,
    annee_scolaire_cible INT UNSIGNED NOT NULL,
    source_label VARCHAR(255) NOT NULL,
    cible_label VARCHAR(255) NOT NULL,
    nb_etudiants INT UNSIGNED NOT NULL,
    filtre_departement VARCHAR(100),
    filtre_formation VARCHAR(100),
    filtre_modalite VARCHAR(50),
    filtre_parcours VARCHAR(100),
    FOREIGN KEY (cohorte_id) REFERENCES Cohorte(cohorte_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. Scenarios administrables
-- ============================================================

CREATE TABLE RegleScenario(
    regle_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    formation_source_id BIGINT UNSIGNED DEFAULT NULL,
    parcours_source_id BIGINT UNSIGNED DEFAULT NULL,
    modalite_source VARCHAR(50),
    ordre_source TINYINT UNSIGNED,
    code_decision_source VARCHAR(50),
    formation_cible_id BIGINT UNSIGNED DEFAULT NULL,
    parcours_cible_id BIGINT UNSIGNED DEFAULT NULL,
    modalite_cible VARCHAR(50),
    ordre_cible TINYINT UNSIGNED,
    categorie_flux VARCHAR(100),
    actif BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (formation_source_id) REFERENCES Formation(formation_id) ON DELETE SET NULL,
    FOREIGN KEY (parcours_source_id) REFERENCES Parcours(parcours_id) ON DELETE SET NULL,
    FOREIGN KEY (formation_cible_id) REFERENCES Formation(formation_id) ON DELETE SET NULL,
    FOREIGN KEY (parcours_cible_id) REFERENCES Parcours(parcours_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 8. Utilisateurs
-- ============================================================

CREATE TABLE Users(
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Codes de decision de base
INSERT INTO CodeDecision(code, type_decision, signification, categorie_flux, est_reussite, est_sortie, est_redoublement) VALUES
('ADM', 'ANNEE', 'Admis', 'VALIDATION', TRUE, FALSE, FALSE),
('ADJ', 'ANNEE', 'Admis par decision de jury', 'VALIDATION_JURY', TRUE, FALSE, FALSE),
('ADSUP', 'ANNEE', 'Admis par supplement', 'VALIDATION', TRUE, FALSE, FALSE),
('CMP', 'ANNEE', 'Compense', 'VALIDATION', TRUE, FALSE, FALSE),
('PASD', 'ANNEE', 'Passage sans decision ou non validation complete', 'NON_VALIDATION', FALSE, FALSE, FALSE),
('RED', 'ANNEE', 'Redoublement', 'REDOUBLEMENT', FALSE, FALSE, TRUE),
('NAR', 'ANNEE', 'Non autorise a se reinscrire / sortie', 'SORTIE', FALSE, TRUE, FALSE),
('DEM', 'ANNEE', 'Demission', 'SORTIE', FALSE, TRUE, FALSE),
('DEF', 'ANNEE', 'Defaillant', 'SORTIE', FALSE, TRUE, FALSE),
('AJ', 'ANNEE', 'Ajourne', 'NON_VALIDATION', FALSE, FALSE, FALSE)
ON DUPLICATE KEY UPDATE signification = VALUES(signification);