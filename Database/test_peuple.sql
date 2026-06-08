<?php
/**
 * Mnemosyne - Script de peuplement depuis les JSON ScoDoc
 * -------------------------------------------------------
 * Usage CLI : php mnemosyne_populate.php
 *
 * A adapter : variables DB_* et JSON_ROOT ci-dessous.
 * Le script parcourt récursivement les dossiers JSON, détecte le type
 * de fichier puis insère les données dans la base.
 */

// ========================
// CONFIGURATION
// ========================
const DB_HOST = 'localhost';
const DB_NAME = 'test_SAE';
const DB_USER = 'mnemosyne_user';
const DB_PASS = 'xxxxxxxx@@';
const DB_CHARSET = 'utf8mb4';


const JSON_ROOT = __DIR__ . '/example/json';

// ========================
// BOOTSTRAP
// ========================

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

main($pdo, JSON_ROOT);

function main(PDO $pdo, string $jsonRoot): void
{
    if (!is_dir($jsonRoot)) {
        throw new RuntimeException("Dossier JSON introuvable : $jsonRoot");
    }

    $files = listJsonFiles($jsonRoot);
    echo "Fichiers JSON trouves : " . count($files) . PHP_EOL;

    // Passage 1 : référentiels stables
    foreach ($files as $file) {
        $data = readJson($file);
        $type = detectJsonType($data);

        try {
            if ($type === 'departements') {
                syncDepartements($pdo, $data);
                echo "[OK] Departements : $file" . PHP_EOL;
            } elseif ($type === 'referentiel') {
                syncReferentiel($pdo, $data);
                echo "[OK] Referentiel : $file" . PHP_EOL;
            }
        } catch (Throwable $e) {
            echo "[ERREUR] $file : " . $e->getMessage() . PHP_EOL;
        }
    }

    // Passage 2 : formations, parcours, formsemestres
    foreach ($files as $file) {
        $data = readJson($file);
        $type = detectJsonType($data);

        try {
            if ($type === 'formsemestres') {
                syncFormSemestres($pdo, $data);
                echo "[OK] FormSemestres : $file" . PHP_EOL;
            }
        } catch (Throwable $e) {
            echo "[ERREUR] $file : " . $e->getMessage() . PHP_EOL;
        }
    }

    // Passage 3 : décisions jury, étudiants, résultats, autorisations, positions
    foreach ($files as $file) {
        $data = readJson($file);
        $type = detectJsonType($data);

        try {
            if ($type === 'decisions_jury') {
                $formsemestreId = extractFormSemestreIdFromFilename($file);
                if ($formsemestreId === null) {
                    echo "[SKIP] Impossible de trouver formsemestre_id dans le nom du fichier : $file" . PHP_EOL;
                    continue;
                }
                syncDecisionsJury($pdo, $data, $formsemestreId);
                echo "[OK] Decisions jury fs=$formsemestreId : $file" . PHP_EOL;
            }
        } catch (Throwable $e) {
            echo "[ERREUR] $file : " . $e->getMessage() . PHP_EOL;
        }
    }

    // Passage 4 : cohortes et étudiants des cohortes
    generateCohortes($pdo);
    generateCohorteEtudiants($pdo);

    echo "Peuplement termine." . PHP_EOL;
}

// ========================
// OUTILS GENERAUX
// ========================

function listJsonFiles(string $root): array
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    $files = [];

    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        if (strtolower($file->getExtension()) === 'json') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);
    return $files;
}

function readJson(string $path): mixed
{
    $content = file_get_contents($path);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('JSON invalide : ' . json_last_error_msg());
    }

    return $data;
}

function detectJsonType(mixed $data): string
{
    if (is_array($data) && array_is_list($data) && isset($data[0]['etudid'], $data[0]['annee'])) {
        return 'decisions_jury';
    }

    if (is_array($data) && array_is_list($data) && isset($data[0]['id'], $data[0]['acronym'], $data[0]['dept_name'])) {
        return 'departements';
    }

    if (is_array($data) && array_is_list($data) && (isset($data[0]['formsemestre_id']) || isset($data[0]['formation']))) {
        return 'formsemestres';
    }

    if (is_array($data) && isset($data['competences'], $data['specialite'])) {
        return 'referentiel';
    }

    return 'unknown';
}

function extractFormSemestreIdFromFilename(string $file): ?int
{
    $base = basename($file);

    $patterns = [
        '/fs[_-]?(\d+)/i',
        '/formsemestre[_-]?(\d+)/i',
        '/(\d{3,6})/'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $base, $m)) {
            return (int)$m[1];
        }
    }

    return null;
}

