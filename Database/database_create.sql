DROP TABLE IF EXISTS EffectuerAnnee;
DROP TABLE IF EXISTS EffectuerUE;
DROP TABLE IF EXISTS EffectuerRCUE;
DROP TABLE IF EXISTS ContribuerUE;
DROP TABLE IF EXISTS CodeRCUE;
DROP TABLE IF EXISTS CodeUE;
DROP TABLE IF EXISTS CodeAnnee;
DROP TABLE IF EXISTS Etudiant;
DROP TABLE IF EXISTS UE;
DROP TABLE IF EXISTS RCUE;
DROP TABLE IF EXISTS FormSemestre;
DROP TABLE IF EXISTS AnneeFormation;
DROP TABLE IF EXISTS Parcours;
DROP TABLE IF EXISTS Formation;
DROP TABLE IF EXISTS Departement;
DROP TABLE IF EXISTS AnneeScolaire;
DROP TABLE IF EXISTS Users;

CREATE TABLE Departement (
    dep_id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    accronyme     VARCHAR(50)  NOT NULL,
    description   VARCHAR(255),
    visible       BOOLEAN,
    date_creation DATE,
    nom_dep       VARCHAR(100) NOT NULL,
    PRIMARY KEY (dep_id)
);

CREATE TABLE Formation (
    formation_id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    accronyme       VARCHAR(50)  NOT NULL,
    titre           VARCHAR(100) NOT NULL,
    version         TINYINT UNSIGNED,
    formation_code  VARCHAR(50)  NOT NULL,
    type_parcours   TINYINT UNSIGNED,
    titre_officiel  VARCHAR(100) NOT NULL,
    commentaire     VARCHAR(255) DEFAULT NULL,
    code_specialite VARCHAR(50)  DEFAULT NULL,
    archived        BOOLEAN      DEFAULT FALSE,
    dep_id          BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (formation_id),
    FOREIGN KEY (dep_id) REFERENCES Departement(dep_id) ON DELETE CASCADE
);

CREATE TABLE Parcours (
    parcours_id  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(50)  NOT NULL,
    libelle      VARCHAR(100) NOT NULL,
    formation_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (parcours_id),
    FOREIGN KEY (formation_id) REFERENCES Formation(formation_id) ON DELETE CASCADE
);

CREATE TABLE AnneeFormation (
    anneeformation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ordre             TINYINT UNSIGNED NOT NULL,
    libelle           VARCHAR(100),
    parcours_id       BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (anneeformation_id),
    FOREIGN KEY (parcours_id) REFERENCES Parcours(parcours_id) ON DELETE CASCADE
);

CREATE TABLE FormSemestre (
    formsemestre_id   INT UNSIGNED NOT NULL,
    titre             VARCHAR(100) NOT NULL,
    semestre_num      TINYINT UNSIGNED,
    date_debut        DATE,
    date_fin          DATE,
    titre_long        VARCHAR(255),
    etape_apo         VARCHAR(50),
    anneeformation_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (formsemestre_id),
    FOREIGN KEY (anneeformation_id) REFERENCES AnneeFormation(anneeformation_id) ON DELETE CASCADE
);

CREATE TABLE RCUE (
    rcue_id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nomCompetence     VARCHAR(100) NOT NULL,
    niveau            TINYINT UNSIGNED,
    anneeformation_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (rcue_id),
    FOREIGN KEY (anneeformation_id) REFERENCES AnneeFormation(anneeformation_id) ON DELETE CASCADE
);

