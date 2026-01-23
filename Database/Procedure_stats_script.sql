-- ==============================================================================
-- FICHIER : 02_procedures_stats.sql
-- CRÉATION DES PROCÉDURES STOCKÉES D'ACTUALISATION DES STATISTIQUES
-- ==============================================================================

DELIMITER //

-- Suppression des anciennes procédures (pour les mettre à jour facilement)
DROP PROCEDURE IF EXISTS actualise_nb_eleve_par_formation
DROP PROCEDURE IF EXISTS actualise_nb_ue_par_formation_par_semestre
DROP PROCEDURE IF EXISTS actualise_res_ue_par_annee_par_eleve
DROP PROCEDURE IF EXISTS actualise_nb_ue_valide_par_annee_par_eleve
DROP PROCEDURE IF EXISTS actualise_repartition_notes_par_parcours
DROP PROCEDURE IF EXISTS actualise_nb_rcue_valide_par_annee_par_eleve
DROP PROCEDURE IF EXISTS actualise_repartition_rcue_par_parcours 


-- ==============================================================================
-- PARTIE 1 : STATISTIQUES GENERALES
-- ==============================================================================

CREATE PROCEDURE actualise_nb_eleve_par_formation()
BEGIN  
    -- Nettoyer la table avant de la remplir
    TRUNCATE TABLE nb_eleve_par_formation; 

    INSERT INTO nb_eleve_par_formation (
        dep,
        formation, 
        parcours, 
        annee_scolaire, 
        nombre_etudiants
    )
    
    SELECT
        departement.description AS dep,
        formation.titre AS formation,
        parcours.libelle AS parcours,
        effectuerannee.annee_scolaire, -- AJOUT DE L'ANNÉE
        COUNT(DISTINCT etudiant.etudiant_id) AS nombre_etudiants
    FROM scolarite.etudiant
    INNER JOIN scolarite.effectuerannee 
        ON (etudiant.etudiant_id = effectuerannee.etudiant_id)
    INNER JOIN scolarite.anneeformation 
        ON (effectuerannee.anneeformation_id = anneeformation.anneeformation_id)
    INNER JOIN scolarite.parcours 
        ON (anneeformation.parcours_id = parcours.parcours_id)
    INNER JOIN scolarite.formation 
        ON (parcours.formation_id = formation.formation_id)
    INNER JOIN scolarite.departement 
        ON (formation.dep_id = departement.dep_id)
    GROUP BY 
        departement.description, 
        formation.titre, 
        parcours.libelle,
        effectuerannee.annee_scolaire; -- GROUPE PAR ANNEE
END//

CREATE PROCEDURE actualise_nb_ue_par_formation_par_semestre()
BEGIN
    -- On vide la table avant de la recalculer
    TRUNCATE TABLE nb_ue_par_formation_semestre;

    -- Insertion des nouvelles statistiques
    INSERT INTO nb_ue_par_formation_semestre(
        dep, 
        formation, 
        semestre, 
        parcours, 
        annee_scolaire, 
        nb_ue
    )
    SELECT
        departement.description AS dep,
        formation.titre AS formation,
        FormSemestre.formsemestre_num AS semestre,
        parcours.libelle AS parcours,
        EffectuerAnnee.annee_scolaire,
        COUNT(DISTINCT UE.ue_id) AS nb_ue
    FROM scolarite.departement
    INNER JOIN scolarite.formation
        ON departement.dep_id = formation.dep_id
    INNER JOIN scolarite.parcours
        ON formation.formation_id = parcours.formation_id
    INNER JOIN scolarite.anneeformation
        ON parcours.parcours_id = anneeformation.parcours_id
    INNER JOIN scolarite.EffectuerAnnee
        ON anneeformation.anneeformation_id = EffectuerAnnee.anneeformation_id
    INNER JOIN scolarite.FormSemestre
        ON anneeformation.anneeformation_id = FormSemestre.anneeformation_id
    INNER JOIN scolarite.UE
        ON FormSemestre.formsemestre_id = UE.formsemestre_id
    GROUP BY 
        departement.description,
        formation.titre,
        FormSemestre.formsemestre_num,
        parcours.libelle,
        EffectuerAnnee.annee_scolaire;
