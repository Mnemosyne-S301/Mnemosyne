-- ==============================================================================
-- SCRIPT DE STATISTIQUES SCOLARITE : UE ET RCUE
-- ==============================================================================

-- 1. NETTOYAGE
-- Suppression des tables existantes pour permettre la régénération des données
DROP TABLE IF EXISTS nb_eleve_par_formation;
DROP TABLE IF EXISTS res_ue_par_annee_par_eleve;
DROP TABLE IF EXISTS nb_ue_valide_par_annee_par_eleve;
DROP TABLE IF EXISTS repartition_notes_par_parcours;
DROP TABLE IF EXISTS nb_rcue_valide_par_annee_par_eleve;
DROP TABLE IF EXISTS repartition_rcue_par_parcours;

-- ==============================================================================
-- PARTIE 1 : STATISTIQUES GENERALES
-- ==============================================================================

-- 2. RECENSEMENT DES ETUDIANTS
-- Compte le nombre d'étudiants inscrits par Département > Formation > Parcours
CREATE TABLE nb_eleve_par_formation AS
SELECT
    departement.description AS dep,
    formation.titre AS formation,
    parcours.libelle AS parcours,
    COUNT(etudiant.etudiant_id) AS nombre_etudiants
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
    parcours.libelle;

-- ==============================================================================
-- PARTIE 2 : ANALYSE DES UE (UNITES D'ENSEIGNEMENT)
-- ==============================================================================

-- 3. CONSOLIDATION DES RESULTATS UE
-- Crée une vue détaillée des résultats de chaque étudiant avec le contexte pédagogique complet
CREATE TABLE res_ue_par_annee_par_eleve AS
SELECT  
    departement.description AS dep,
    formation.titre AS formation,
    parcours.libelle AS parcours,
    effectuerannee.annee_scolaire,
    etudiant.etudiant_id,
    codeue.code AS resultat_ue
FROM scolarite.effectuerannee
INNER JOIN scolarite.etudiant
    ON (effectuerannee.etudiant_id = etudiant.etudiant_id)
-- Jointures structurelles (Parcours/Formation)
INNER JOIN scolarite.anneeformation 
    ON (effectuerannee.anneeformation_id = anneeformation.anneeformation_id)
INNER JOIN scolarite.parcours 
    ON (anneeformation.parcours_id = parcours.parcours_id)
INNER JOIN scolarite.formation 
    ON (parcours.formation_id = formation.formation_id)
INNER JOIN scolarite.departement 
    ON (formation.dep_id = departement.dep_id)
-- Jointures fonctionnelles (Résultats)
INNER JOIN scolarite.effectuerue
    ON (etudiant.etudiant_id = effectuerue.etudiant_id 
    AND effectuerannee.annee_scolaire = effectuerue.annee_scolaire) 
INNER JOIN scolarite.codeue
    ON (effectuerue.codeue_id = codeue.codeue_id);

-- 4. COMPTAGE DES UE VALIDEES PAR ETUDIANT
-- Compte combien d'UE ont été validées (ADM, ADSUP, PASD) par étudiant et par an
CREATE TABLE nb_ue_valide_par_annee_par_eleve AS
SELECT 
    t1.dep,
    t1.formation,
    t1.parcours,
    t1.annee_scolaire,
    t1.etudiant_id,
    COUNT(t1.resultat_ue) AS nb_ue_validees
FROM res_ue_par_annee_par_eleve AS t1
WHERE t1.resultat_ue IN ('ADM','ADSUP','PASD') -- Filtre sur les codes de réussite
GROUP BY
    t1.dep,
    t1.formation,
    t1.parcours,
    t1.annee_scolaire,
    t1.etudiant_id;

-- 5. REPARTITION DES RESULTATS UE
-- Regroupe les étudiants par nombre d'UE validées (Distribution de fréquence)
CREATE TABLE repartition_notes_par_parcours AS
SELECT 
    dep,
    formation,
    parcours,
    annee_scolaire,
    nb_ue_validees,                 -- Score atteint (ex: 6 UE)
    COUNT(etudiant_id) AS nb_eleves -- Nombre d'élèves ayant atteint ce score
FROM nb_ue_valide_par_annee_par_eleve
GROUP BY 
    dep,
    formation,
    parcours,
    annee_scolaire,
    nb_ue_validees
ORDER BY 
    dep, 
    formation, 
    parcours, 
    annee_scolaire,
    nb_ue_validees DESC;

-- ==============================================================================
-- PARTIE 3 : ANALYSE DES RCUE (COMPETENCES)
-- ==============================================================================

-- 6. COMPTAGE DES RCUE VALIDES PAR ETUDIANT
-- Compte combien de blocs de compétences (RCUE) ont été validés par étudiant
CREATE TABLE nb_rcue_valide_par_annee_par_eleve AS
SELECT 
    departement.description AS dep,
    formation.titre AS formation,
    parcours.libelle AS parcours,
    effectuerrcue.annee_scolaire,
    etudiant.etudiant_id,
    COUNT(codercue.code) AS nb_rcue_valides
FROM scolarite.effectuerrcue
INNER JOIN scolarite.etudiant
    ON (effectuerrcue.etudiant_id = etudiant.etudiant_id)
-- Jointure Résultats
INNER JOIN scolarite.codercue
    ON (effectuerrcue.codercue_id = codercue.codercue_id)
-- Remontée structurelle via RCUE
INNER JOIN scolarite.rcue
    ON (effectuerrcue.rcue_id = rcue.rcue_id)
INNER JOIN scolarite.anneeformation
    ON (rcue.anneeformation_id = anneeformation.anneeformation_id)
INNER JOIN scolarite.parcours
    ON (anneeformation.parcours_id = parcours.parcours_id)
INNER JOIN scolarite.formation
    ON (parcours.formation_id = formation.formation_id)
INNER JOIN scolarite.departement
    ON (formation.dep_id = departement.dep_id)
WHERE codercue.code IN ('ADM', 'ADSUP', 'PASD')
GROUP BY
    departement.description,
    formation.titre,
    parcours.libelle,
    effectuerrcue.annee_scolaire,
    etudiant.etudiant_id;

-- 7. REPARTITION DES RESULTATS RCUE
-- Regroupe les étudiants par nombre de RCUE validés (Distribution de fréquence)
CREATE TABLE repartition_rcue_par_parcours AS
SELECT 
    dep,
    formation,
    parcours,
    annee_scolaire,
    nb_rcue_valides,                 -- Score atteint (ex: 4 RCUE)
    COUNT(etudiant_id) AS nb_eleves  -- Nombre d'élèves ayant atteint ce score
FROM nb_rcue_valide_par_annee_par_eleve
GROUP BY 
    dep,
    formation,
    parcours,
    annee_scolaire,
    nb_rcue_valides
ORDER BY 
    dep, 
    formation, 
    parcours, 
    annee_scolaire,
    nb_rcue_valides DESC;