CREATE TABLE UE (
    ue_id           INT UNSIGNED NOT NULL,
    rcue_id         BIGINT UNSIGNED NOT NULL,
    formsemestre_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (ue_id),
    FOREIGN KEY (rcue_id) REFERENCES RCUE(rcue_id) ON DELETE CASCADE,
    FOREIGN KEY (formsemestre_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE CASCADE
);

CREATE TABLE ContribuerUE (
    rcue_id     BIGINT UNSIGNED NOT NULL,
    ue_id       INT UNSIGNED  NOT NULL,
    coefficient DECIMAL(5, 2) NOT NULL DEFAULT 1.00,
    PRIMARY KEY (rcue_id, ue_id),
    FOREIGN KEY (rcue_id) REFERENCES RCUE(rcue_id) ON DELETE CASCADE,
    FOREIGN KEY (ue_id) REFERENCES UE(ue_id) ON DELETE CASCADE
);

CREATE TABLE Etudiant (
    etudiant_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code_nip    VARCHAR(100) NOT NULL,
    etat        VARCHAR(50),
    PRIMARY KEY (etudiant_id)
);

CREATE TABLE AnneeScolaire (
    annee_scolaire VARCHAR(9) NOT NULL,
    PRIMARY KEY (annee_scolaire)
);

CREATE TABLE CodeRCUE (
    codercue_id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code           VARCHAR(50)  NOT NULL,
    signification  VARCHAR(100),
    PRIMARY KEY (codercue_id)
);

CREATE TABLE CodeUE (
    codeue_id     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code          VARCHAR(50)  NOT NULL,
    signification VARCHAR(100),
    PRIMARY KEY (codeue_id)
);

CREATE TABLE CodeAnnee (
    codeannee_id  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code          VARCHAR(50)  NOT NULL,
    signification VARCHAR(100),
    PRIMARY KEY (codeannee_id)
);

CREATE TABLE EffectuerRCUE (
    annee_scolaire VARCHAR(9)   NOT NULL,
    rcue_id        BIGINT UNSIGNED NOT NULL,
    etudiant_id    BIGINT UNSIGNED NOT NULL,
    codercue_id    BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (annee_scolaire, rcue_id, etudiant_id, codercue_id),
    FOREIGN KEY (annee_scolaire) REFERENCES AnneeScolaire(annee_scolaire) ON DELETE CASCADE,
    FOREIGN KEY (rcue_id) REFERENCES RCUE(rcue_id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (codercue_id) REFERENCES CodeRCUE(codercue_id) ON DELETE CASCADE
);

CREATE TABLE EffectuerUE (
    annee_scolaire VARCHAR(9)   NOT NULL,
    ue_id          INT UNSIGNED NOT NULL,
    etudiant_id    BIGINT UNSIGNED NOT NULL,
    codeue_id      BIGINT UNSIGNED NOT NULL,
    moyenne        DECIMAL(4, 2),
    PRIMARY KEY (annee_scolaire, ue_id, etudiant_id, codeue_id),
    FOREIGN KEY (annee_scolaire) REFERENCES AnneeScolaire(annee_scolaire) ON DELETE CASCADE,
    FOREIGN KEY (ue_id) REFERENCES UE(ue_id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (codeue_id) REFERENCES CodeUE(codeue_id) ON DELETE CASCADE
);

CREATE TABLE EffectuerAnnee (
    annee_scolaire    VARCHAR(9)   NOT NULL,
    anneeformation_id BIGINT UNSIGNED NOT NULL,
    etudiant_id       BIGINT UNSIGNED NOT NULL,
    codeannee_id      BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (annee_scolaire, anneeformation_id, etudiant_id, codeannee_id),
    FOREIGN KEY (annee_scolaire) REFERENCES AnneeScolaire(annee_scolaire) ON DELETE CASCADE,
    FOREIGN KEY (anneeformation_id) REFERENCES AnneeFormation(anneeformation_id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (codeannee_id) REFERENCES CodeAnnee(codeannee_id) ON DELETE CASCADE
);

CREATE TABLE Users (
    user_id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(50)  NOT NULL,
    PRIMARY KEY (user_id)
);

-- Admin par défaut test
INSERT IGNORE INTO Users (username, password_hash, role)
    VALUES ('testadmin', '$2y$10$wO0usZ4ju4ivozFYG3DWq.xF7N4oo9Zpy2G9k6dXaxOVxbgHjR5F.', 'admin');
