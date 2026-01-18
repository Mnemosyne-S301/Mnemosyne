DROP TABLE IF EXISTS EffectuerAnnee;
DROP TABLE IF EXISTS EffectuerUE;
DROP TABLE IF EXISTS EffectuerRCUE;
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

CREATE TABLE Departement(
    dep_id INTEGER PRIMARY KEY,
    accronyme VARCHAR(50) NOT NULL,
    description VARCHAR(50),
    visible BOOLEAN,
    date_creation DATETIME,
    nom_dep VARCHAR(50) NOT NULL
);

CREATE TABLE Formation(
    formation_id SERIAL PRIMARY KEY,
    accronyme VARCHAR(50) NOT NULL,
    titre VARCHAR(100) NOT NULL,
    version TINYINT UNSIGNED,
    formation_code VARCHAR(50) NOT NULL,
    type_parcours SMALLINT UNSIGNED,
    titre_officiel VARCHAR(150) NOT NULL,
    commentaire VARCHAR(100) DEFAULT NULL,
    code_specialite VARCHAR(50) DEFAULT NULL,
    dep_id INTEGER,
    FOREIGN KEY (dep_id) REFERENCES Departement(dep_id) ON DELETE CASCADE
);

CREATE TABLE Parcours(
    parcours_id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    formation_id BIGINT UNSIGNED,
    FOREIGN KEY (formation_id) REFERENCES Formation(formation_id) ON DELETE CASCADE
);

/*  Oui, pour referencer une PK de type SERIAL il faut avoir une FK du type
    BIGINT UNSIGNED. Car SERIAL est un alias vers ce type sous MySQL (selon documentation et StackOverflow)
    Donc c'est pas très opti de mettre du SERIAL partout, faudra changer ça plus tard.
    source : https://stackoverflow.com/questions/14148880/how-do-i-reference-a-foreign-key-to-serial-datatype
    Cordialement, JML Mathéo
*/

CREATE TABLE AnneeFormation(
    anneeformation_id SERIAL PRIMARY KEY,
    ordre TINYINT UNSIGNED,
    parcours_id BIGINT UNSIGNED,
    FOREIGN KEY (parcours_id) REFERENCES Parcours(parcours_id) ON DELETE CASCADE
);

CREATE TABLE FormSemestre(
    formsemestre_id INTEGER PRIMARY KEY,
    titre VARCHAR(100) NOT NULL,
    semestre_num TINYINT UNSIGNED,
    date_debut DATE,
    date_fin DATE,
    titre_long VARCHAR(100),
    etape_apo VARCHAR(50),
    anneeformation_id BIGINT UNSIGNED,
    FOREIGN KEY (anneeformation_id) REFERENCES AnneeFormation(anneeformation_id) ON DELETE CASCADE
);

CREATE TABLE RCUE(
    rcue_id SERIAL PRIMARY KEY,
    nomCompetence VARCHAR(50) NOT NULL,
    niveau INTEGER, -- je sais pas ce que c'est. J'ai pas retrouvé à quoi correspond le "niveau d'une compétence"
    anneeformation_id BIGINT UNSIGNED,
    FOREIGN KEY (anneeformation_id) REFERENCES AnneeFormation(anneeformation_id) ON DELETE CASCADE
);

CREATE TABLE UE(
    ue_id INTEGER PRIMARY KEY,
    rcue_id BIGINT UNSIGNED,
    formsemestre_id INTEGER,
    FOREIGN KEY (rcue_id) REFERENCES RCUE(rcue_id) ON DELETE CASCADE,
    FOREIGN KEY (formsemestre_id) REFERENCES FormSemestre(formsemestre_id) ON DELETE CASCADE
);

CREATE TABLE Etudiant(
    etudiant_id SERIAL PRIMARY KEY,
    code_nip VARCHAR(100) NOT NULL,
    etat VARCHAR(50) -- pareil, je sais pas ce que c'est
);

CREATE TABLE CodeRCUE(
    codercue_id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    signification VARCHAR(50)
);

CREATE TABLE CodeUE(
    codeue_id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    signification VARCHAR(50)
);

CREATE TABLE CodeAnnee(
    codeannee_id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    signification VARCHAR(50)
);

CREATE TABLE EffectuerRCUE(
    annee_scolaire INTEGER UNSIGNED,
    rcue_id BIGINT UNSIGNED,
    etudiant_id BIGINT UNSIGNED,
    codercue_id BIGINT UNSIGNED,
    PRIMARY KEY (annee_scolaire, rcue_id, etudiant_id, codercue_id),
    FOREIGN KEY (rcue_id) REFERENCES RCUE(rcue_id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (codercue_id) REFERENCES CodeRCUE(codercue_id) ON DELETE CASCADE
);

CREATE TABLE EffectuerUE(
    annee_scolaire INTEGER UNSIGNED,
    ue_id INTEGER,
    etudiant_id BIGINT UNSIGNED,
    codeue_id BIGINT UNSIGNED,
    PRIMARY KEY (annee_scolaire, ue_id, etudiant_id, codeue_id),
    FOREIGN KEY (ue_id) REFERENCES UE(ue_id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (codeue_id) REFERENCES CodeUE(codeue_id) ON DELETE CASCADE
);

CREATE TABLE EffectuerAnnee(
    annee_scolaire INTEGER UNSIGNED,
    anneeformation_id BIGINT UNSIGNED,
    etudiant_id BIGINT UNSIGNED,
    codeannee_id BIGINT UNSIGNED,
    PRIMARY KEY (annee_scolaire, anneeformation_id, etudiant_id, codeannee_id),
    FOREIGN KEY (anneeformation_id) REFERENCES AnneeFormation(anneeformation_id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES Etudiant(etudiant_id) ON DELETE CASCADE,
    FOREIGN KEY (codeannee_id) REFERENCES CodeAnnee(codeannee_id) ON DELETE CASCADE
);