END//
/*
-- ==============================================================================
-- PARTIE 2 : ANALYSE DES UE
-- ==============================================================================

CREATE PROCEDURE actualise_res_ue_par_annee_par_eleve()
BEGIN
    TRUNCATE TABLE res_ue_par_annee_par_eleve;
    
    INSERT INTO res_ue_par_annee_par_eleve (dep, formation, parcours, annee_scolaire, etudiant_id, resultat_ue)
    SELECT  
        departement.description AS dep,
        formation.titre AS formation,
        parcours.libelle AS parcours,
        effectuerannee.annee_scolaire,
        etudiant.etudiant_id,
        codeue.code AS resultat_ue
    FROM scolarite.effectuerannee
    INNER JOIN scolarite.etudiant ON (effectuerannee.etudiant_id = etudiant.etudiant_id)
    INNER JOIN scolarite.anneeformation ON (effectuerannee.anneeformation_id = anneeformation.anneeformation_id)
    INNER JOIN scolarite.parcours ON (anneeformation.parcours_id = parcours.parcours_id)
    INNER JOIN scolarite.formation ON (parcours.formation_id = formation.formation_id)
    INNER JOIN scolarite.departement ON (formation.dep_id = departement.dep_id)
    INNER JOIN scolarite.effectuerue ON (etudiant.etudiant_id = effectuerue.etudiant_id 
        AND effectuerannee.annee_scolaire = effectuerue.annee_scolaire) 
    INNER JOIN scolarite.codeue ON (effectuerue.codeue_id = codeue.codeue_id);
END//


CREATE PROCEDURE actualise_nb_ue_valide_par_annee_par_eleve()
BEGIN
    TRUNCATE TABLE nb_ue_valide_par_annee_par_eleve;
    
    INSERT INTO nb_ue_valide_par_annee_par_eleve (dep, formation, parcours, annee_scolaire, etudiant_id, nb_ue_validees)
    SELECT 
        t1.dep,
        t1.formation,
        t1.parcours,
        t1.annee_scolaire,
        t1.etudiant_id,
        COUNT(t1.resultat_ue) AS nb_ue_validees
    FROM res_ue_par_annee_par_eleve AS t1
    WHERE t1.resultat_ue IN ('ADM','ADSUP','PASD')
    GROUP BY
        t1.dep,
        t1.formation,
        t1.parcours,
        t1.annee_scolaire,
        t1.etudiant_id;
END//


CREATE PROCEDURE actualise_repartition_notes_par_parcours()
BEGIN
    TRUNCATE TABLE repartition_notes_par_parcours;
    
    INSERT INTO repartition_notes_par_parcours (dep, formation, parcours, annee_scolaire, nb_ue_validees, nb_eleves)
    SELECT 
        dep,
        formation,
        parcours,
        annee_scolaire,
        nb_ue_validees,
        COUNT(etudiant_id) AS nb_eleves
    FROM nb_ue_valide_par_annee_par_eleve
    GROUP BY 
        dep,
        formation,
        parcours,
        annee_scolaire,
        nb_ue_validees;
END//

*/
-- ==============================================================================
-- PARTIE 3 : ANALYSE DES RCUE (Compétences - REQUIERT L'EXISTENCE DES TABLES RCUE)
-- ==============================================================================

CREATE PROCEDURE actualise_nb_rcue_valide_par_annee_par_eleve()
BEGIN
    TRUNCATE TABLE nb_rcue_valide_par_annee_par_eleve;
    
    INSERT INTO nb_rcue_valide_par_annee_par_eleve (dep, formation, parcours, annee_scolaire, etudiant_id, nb_rcue_valides)
    SELECT 
        departement.description AS dep,
        formation.titre AS formation,
        parcours.libelle AS parcours,
        effectuerrcue.annee_scolaire,
        etudiant.etudiant_id,
        COUNT(codercue.code) AS nb_rcue_valides
    FROM scolarite.effectuerrcue
    INNER JOIN scolarite.etudiant ON (effectuerrcue.etudiant_id = etudiant.etudiant_id)
    INNER JOIN scolarite.codercue ON (effectuerrcue.codercue_id = codercue.codercue_id)
    INNER JOIN scolarite.rcue ON (effectuerrcue.rcue_id = rcue.rcue_id)
    INNER JOIN scolarite.anneeformation ON (rcue.anneeformation_id = anneeformation.anneeformation_id)
    INNER JOIN scolarite.parcours ON (anneeformation.parcours_id = parcours.parcours_id)
    INNER JOIN scolarite.formation ON (parcours.formation_id = formation.formation_id)
    INNER JOIN scolarite.departement ON (formation.dep_id = departement.dep_id)
    WHERE codercue.code IN ('ADM', 'ADSUP', 'PASD')
    GROUP BY
        departement.description,
        formation.titre,
        parcours.libelle,
        effectuerrcue.annee_scolaire,
        etudiant.etudiant_id;
END//


CREATE PROCEDURE actualise_repartition_rcue_par_parcours()
BEGIN
    TRUNCATE TABLE repartition_rcue_par_parcours;
    
    INSERT INTO repartition_rcue_par_parcours (dep, formation, parcours, annee_scolaire, nb_rcue_valides, nb_eleves)
    SELECT 
        dep,
        formation,
        parcours,
        annee_scolaire,
        nb_rcue_valides,
        COUNT(etudiant_id) AS nb_eleves
    FROM nb_rcue_valide_par_annee_par_eleve
    GROUP BY 
        dep,
        formation,
        parcours,
        annee_scolaire,
        nb_rcue_valides;
END//

DELIMITER ;