function sqlDateTime(?string $value): ?string
{
    if (!$value) return null;
    return str_replace('T', ' ', substr($value, 0, 19));
}

function sqlDate(?string $value): ?string
{
    if (!$value) return null;
    if (str_contains($value, '/')) {
        $parts = explode('/', $value);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
    }
    return substr($value, 0, 10);
}

function boolInt(mixed $value): int
{
    return !empty($value) ? 1 : 0;
}

function fetchOne(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// ========================
// SYNC DEPARTEMENTS
// ========================

function syncDepartements(PDO $pdo, array $departements): void
{
    $stmt = $pdo->prepare("\n        INSERT INTO Departement(dep_id, acronyme, description, visible, date_creation, nom_dep)\n        VALUES(:dep_id, :acronyme, :description, :visible, :date_creation, :nom_dep)\n        ON DUPLICATE KEY UPDATE\n            acronyme = VALUES(acronyme),\n            description = VALUES(description),\n            visible = VALUES(visible),\n            date_creation = VALUES(date_creation),\n            nom_dep = VALUES(nom_dep)\n    ");

    foreach ($departements as $dep) {
        $stmt->execute([
            ':dep_id' => $dep['id'],
            ':acronyme' => $dep['acronym'],
            ':description' => $dep['description'] ?? null,
            ':visible' => boolInt($dep['visible'] ?? true),
            ':date_creation' => sqlDateTime($dep['date_creation'] ?? null),
            ':nom_dep' => $dep['dept_name'] ?? $dep['acronym'],
        ]);
    }
}

function upsertDepartementFromObject(PDO $pdo, ?array $dep): void
{
    if (!$dep || !isset($dep['id'])) return;
    syncDepartements($pdo, [$dep]);
}

// ========================
// SYNC REFERENTIEL
// ========================

function syncReferentiel(PDO $pdo, array $ref): void
{
    $deptId = $ref['dept_id'] ?? null;

    $stmtRef = $pdo->prepare("\n        INSERT INTO ReferentielCompetence(\n            dept_id, specialite, specialite_long, type_structure, type_departement,\n            type_titre, version_orebut, scodoc_date_loaded, scodoc_orig_filename\n        )\n        VALUES(\n            :dept_id, :specialite, :specialite_long, :type_structure, :type_departement,\n            :type_titre, :version_orebut, :scodoc_date_loaded, :scodoc_orig_filename\n        )\n        ON DUPLICATE KEY UPDATE\n            specialite_long = VALUES(specialite_long),\n            type_structure = VALUES(type_structure),\n            type_departement = VALUES(type_departement),\n            type_titre = VALUES(type_titre),\n            version_orebut = VALUES(version_orebut),\n            scodoc_date_loaded = VALUES(scodoc_date_loaded),\n            scodoc_orig_filename = VALUES(scodoc_orig_filename)\n    ");

    $stmtRef->execute([
        ':dept_id' => $deptId,
        ':specialite' => $ref['specialite'] ?? null,
        ':specialite_long' => $ref['specialite_long'] ?? null,
        ':type_structure' => $ref['type_structure'] ?? null,
        ':type_departement' => $ref['type_departement'] ?? null,
        ':type_titre' => $ref['type_titre'] ?? null,
        ':version_orebut' => sqlDateTime($ref['version_orebut'] ?? null),
        ':scodoc_date_loaded' => sqlDateTime($ref['scodoc_date_loaded'] ?? null),
        ':scodoc_orig_filename' => $ref['scodoc_orig_filename'] ?? null,
    ]);

    $referentielId = (int)fetchOne($pdo,
        "SELECT referentiel_id FROM ReferentielCompetence WHERE dept_id <=> :dept_id AND specialite <=> :specialite",
        [':dept_id' => $deptId, ':specialite' => $ref['specialite'] ?? null]
    );

    foreach (($ref['competences'] ?? []) as $titreCle => $competence) {
        syncCompetence($pdo, $referentielId, $competence);
    }
}

function syncCompetence(PDO $pdo, int $referentielId, array $competence): void
{
    $stmt = $pdo->prepare("\n        INSERT INTO Competence(id_orebut, titre, titre_long, couleur, numero, referentiel_id)\n        VALUES(:id_orebut, :titre, :titre_long, :couleur, :numero, :referentiel_id)\n        ON DUPLICATE KEY UPDATE\n            titre = VALUES(titre),\n            titre_long = VALUES(titre_long),\n            couleur = VALUES(couleur),\n            numero = VALUES(numero)\n    ");

    $stmt->execute([
        ':id_orebut' => $competence['id_orebut'] ?? null,
        ':titre' => $competence['titre'] ?? 'Sans titre',
        ':titre_long' => $competence['titre_long'] ?? null,
        ':couleur' => $competence['couleur'] ?? null,
        ':numero' => $competence['numero'] ?? null,
        ':referentiel_id' => $referentielId,
    ]);

    $competenceId = (int)fetchOne($pdo,
        "SELECT competence_id FROM Competence WHERE referentiel_id = :ref AND id_orebut <=> :orebut",
        [':ref' => $referentielId, ':orebut' => $competence['id_orebut'] ?? null]
    );

    foreach (($competence['niveaux'] ?? []) as $niveau) {
        syncNiveauCompetence($pdo, $competenceId, $niveau);
    }
}

function syncNiveauCompetence(PDO $pdo, int $competenceId, array $niveau): void
{
    $stmt = $pdo->prepare("\n        INSERT INTO NiveauCompetence(competence_id, anneeformation_id, niveau, libelle, ordre)\n        VALUES(:competence_id, NULL, :niveau, :libelle, :ordre)\n        ON DUPLICATE KEY UPDATE\n            libelle = VALUES(libelle)\n    ");

    $stmt->execute([
        ':competence_id' => $competenceId,
        ':niveau' => $niveau['ordre'] ?? null,
        ':libelle' => $niveau['libelle'] ?? null,
        ':ordre' => $niveau['ordre'] ?? null,
    ]);

    $niveauId = (int)fetchOne($pdo,
        "SELECT niveaucompetence_id FROM NiveauCompetence WHERE competence_id = :cid AND ordre <=> :ordre LIMIT 1",
        [':cid' => $competenceId, ':ordre' => $niveau['ordre'] ?? null]
    );

    foreach (($niveau['app_critiques'] ?? []) as $code => $ac) {
        $stmtAc = $pdo->prepare("\n            INSERT INTO ApprentissageCritique(code, libelle, oid, niveaucompetence_id)\n            VALUES(:code, :libelle, :oid, :niveaucompetence_id)\n            ON DUPLICATE KEY UPDATE\n                libelle = VALUES(libelle),\n                oid = VALUES(oid)\n        ");
        $stmtAc->execute([
            ':code' => $code,
            ':libelle' => $ac['libelle'] ?? null,
            ':oid' => $ac['oid'] ?? null,
            ':niveaucompetence_id' => $niveauId,
        ]);
    }
}

// ========================
// SYNC FORMSEMESTRES
// ========================

function syncFormSemestres(PDO $pdo, array $formsemestres): void
{
    foreach ($formsemestres as $fs) {
        if (!isset($fs['formation'])) continue;

        upsertDepartementFromObject($pdo, $fs['departement'] ?? ($fs['formation']['departement'] ?? null));
        syncFormation($pdo, $fs['formation']);
        syncFormSemestre($pdo, $fs);

        foreach (($fs['parcours'] ?? []) as $parcours) {
            syncParcoursDepuisFormSemestre($pdo, $parcours, (int)$fs['formation']['id'], (int)($fs['formsemestre_id'] ?? $fs['id']));
        }
    }
}

function syncFormation(PDO $pdo, array $formation): void
{
    upsertDepartementFromObject($pdo, $formation['departement'] ?? null);

    $stmt = $pdo->prepare("\n        INSERT INTO Formation(\n            formation_id, acronyme, titre, titre_officiel, formation_code, version,\n            type_parcours, commentaire, code_specialite, archived, referentiel_competence_id, dep_id\n        )\n        VALUES(\n            :formation_id, :acronyme, :titre, :titre_officiel, :formation_code, :version,\n            :type_parcours, :commentaire, :code_specialite, :archived, :referentiel_competence_id, :dep_id\n        )\n        ON DUPLICATE KEY UPDATE\n            acronyme = VALUES(acronyme),\n            titre = VALUES(titre),\n            titre_officiel = VALUES(titre_officiel),\n            formation_code = VALUES(formation_code),\n            version = VALUES(version),\n            type_parcours = VALUES(type_parcours),\n            commentaire = VALUES(commentaire),\n            code_specialite = VALUES(code_specialite),\n            archived = VALUES(archived),\n            referentiel_competence_id = VALUES(referentiel_competence_id),\n            dep_id = VALUES(dep_id)\n    ");

    $stmt->execute([
        ':formation_id' => $formation['id'] ?? $formation['formation_id'],
        ':acronyme' => $formation['acronyme'] ?? '',
        ':titre' => $formation['titre'] ?? '',
        ':titre_officiel' => $formation['titre_officiel'] ?? null,
        ':formation_code' => $formation['formation_code'] ?? '',
        ':version' => $formation['version'] ?? null,
        ':type_parcours' => $formation['type_parcours'] ?? null,
        ':commentaire' => $formation['commentaire'] ?? null,
        ':code_specialite' => $formation['code_specialite'] ?? null,
        ':archived' => boolInt($formation['archived'] ?? false),
        ':referentiel_competence_id' => $formation['referentiel_competence_id'] ?? null,
        ':dep_id' => $formation['dept_id'],
    ]);
}

function syncFormSemestre(PDO $pdo, array $fs): void
{
    $stmt = $pdo->prepare("\n        INSERT INTO FormSemestre(\n            formsemestre_id, titre, titre_court, titre_num, session_id, modalite, semestre_id,\n            annee_scolaire, date_debut, date_fin, etape_apo, elt_sem_apo, elt_annee_apo,\n            etat, capacite_accueil, formation_id, dep_id\n        )\n        VALUES(\n            :formsemestre_id, :titre, :titre_court, :titre_num, :session_id, :modalite, :semestre_id,\n            :annee_scolaire, :date_debut, :date_fin, :etape_apo, :elt_sem_apo, :elt_annee_apo,\n            :etat, :capacite_accueil, :formation_id, :dep_id\n        )\n        ON DUPLICATE KEY UPDATE\n            titre = VALUES(titre), titre_court = VALUES(titre_court), titre_num = VALUES(titre_num),\n            session_id = VALUES(session_id), modalite = VALUES(modalite), semestre_id = VALUES(semestre_id),\n            annee_scolaire = VALUES(annee_scolaire), date_debut = VALUES(date_debut), date_fin = VALUES(date_fin),\n            etape_apo = VALUES(etape_apo), elt_sem_apo = VALUES(elt_sem_apo), elt_annee_apo = VALUES(elt_annee_apo),\n            etat = VALUES(etat), capacite_accueil = VALUES(capacite_accueil),\n            formation_id = VALUES(formation_id), dep_id = VALUES(dep_id)\n    ");

    $stmt->execute([
        ':formsemestre_id' => $fs['formsemestre_id'] ?? $fs['id'],
        ':titre' => $fs['titre'] ?? '',
        ':titre_court' => $fs['titre_court'] ?? null,
        ':titre_num' => $fs['titre_num'] ?? null,
        ':session_id' => $fs['session_id'] ?? null,
        ':modalite' => $fs['modalite'] ?? null,
        ':semestre_id' => $fs['semestre_id'] ?? null,
        ':annee_scolaire' => $fs['annee_scolaire'] ?? null,
        ':date_debut' => sqlDate($fs['date_debut_iso'] ?? ($fs['date_debut'] ?? null)),
        ':date_fin' => sqlDate($fs['date_fin_iso'] ?? ($fs['date_fin'] ?? null)),
        ':etape_apo' => $fs['etape_apo'] ?? null,
        ':elt_sem_apo' => $fs['elt_sem_apo'] ?? null,
        ':elt_annee_apo' => $fs['elt_annee_apo'] ?? null,
        ':etat' => boolInt($fs['etat'] ?? false),
        ':capacite_accueil' => $fs['capacite_accueil'] ?? null,
        ':formation_id' => $fs['formation_id'],
        ':dep_id' => $fs['dept_id'],
    ]);
}

function syncParcoursDepuisFormSemestre(PDO $pdo, array $parcours, int $formationId, int $formsemestreId): void
{
    $stmt = $pdo->prepare("\n        INSERT INTO Parcours(code, numero, libelle, formation_id)\n        VALUES(:code, :numero, :libelle, :formation_id)\n        ON DUPLICATE KEY UPDATE\n            numero = VALUES(numero),\n            libelle = VALUES(libelle)\n    ");

    $stmt->execute([
        ':code' => $parcours['code'],
        ':numero' => $parcours['numero'] ?? null,
        ':libelle' => $parcours['libelle'] ?? $parcours['code'],
        ':formation_id' => $formationId,
    ]);

    $parcoursId = (int)fetchOne($pdo,
        "SELECT parcours_id FROM Parcours WHERE formation_id = :formation_id AND code = :code",
        [':formation_id' => $formationId, ':code' => $parcours['code']]
    );

    $stmtLien = $pdo->prepare("\n        INSERT IGNORE INTO FormSemestreParcours(formsemestre_id, parcours_id)\n        VALUES(:formsemestre_id, :parcours_id)\n    ");
    $stmtLien->execute([':formsemestre_id' => $formsemestreId, ':parcours_id' => $parcoursId]);

    foreach (($parcours['annees'] ?? []) as $annee) {
        $ordre = $annee['ordre'] ?? null;
        if (!$ordre) continue;

        $stmtAnnee = $pdo->prepare("\n            INSERT INTO AnneeFormation(ordre, libelle, parcours_id)\n            VALUES(:ordre, :libelle, :parcours_id)\n            ON DUPLICATE KEY UPDATE libelle = VALUES(libelle)\n        ");
        $stmtAnnee->execute([
            ':ordre' => $ordre,
            ':libelle' => 'BUT' . $ordre,
            ':parcours_id' => $parcoursId,
        ]);
    }
}

// ========================
// DECISIONS JURY
// ========================

function syncDecisionsJury(PDO $pdo, array $etudiants, int $formsemestreId): void
{
    $fs = getFormSemestre($pdo, $formsemestreId);
    if (!$fs) {
        echo "[WARN] FormSemestre $formsemestreId absent. Decisions ignorees." . PHP_EOL;
        return;
    }

    foreach ($etudiants as $etu) {
        $etudiantId = syncEtudiant($pdo, $etu);

        syncInscriptionSemestre($pdo, $etudiantId, $formsemestreId, $fs, $etu);
        syncDecisionAnnee($pdo, $etudiantId, $formsemestreId, $fs, $etu);
        syncResultatsUE($pdo, $etu, $etudiantId, $formsemestreId, $fs);
        syncResultatsRCUE($pdo, $etu, $etudiantId, $formsemestreId, $fs);
        syncAutorisations($pdo, $etu, $etudiantId);
    }
}

function getFormSemestre(PDO $pdo, int $formsemestreId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM FormSemestre WHERE formsemestre_id = :id");
    $stmt->execute([':id' => $formsemestreId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function syncEtudiant(PDO $pdo, array $etu): int
{
    $stmt = $pdo->prepare("\n        INSERT INTO Etudiant(scodoc_etudid, code_nip, code_ine, is_apc, etat, nb_competences)\n        VALUES(:scodoc_etudid, :code_nip, :code_ine, :is_apc, :etat, :nb_competences)\n        ON DUPLICATE KEY UPDATE\n            code_nip = VALUES(code_nip),\n            code_ine = VALUES(code_ine),\n            is_apc = VALUES(is_apc),\n            etat = VALUES(etat),\n            nb_competences = VALUES(nb_competences)\n    ");

    $stmt->execute([
        ':scodoc_etudid' => $etu['etudid'],
        ':code_nip' => $etu['code_nip'] ?? null,
        ':code_ine' => $etu['code_ine'] ?? null,
        ':is_apc' => boolInt($etu['is_apc'] ?? true),
        ':etat' => $etu['etat'] ?? null,
        ':nb_competences' => $etu['nb_competences'] ?? null,
    ]);

    return (int)fetchOne($pdo,
        "SELECT etudiant_id FROM Etudiant WHERE scodoc_etudid = :etudid",
        [':etudid' => $etu['etudid']]
    );
}

function syncInscriptionSemestre(PDO $pdo, int $etudiantId, int $formsemestreId, array $fs, array $etu): void
{
    $stmt = $pdo->prepare("\n        INSERT INTO InscriptionSemestre(\n            etudiant_id, formsemestre_id, parcours_id, annee_scolaire, semestre_id, modalite, etat_inscription\n        )\n        VALUES(:etudiant_id, :formsemestre_id, NULL, :annee_scolaire, :semestre_id, :modalite, :etat_inscription)\n        ON DUPLICATE KEY UPDATE\n            annee_scolaire = VALUES(annee_scolaire),\n            semestre_id = VALUES(semestre_id),\n            modalite = VALUES(modalite),\n            etat_inscription = VALUES(etat_inscription)\n    ");

    $stmt->execute([
        ':etudiant_id' => $etudiantId,
        ':formsemestre_id' => $formsemestreId,
        ':annee_scolaire' => $etu['annee']['annee_scolaire'] ?? $fs['annee_scolaire'],
        ':semestre_id' => $fs['semestre_id'],
        ':modalite' => $fs['modalite'],
        ':etat_inscription' => $etu['etat'] ?? null,
    ]);
}

function syncDecisionAnnee(PDO $pdo, int $etudiantId, int $formsemestreId, array $fs, array $etu): void
{
    if (!isset($etu['annee']['code'])) return;

    $code = $etu['annee']['code'];
    $codeDecisionId = getOrCreateCodeDecision($pdo, $code, 'ANNEE');
    $anneeScolaire = $etu['annee']['annee_scolaire'] ?? $fs['annee_scolaire'];
    $ordre = $etu['annee']['ordre'] ?? null;

    $stmt = $pdo->prepare("\n        INSERT INTO DecisionAnnee(etudiant_id, formsemestre_id, annee_scolaire, ordre_annee, codedecision_id)\n        VALUES(:etudiant_id, :formsemestre_id, :annee_scolaire, :ordre_annee, :codedecision_id)\n        ON DUPLICATE KEY UPDATE\n            ordre_annee = VALUES(ordre_annee),\n            codedecision_id = VALUES(codedecision_id)\n    ");
    $stmt->execute([
        ':etudiant_id' => $etudiantId,
        ':formsemestre_id' => $formsemestreId,
        ':annee_scolaire' => $anneeScolaire,
        ':ordre_annee' => $ordre,
        ':codedecision_id' => $codeDecisionId,
    ]);

    syncPositionAnnuelleDepuisDecision($pdo, $etudiantId, $formsemestreId, $fs, $anneeScolaire, $ordre, $codeDecisionId, $code);
}

function getOrCreateCodeDecision(PDO $pdo, string $code, string $type): int
{
    $categorie = getCategorieFlux($code);
    $stmt = $pdo->prepare("\n        INSERT INTO CodeDecision(code, type_decision, signification, categorie_flux, est_reussite, est_sortie, est_redoublement)\n        VALUES(:code, :type_decision, :signification, :categorie_flux, :est_reussite, :est_sortie, :est_redoublement)\n        ON DUPLICATE KEY UPDATE\n            categorie_flux = VALUES(categorie_flux),\n            est_reussite = VALUES(est_reussite),\n            est_sortie = VALUES(est_sortie),\n            est_redoublement = VALUES(est_redoublement)\n    ");

    $stmt->execute([
        ':code' => $code,
        ':type_decision' => $type,
        ':signification' => getSignificationCode($code),
        ':categorie_flux' => $categorie,
        ':est_reussite' => in_array($code, ['ADM', 'ADJ', 'ADSUP', 'CMP'], true) ? 1 : 0,
        ':est_sortie' => in_array($code, ['NAR', 'DEM', 'DEF'], true) ? 1 : 0,
        ':est_redoublement' => $code === 'RED' ? 1 : 0,
    ]);

    return (int)fetchOne($pdo,
        "SELECT codedecision_id FROM CodeDecision WHERE code = :code AND type_decision = :type",
        [':code' => $code, ':type' => $type]
    );
}

function getCategorieFlux(string $code): string
{
    return match ($code) {
        'ADM', 'ADJ', 'ADSUP', 'CMP' => 'VALIDATION',
        'RED' => 'REDOUBLEMENT',
        'NAR', 'DEM', 'DEF' => 'SORTIE',
        'PASD', 'AJ' => 'NON_VALIDATION',
        default => 'AUTRE',
    };
}

function getSignificationCode(string $code): ?string
{
    return match ($code) {
        'ADM' => 'Admis',
        'ADJ' => 'Admis par decision de jury',
        'ADSUP' => 'Admis par supplement',
        'CMP' => 'Compense',
        'RED' => 'Redoublement',
        'PASD' => 'Non validation complete / passage sans decision',
        'NAR' => 'Non autorise a se reinscrire / sortie',
        'DEM' => 'Demission',
        'DEF' => 'Defaillant',
        'AJ' => 'Ajourne',
        default => null,
    };
}

function getStatutPosition(string $code): string
{
    return match ($code) {
        'ADM', 'ADJ', 'ADSUP', 'CMP' => 'ADMIS',
        'RED' => 'REDOUBLANT',
        'NAR', 'DEM', 'DEF' => 'SORTIE',
        'PASD', 'AJ' => 'NON_VALIDATION',
        default => 'AUTRE',
    };
}

function syncPositionAnnuelleDepuisDecision(
    PDO $pdo,
    int $etudiantId,
    int $formsemestreId,
    array $fs,
    ?int $anneeScolaire,
    mixed $ordre,
    int $codeDecisionId,
    string $codeDecision
): void {
    if (!$anneeScolaire) return;

    $anneeFormationId = null;
    if ($ordre) {
        $anneeFormationId = fetchOne($pdo, "\n            SELECT af.anneeformation_id\n            FROM AnneeFormation af\n            JOIN Parcours p ON p.parcours_id = af.parcours_id\n            WHERE p.formation_id = :formation_id\n            AND af.ordre = :ordre\n            LIMIT 1\n        ", [':formation_id' => $fs['formation_id'], ':ordre' => $ordre]);
    }

    $stmt = $pdo->prepare("\n        INSERT INTO PositionAnnuelleEtudiant(\n            etudiant_id, annee_scolaire, dep_id, formation_id, parcours_id, anneeformation_id,\n            ordre_annee, modalite, formsemestre_reference_id, code_decision_id, statut_position\n        )\n        VALUES(\n            :etudiant_id, :annee_scolaire, :dep_id, :formation_id, NULL, :anneeformation_id,\n            :ordre_annee, :modalite, :formsemestre_reference_id, :code_decision_id, :statut_position\n        )\n        ON DUPLICATE KEY UPDATE\n            dep_id = VALUES(dep_id),\n            formation_id = VALUES(formation_id),\n            anneeformation_id = VALUES(anneeformation_id),\n            ordre_annee = VALUES(ordre_annee),\n            modalite = VALUES(modalite),\n            code_decision_id = VALUES(code_decision_id),\n            statut_position = VALUES(statut_position)\n    ");

    $stmt->execute([
        ':etudiant_id' => $etudiantId,
        ':annee_scolaire' => $anneeScolaire,
        ':dep_id' => $fs['dep_id'],
        ':formation_id' => $fs['formation_id'],
        ':anneeformation_id' => $anneeFormationId ?: null,
        ':ordre_annee' => $ordre,
        ':modalite' => $fs['modalite'],
        ':formsemestre_reference_id' => $formsemestreId,
        ':code_decision_id' => $codeDecisionId,
        ':statut_position' => getStatutPosition($codeDecision),
    ]);
}

function syncResultatsUE(PDO $pdo, array $etu, int $etudiantId, int $formsemestreId, array $fs): void
{
    foreach (($etu['ues'] ?? []) as $ue) {
        if (!isset($ue['ue_id'], $ue['code'])) continue;

        $codeDecisionId = getOrCreateCodeDecision($pdo, $ue['code'], 'UE');
        upsertUE($pdo, (int)$ue['ue_id']);
        linkUEToFormSemestre($pdo, $formsemestreId, (int)$ue['ue_id']);

        $stmt = $pdo->prepare("\n            INSERT INTO ResultatUE(etudiant_id, ue_id, formsemestre_id, annee_scolaire, codedecision_id, ects, moyenne)\n            VALUES(:etudiant_id, :ue_id, :formsemestre_id, :annee_scolaire, :codedecision_id, :ects, NULL)\n            ON DUPLICATE KEY UPDATE\n                codedecision_id = VALUES(codedecision_id),\n                ects = VALUES(ects)\n        ");

        $stmt->execute([
            ':etudiant_id' => $etudiantId,
            ':ue_id' => $ue['ue_id'],
            ':formsemestre_id' => $formsemestreId,
            ':annee_scolaire' => $etu['annee']['annee_scolaire'] ?? $fs['annee_scolaire'],
            ':codedecision_id' => $codeDecisionId,
            ':ects' => $ue['ects'] ?? null,
        ]);
    }
}

function upsertUE(PDO $pdo, int $ueId): void
{
    $stmt = $pdo->prepare("INSERT IGNORE INTO UE(ue_id) VALUES(:ue_id)");
    $stmt->execute([':ue_id' => $ueId]);
}

function linkUEToFormSemestre(PDO $pdo, int $formsemestreId, int $ueId): void
{
    $stmt = $pdo->prepare("INSERT IGNORE INTO FormSemestreUE(formsemestre_id, ue_id) VALUES(:fs, :ue)");
    $stmt->execute([':fs' => $formsemestreId, ':ue' => $ueId]);
}

function syncResultatsRCUE(PDO $pdo, array $etu, int $etudiantId, int $formsemestreId, array $fs): void
{
    $ordre = 1;

    foreach (($etu['rcues'] ?? []) as $rcue) {
        if (!isset($rcue['code'])) {
            $ordre++;
            continue;
        }

        $codeDecisionId = getOrCreateCodeDecision($pdo, $rcue['code'], 'RCUE');

        $stmt = $pdo->prepare("\n            INSERT INTO ResultatRCUE(\n                etudiant_id, formsemestre_id, competence_id, annee_scolaire, ordre_rcue, codedecision_id, moyenne\n            )\n            VALUES(:etudiant_id, :formsemestre_id, NULL, :annee_scolaire, :ordre_rcue, :codedecision_id, :moyenne)\n            ON DUPLICATE KEY UPDATE\n                codedecision_id = VALUES(codedecision_id),\n                moyenne = VALUES(moyenne)\n        ");

        $stmt->execute([
            ':etudiant_id' => $etudiantId,
            ':formsemestre_id' => $formsemestreId,
            ':annee_scolaire' => $etu['annee']['annee_scolaire'] ?? $fs['annee_scolaire'],
            ':ordre_rcue' => $ordre,
            ':codedecision_id' => $codeDecisionId,
            ':moyenne' => $rcue['moy'] ?? null,
        ]);

        $resultatRcueId = (int)fetchOne($pdo, "\n            SELECT resultat_rcue_id\n            FROM ResultatRCUE\n            WHERE etudiant_id = :etud\n            AND formsemestre_id = :fs\n            AND ordre_rcue = :ordre\n        ", [':etud' => $etudiantId, ':fs' => $formsemestreId, ':ordre' => $ordre]);

        syncDetailRcueUE($pdo, $resultatRcueId, $rcue, $formsemestreId);
        $ordre++;
    }
}

function syncDetailRcueUE(PDO $pdo, int $resultatRcueId, array $rcue, int $formsemestreId): void
{
    foreach (['ue_1' => 1, 'ue_2' => 2] as $key => $position) {
        if (!isset($rcue[$key]['ue_id'])) continue;

        $ue = $rcue[$key];
        $ueId = (int)$ue['ue_id'];
        $codeDecisionId = isset($ue['code']) ? getOrCreateCodeDecision($pdo, $ue['code'], 'UE') : null;

        upsertUE($pdo, $ueId);
        linkUEToFormSemestre($pdo, $formsemestreId, $ueId);

        $stmt = $pdo->prepare("\n            INSERT INTO ResultatRCUE_UE(resultat_rcue_id, ue_id, position_ue, moyenne_ue, codedecision_id)\n            VALUES(:resultat_rcue_id, :ue_id, :position_ue, :moyenne_ue, :codedecision_id)\n            ON DUPLICATE KEY UPDATE\n                moyenne_ue = VALUES(moyenne_ue),\n                codedecision_id = VALUES(codedecision_id)\n        ");

        $stmt->execute([
            ':resultat_rcue_id' => $resultatRcueId,
            ':ue_id' => $ueId,
            ':position_ue' => $position,
            ':moyenne_ue' => $ue['moy'] ?? null,
            ':codedecision_id' => $codeDecisionId,
        ]);
    }
}

function syncAutorisations(PDO $pdo, array $etu, int $etudiantId): void
{
    foreach (($etu['autorisations'] ?? []) as $auto) {
        if (!isset($auto['id'])) continue;

        $stmt = $pdo->prepare("\n            INSERT INTO AutorisationPassage(\n                autorisation_id, etudiant_id, origin_formsemestre_id, formation_code, semestre_id_autorise, date_autorisation\n            )\n            VALUES(:autorisation_id, :etudiant_id, :origin_formsemestre_id, :formation_code, :semestre_id_autorise, :date_autorisation)\n            ON DUPLICATE KEY UPDATE\n                formation_code = VALUES(formation_code),\n                semestre_id_autorise = VALUES(semestre_id_autorise),\n                date_autorisation = VALUES(date_autorisation)\n        ");

        $stmt->execute([
            ':autorisation_id' => $auto['id'],
            ':etudiant_id' => $etudiantId,
            ':origin_formsemestre_id' => $auto['origin_formsemestre_id'] ?? null,
            ':formation_code' => $auto['formation_code'] ?? null,
            ':semestre_id_autorise' => $auto['semestre_id'] ?? null,
            ':date_autorisation' => sqlDateTime($auto['date'] ?? null),
        ]);
    }
}

// ========================
// COHORTES
// ========================

function generateCohortes(PDO $pdo): void
{
    $sql = "\n        INSERT INTO Cohorte(annee_entree, dep_id, formation_id, parcours_id, modalite, libelle)\n        SELECT DISTINCT\n            p.annee_scolaire,\n            p.dep_id,\n            p.formation_id,\n            p.parcours_id,\n            p.modalite,\n            CONCAT('Cohorte ', COALESCE(f.acronyme, p.formation_id), ' ', COALESCE(p.modalite, ''), ' ', p.annee_scolaire)\n        FROM PositionAnnuelleEtudiant p\n        LEFT JOIN Formation f ON f.formation_id = p.formation_id\n        WHERE p.ordre_annee = 1\n        AND p.formation_id IS NOT NULL\n        ON DUPLICATE KEY UPDATE\n            libelle = VALUES(libelle)\n    ";

    $pdo->exec($sql);
}

function generateCohorteEtudiants(PDO $pdo): void
{
    $sql = "\n        INSERT IGNORE INTO CohorteEtudiant(cohorte_id, etudiant_id, date_entree, type_entree)\n        SELECT\n            c.cohorte_id,\n            p.etudiant_id,\n            NULL,\n            'PRIMO_ENTRANT'\n        FROM Cohorte c\n        JOIN PositionAnnuelleEtudiant p\n            ON p.annee_scolaire = c.annee_entree\n            AND p.formation_id <=> c.formation_id\n            AND p.parcours_id <=> c.parcours_id\n            AND p.modalite <=> c.modalite\n        WHERE p.ordre_annee = 1\n    ";

    $pdo->exec($sql);
}