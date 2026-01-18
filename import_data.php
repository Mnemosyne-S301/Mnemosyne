<?php
/**
 * Script d'importation des données JSON vers la base de données scolarite
 * Importe : Départements, Formations, Parcours, AnnéeFormation, FormSemestre, UE, RCUE, Étudiants et décisions
 * 
 * Usage : php import_data.php [--verbose] [--year=2022]
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/Models/JsonDAO.php';
require_once __DIR__ . '/Models/ScolariteDAO.php';

// Options de ligne de commande
$verbose = in_array('--verbose', $argv ?? []);
$yearFilter = null;
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--year=(\d{4})$/', $arg, $m)) {
        $yearFilter = (int)$m[1];
    }
}

$JSON_PATH = __DIR__ . '/Database/example/json';

/**
 * Supprime les accents d'une chaine pour eviter les problemes d'encodage
 */
function removeAccents($string) {
    if (empty($string)) return $string;
    
    // Utiliser iconv pour une conversion plus robuste
    $result = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    if ($result === false) {
        // Fallback: supprimer les caracteres non-ASCII
        $result = preg_replace('/[^\x00-\x7F]/', '', $string);
    }
    return $result;
}

function logMsg($msg, $forceShow = false) {
    global $verbose;
    if ($verbose || $forceShow) {
        echo $msg . "\n";
    }
}

function logSection($title) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "  " . removeAccents($title) . "\n";
    echo str_repeat('=', 60) . "\n";
}

function logSuccess($msg) {
    echo "[OK] $msg\n";
}

function logError($msg) {
    echo "[ERREUR] $msg\n";
}

echo "================================================================\n";
echo "     IMPORTATION DES DONNEES JSON VERS LA BASE SCOLARITE        \n";
echo "================================================================\n";

try {
    // Connexion PDO simple
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $scolariteDAO = ScolariteDAO::getModel();
    
    // ============================================================
    // ETAPE 1 : Nettoyage des tables
    // ============================================================
    logSection("ETAPE 1 : Nettoyage des tables existantes");
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = [
        'EffectuerAnnee', 'EffectuerUE', 'EffectuerRCUE', 
        'CodeRCUE', 'CodeUE', 'CodeAnnee', 
        'Etudiant', 'UE', 'RCUE', 'FormSemestre', 
        'AnneeFormation', 'Parcours', 'Formation', 'Departement'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("DELETE FROM $table");
        $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
        logMsg("  Table $table videe");
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    logSuccess("Toutes les tables ont été nettoyées");
    
    logSection("ETAPE 2 : Import des departements");
    
    $deptContent = file_get_contents($JSON_PATH . '/departements.json');
    $deptData = json_decode($deptContent, true);
    
    if ($deptData && count($deptData) > 0) {
        $deptRows = [];
        foreach ($deptData as $dept) {
            $deptRows[] = [
                'dep_id' => $dept['id'],
                'accronyme' => removeAccents($dept['acronym'] ?? ''),
                'description' => removeAccents(substr($dept['description'] ?? '', 0, 50)),
                'visible' => ($dept['visible'] ?? false) ? 1 : 0,
                'date_creation' => $dept['date_creation'] ?? null,
                'nom_dep' => removeAccents(substr($dept['dept_name'] ?? '', 0, 50))
            ];
        }
        $scolariteDAO->addDepartement($deptRows);
        logSuccess(count($deptRows) . " departements importes");
    }
    
    logSection("ETAPE 3 : Import des formations");
    
    $formContent = file_get_contents($JSON_PATH . '/formations.json');
    $formData = json_decode($formContent, true);
    
    if ($formData && count($formData) > 0) {
        $formRows = [];
        foreach ($formData as $form) {
            $typeParcours = min($form['type_parcours'] ?? 0, 255);
            $formRows[] = [
                'formation_id' => $form['id'],
                'accronyme' => removeAccents(substr($form['acronyme'] ?? '', 0, 50)),
                'titre' => removeAccents(substr($form['titre'] ?? '', 0, 50)),
                'version' => min($form['version'] ?? 1, 255),
                'formation_code' => substr($form['formation_code'] ?? '', 0, 50),
                'type_parcours' => $typeParcours,
                'titre_officiel' => removeAccents(substr($form['titre_officiel'] ?? '', 0, 50)),
                'commentaire' => null,
                'code_specialite' => $form['code_specialite'] ?? null,
                'dep_id' => $form['dept_id'] ?? null
            ];
        }
        $scolariteDAO->addFormation($formRows);
        logSuccess(count($formRows) . " formations importees");
    }
    
    logSection("ETAPE 4 : Import des codes de decision");
    
    $codes = [
        ['code' => 'ADM', 'signification' => 'Admis'],
        ['code' => 'AJ', 'signification' => 'Ajourne'],
        ['code' => 'ADMC', 'signification' => 'Admis par compensation'],
        ['code' => 'ADJ', 'signification' => 'Ajourne par jury'],
        ['code' => 'DEF', 'signification' => 'Defaillant'],
        ['code' => 'ABS', 'signification' => 'Absent'],
        ['code' => 'ADSUP', 'signification' => 'Admis superieur'],
        ['code' => 'PASD', 'signification' => 'Passage avec dettes'],
        ['code' => 'ATT', 'signification' => 'Attente'],
        ['code' => 'CMP', 'signification' => 'Compense'],
        ['code' => 'RED', 'signification' => 'Redoublement'],
        ['code' => 'NAR', 'signification' => 'Non autorise a redoubler'],
        ['code' => 'DEM', 'signification' => 'Demission'],
        ['code' => 'ABL', 'signification' => 'Abandon libre'],
        ['code' => 'RAT', 'signification' => 'En attente de rattrapage'],
        ['code' => 'EXCLU', 'signification' => 'Exclusion'],
    ];
    
    $scolariteDAO->addCodeAnnee($codes);
    $scolariteDAO->addCodeUE($codes);
    $scolariteDAO->addCodeRCUE($codes);
    logSuccess(count($codes) . " codes de décision ajoutés (CodeAnnee, CodeUE, CodeRCUE)");
    
    // Créer un mapping code -> id pour utilisation ultérieure
    $codeAnneeMap = [];
    $result = $pdo->query("SELECT codeannee_id, code FROM CodeAnnee");
    foreach ($result as $row) {
        $codeAnneeMap[$row['code']] = $row['codeannee_id'];
    }
    
    $codeUEMap = [];
    $result = $pdo->query("SELECT codeue_id, code FROM CodeUE");
    foreach ($result as $row) {
        $codeUEMap[$row['code']] = $row['codeue_id'];
    }
    
    $codeRCUEMap = [];
    $result = $pdo->query("SELECT codercue_id, code FROM CodeRCUE");
    foreach ($result as $row) {
        $codeRCUEMap[$row['code']] = $row['codercue_id'];
    }
    
    logSection("ETAPE 5 : Import des etudiants");
    
    $allStudents = []; // code_nip => etat
    $files = glob($JSON_PATH . '/decisions_jury_*.json');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (!is_array($data)) continue;
        
        foreach ($data as $etud) {
            $codeNip = $etud['code_nip'] ?? null;
            if ($codeNip && !isset($allStudents[$codeNip])) {
                $allStudents[$codeNip] = $etud['etat'] ?? '';
            }
        }
    }
    
    if (count($allStudents) > 0) {
        $etudRows = [];
        foreach ($allStudents as $nip => $etat) {
            $etudRows[] = ['code_nip' => $nip, 'etat' => $etat];
        }
        
        // Inserer par lots de 500 pour eviter les requetes trop longues
        $batchSize = 500;
        $batches = array_chunk($etudRows, $batchSize);
        foreach ($batches as $batch) {
            $scolariteDAO->addEtudiant($batch);
        }
        logSuccess(count($etudRows) . " etudiants uniques importes");
    }
    
    // Creer mapping code_nip -> etudiant_id
    $etudiantMap = [];
    $result = $pdo->query("SELECT etudiant_id, code_nip FROM Etudiant");
    foreach ($result as $row) {
        $etudiantMap[$row['code_nip']] = $row['etudiant_id'];
    }
    
    // ============================================================
    // ETAPE 6 : Import des Parcours et AnneeFormation depuis formsemestres
    // ============================================================
    logSection("ETAPE 6 : Import des parcours et annees de formation");
    
    $parcoursSet = []; // "formation_id-code" => parcours data
    $anneeFormationSet = []; // "parcours_id-ordre" => anneeformation data
    $parcoursIdCounter = 1;
    $anneeFormationIdCounter = 1;
    
    $formsemestreFiles = glob($JSON_PATH . '/formsemestres_*.json');
    
    foreach ($formsemestreFiles as $file) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (!is_array($data)) continue;
        
        foreach ($data as $fs) {
            $formationId = $fs['formation_id'] ?? null;
            $parcoursList = $fs['parcours'] ?? [];
            
            if (!$formationId) continue;
            
            foreach ($parcoursList as $parcours) {
                $parcoursCode = $parcours['code'] ?? '';
                $parcoursKey = "$formationId-$parcoursCode";
                
                if (!isset($parcoursSet[$parcoursKey])) {
                    $parcoursSet[$parcoursKey] = [
                        'parcours_id' => $parcoursIdCounter++,
                        'code' => substr($parcoursCode, 0, 50),
                        'libelle' => removeAccents(substr($parcours['libelle'] ?? '', 0, 50)),
                        'formation_id' => $formationId
                    ];
                }
                
                $parcoursId = $parcoursSet[$parcoursKey]['parcours_id'];
                
                // Extraire les années du parcours
                $annees = $parcours['annees'] ?? [];
                foreach ($annees as $anneeNum => $anneeData) {
                    $ordre = $anneeData['ordre'] ?? (int)$anneeNum;
                    $anneeKey = "$parcoursId-$ordre";
                    
                    if (!isset($anneeFormationSet[$anneeKey])) {
                        $anneeFormationSet[$anneeKey] = [
                            'anneeformation_id' => $anneeFormationIdCounter++,
                            'ordre' => $ordre,
                            'parcours_id' => $parcoursId
                        ];
                    }
                }
            }
        }
    }
    
    // Inserer les parcours
    if (count($parcoursSet) > 0) {
        $parcoursRows = array_values($parcoursSet);
        $batchSize = 500;
        $batches = array_chunk($parcoursRows, $batchSize);
        foreach ($batches as $batch) {
            $scolariteDAO->addParcours($batch);
        }
        logSuccess(count($parcoursRows) . " parcours importes");
    }
    
    // Inserer les annees de formation
    if (count($anneeFormationSet) > 0) {
        $anneeFormRows = array_values($anneeFormationSet);
        $batchSize = 500;
        $batches = array_chunk($anneeFormRows, $batchSize);
        foreach ($batches as $batch) {
            $scolariteDAO->addAnneeFormation($batch);
        }
        logSuccess(count($anneeFormRows) . " annees de formation importees");
    }
    
    // Mapping pour retrouver les IDs
    $parcoursMap = []; // "formation_id-code" => parcours_id
    foreach ($parcoursSet as $key => $p) {
        $parcoursMap[$key] = $p['parcours_id'];
    }
    
    $anneeFormationMap = []; // "parcours_id-ordre" => anneeformation_id
    foreach ($anneeFormationSet as $key => $af) {
        $anneeFormationMap[$key] = $af['anneeformation_id'];
    }
    
    // ============================================================
    // ETAPE 7 : Import des FormSemestre
    // ============================================================
    logSection("ETAPE 7 : Import des semestres de formation");
    
    $formsemestreRows = [];
    $formsemestreIds = []; // Pour eviter les doublons
    
    foreach ($formsemestreFiles as $file) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (!is_array($data)) continue;
        
        foreach ($data as $fs) {
            $fsId = $fs['id'] ?? null;
            if (!$fsId || isset($formsemestreIds[$fsId])) continue;
            
            $formationId = $fs['formation_id'] ?? null;
            $semestreNum = $fs['semestre_id'] ?? 1;
            
            // Limiter semestre_num a 255 (TINYINT UNSIGNED max)
            if ($semestreNum > 255) $semestreNum = 255;
            if ($semestreNum < 1) $semestreNum = 1;
            
            // Trouver l'anneeformation_id correspondant
            // L'ordre = ceil(semestre_num / 2) : S1-S2 -> BUT1, S3-S4 -> BUT2, S5-S6 -> BUT3
            $ordre = (int)ceil($semestreNum / 2);
            
            // Chercher le premier parcours de cette formation
            $anneeFormationId = null;
            $parcoursList = $fs['parcours'] ?? [];
            if (!empty($parcoursList)) {
                $firstParcours = $parcoursList[0];
                $parcoursKey = "$formationId-" . ($firstParcours['code'] ?? '');
                if (isset($parcoursMap[$parcoursKey])) {
                    $parcoursId = $parcoursMap[$parcoursKey];
                    $anneeKey = "$parcoursId-$ordre";
                    $anneeFormationId = $anneeFormationMap[$anneeKey] ?? null;
                }
            }
            
            // Formater les dates
            $dateDebut = $fs['date_debut'] ?? null;
            $dateFin = $fs['date_fin'] ?? null;
            if ($dateDebut) {
                $dateDebut = DateTime::createFromFormat('d/m/Y', $dateDebut);
                $dateDebut = $dateDebut ? $dateDebut->format('Y-m-d') : null;
            }
            if ($dateFin) {
                $dateFin = DateTime::createFromFormat('d/m/Y', $dateFin);
                $dateFin = $dateFin ? $dateFin->format('Y-m-d') : null;
            }
            
            $formsemestreRows[] = [
                'formsemestre_id' => $fsId,
                'titre' => removeAccents(substr($fs['titre'] ?? '', 0, 50)),
                'semestre_num' => $semestreNum,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'titre_long' => removeAccents(substr($fs['titre_num'] ?? $fs['titre'] ?? '', 0, 50)),
                'etape_apo' => substr($fs['etape_apo'] ?? '', 0, 50),
                'anneeformation_id' => $anneeFormationId
            ];
            
            $formsemestreIds[$fsId] = true;
        }
    }
    
    if (count($formsemestreRows) > 0) {
        $batchSize = 500;
        $batches = array_chunk($formsemestreRows, $batchSize);
        foreach ($batches as $batch) {
            $scolariteDAO->addFormSemestre($batch);
        }
        logSuccess(count($formsemestreRows) . " semestres de formation importes");
    }
    
    // ============================================================
    // ETAPE 8 : Import des RCUE et UE depuis les fichiers de decisions
    // ============================================================
    logSection("ETAPE 8 : Import des RCUE et UE");
    
    $rcueSet = []; // "anneeformation_id-competence" => rcue data
    $ueSet = []; // ue_id => ue data
    $rcueIdCounter = 1;
    
    foreach ($files as $file) {
        // Extraire année et formsemestre_id du nom de fichier
        if (!preg_match('/decisions_jury_(\d{4})_fs_(\d+)_/', basename($file), $matches)) {
            continue;
        }
        
        $anneeScolaire = (int)$matches[1];
        $formsemestreId = (int)$matches[2];
        
        // Appliquer le filtre d'année si spécifié
        if ($yearFilter !== null && $anneeScolaire !== $yearFilter) {
            continue;
        }
        
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (!is_array($data)) continue;
        
        foreach ($data as $etud) {
            $rcues = $etud['rcues'] ?? [];
            
            foreach ($rcues as $rcueIndex => $rcue) {
                // Chaque RCUE contient deux UE : ue_1 et ue_2
                foreach (['ue_1', 'ue_2'] as $ueKey) {
                    if (!isset($rcue[$ueKey])) continue;
                    
                    $ueData = $rcue[$ueKey];
                    $ueId = $ueData['ue_id'] ?? null;
                    
                    if ($ueId && !isset($ueSet[$ueId])) {
                        $ueSet[$ueId] = [
                            'ue_id' => $ueId,
                            'rcue_id' => null, // Sera defini apres creation des RCUE
                            'formsemestre_id' => $formsemestreId
                        ];
                    }
                }
            }
        }
    }
    
    // Pour les RCUE, on va les creer basiquement avec une competence generique
    // car les donnees JSON ne contiennent pas directement le nom de la competence
    $rcueRows = [];
    $rcueIndex = 1;
    
    // Regrouper les UE par formsemestre pour creer les RCUE
    $ueByFormsemestre = [];
    foreach ($ueSet as $ueId => $ue) {
        $fsId = $ue['formsemestre_id'];
        if (!isset($ueByFormsemestre[$fsId])) {
            $ueByFormsemestre[$fsId] = [];
        }
        $ueByFormsemestre[$fsId][$ueId] = $ue;
    }
    
    // Creer une RCUE par paire d'UE dans chaque formsemestre
    $ueToRcue = []; // ue_id => rcue_id
    
    foreach ($ueByFormsemestre as $fsId => $ues) {
        $ueIds = array_keys($ues);
        sort($ueIds);
        
        // Grouper par paires (simplification)
        for ($i = 0; $i < count($ueIds); $i += 2) {
            $rcueId = $rcueIdCounter++;
            
            $rcueRows[] = [
                'nomCompetence' => "Competence " . ceil(($i + 1) / 2),
                'niveau' => 1,
                'anneeformation_id' => null // Simplifie
            ];
            
            // Associer les UE a cette RCUE
            $ueToRcue[$ueIds[$i]] = $rcueId;
            if (isset($ueIds[$i + 1])) {
                $ueToRcue[$ueIds[$i + 1]] = $rcueId;
            }
        }
    }
    
    if (count($rcueRows) > 0) {
        $scolariteDAO->addRCUE($rcueRows);
        logSuccess(count($rcueRows) . " RCUE importees");
    }
    
    // Mettre a jour les UE avec les rcue_id
    $ueRows = [];
    foreach ($ueSet as $ueId => $ue) {
        $ueRows[] = [
            'ue_id' => $ueId,
            'rcue_id' => $ueToRcue[$ueId] ?? null,
            'formsemestre_id' => $ue['formsemestre_id']
        ];
    }
    
    if (count($ueRows) > 0) {
        $batchSize = 500;
        $batches = array_chunk($ueRows, $batchSize);
        foreach ($batches as $batch) {
            $scolariteDAO->addUE($batch);
        }
        logSuccess(count($ueRows) . " UE importees");
    }
    
    // ============================================================
    // ETAPE 9 : Import des decisions (EffectuerAnnee, EffectuerUE, EffectuerRCUE)
    // ============================================================
    logSection("ETAPE 9 : Import des decisions des etudiants");
    
    $effectuerAnneeRows = [];
    $effectuerUERows = [];
    $effectuerRCUERows = [];
    $processedDecisions = []; // Pour eviter les doublons
    
    foreach ($files as $file) {
        if (!preg_match('/decisions_jury_(\d{4})_fs_(\d+)_/', basename($file), $matches)) {
            continue;
        }
        
        $anneeScolaire = (int)$matches[1];
        $formsemestreId = (int)$matches[2];
        
        if ($yearFilter !== null && $anneeScolaire !== $yearFilter) {
            continue;
        }
        
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (!is_array($data)) continue;
        
        foreach ($data as $etud) {
            $codeNip = $etud['code_nip'] ?? null;
            if (!$codeNip || !isset($etudiantMap[$codeNip])) continue;
            
            $etudiantId = $etudiantMap[$codeNip];
            
            // Décision annuelle
            $anneeData = $etud['annee'] ?? null;
            if ($anneeData && isset($anneeData['code'])) {
                $codeAnnee = $anneeData['code'];
                $decisionKey = "$anneeScolaire-$etudiantId-annee";
                
                if (!isset($processedDecisions[$decisionKey]) && isset($codeAnneeMap[$codeAnnee])) {
                    $effectuerAnneeRows[] = [
                        'annee_scolaire' => $anneeScolaire,
                        'code_nip' => $codeNip,
                        'formsemestre_id' => $formsemestreId,
                        'code_annee' => $codeAnnee
                    ];
                    $processedDecisions[$decisionKey] = true;
                }
            }
            
            // Décisions UE et RCUE
            $rcues = $etud['rcues'] ?? [];
            foreach ($rcues as $rcueIndex => $rcue) {
                // RCUE
                $rcueCode = $rcue['code'] ?? null;
                
                // UE
                foreach (['ue_1', 'ue_2'] as $ueKey) {
                    if (!isset($rcue[$ueKey])) continue;
                    
                    $ueData = $rcue[$ueKey];
                    $ueId = $ueData['ue_id'] ?? null;
                    $ueCode = $ueData['code'] ?? null;
                    
                    if ($ueId && $ueCode && isset($codeUEMap[$ueCode])) {
                        $ueDecisionKey = "$anneeScolaire-$etudiantId-$ueId";
                        if (!isset($processedDecisions[$ueDecisionKey])) {
                            $effectuerUERows[] = [
                                'annee_scolaire' => $anneeScolaire,
                                'ue_id' => $ueId,
                                'code_nip' => $codeNip,
                                'code_ue' => $ueCode
                            ];
                            $processedDecisions[$ueDecisionKey] = true;
                        }
                    }
                    
                    // RCUE (utiliser la première UE pour identifier la RCUE)
                    if ($ueKey === 'ue_1' && $rcueCode && $ueId && isset($codeRCUEMap[$rcueCode])) {
                        $rcueDecisionKey = "$anneeScolaire-$etudiantId-rcue-$rcueIndex";
                        if (!isset($processedDecisions[$rcueDecisionKey])) {
                            $effectuerRCUERows[] = [
                                'annee_scolaire' => $anneeScolaire,
                                'code_nip' => $codeNip,
                                'ue_id' => $ueId,
                                'code_rcue' => $rcueCode
                            ];
                            $processedDecisions[$rcueDecisionKey] = true;
                        }
                    }
                }
            }
        }
    }
    
    // Inserer les decisions par lots
    $totalDecisions = 0;
    
    if (count($effectuerAnneeRows) > 0) {
        $batchSize = 100;
        $batches = array_chunk($effectuerAnneeRows, $batchSize);
        $inserted = 0;
        foreach ($batches as $batch) {
            try {
                $scolariteDAO->addEffectuerAnnee($batch);
                $inserted += count($batch);
            } catch (Exception $e) {
                logMsg("  Erreur batch EffectuerAnnee: " . $e->getMessage());
            }
        }
        $totalDecisions += $inserted;
        logSuccess("$inserted decisions annuelles importees");
    }
    
    if (count($effectuerUERows) > 0) {
        $batchSize = 100;
        $batches = array_chunk($effectuerUERows, $batchSize);
        $inserted = 0;
        foreach ($batches as $batch) {
            try {
                $scolariteDAO->addEffectuerUE($batch);
                $inserted += count($batch);
            } catch (Exception $e) {
                logMsg("  Erreur batch EffectuerUE: " . $e->getMessage());
            }
        }
        $totalDecisions += $inserted;
        logSuccess("$inserted decisions UE importees");
    }
    
    if (count($effectuerRCUERows) > 0) {
        $batchSize = 100;
        $batches = array_chunk($effectuerRCUERows, $batchSize);
        $inserted = 0;
        foreach ($batches as $batch) {
            try {
                $scolariteDAO->addEffectuerRCUE($batch);
                $inserted += count($batch);
            } catch (Exception $e) {
                logMsg("  Erreur batch EffectuerRCUE: " . $e->getMessage());
            }
        }
        $totalDecisions += $inserted;
        logSuccess("$inserted decisions RCUE importees");
    }
    
    // ============================================================
    // RESUME FINAL
    // ============================================================
    logSection("RESUME DE L'IMPORTATION");
    
    $stats = [
        'Departement' => $pdo->query("SELECT COUNT(*) FROM Departement")->fetchColumn(),
        'Formation' => $pdo->query("SELECT COUNT(*) FROM Formation")->fetchColumn(),
        'Parcours' => $pdo->query("SELECT COUNT(*) FROM Parcours")->fetchColumn(),
        'AnneeFormation' => $pdo->query("SELECT COUNT(*) FROM AnneeFormation")->fetchColumn(),
        'FormSemestre' => $pdo->query("SELECT COUNT(*) FROM FormSemestre")->fetchColumn(),
        'RCUE' => $pdo->query("SELECT COUNT(*) FROM RCUE")->fetchColumn(),
        'UE' => $pdo->query("SELECT COUNT(*) FROM UE")->fetchColumn(),
        'Etudiant' => $pdo->query("SELECT COUNT(*) FROM Etudiant")->fetchColumn(),
        'EffectuerAnnee' => $pdo->query("SELECT COUNT(*) FROM EffectuerAnnee")->fetchColumn(),
        'EffectuerUE' => $pdo->query("SELECT COUNT(*) FROM EffectuerUE")->fetchColumn(),
        'EffectuerRCUE' => $pdo->query("SELECT COUNT(*) FROM EffectuerRCUE")->fetchColumn(),
    ];
    
    echo "\n+------------------------+-------------+\n";
    echo "| Table                  | Nb lignes   |\n";
    echo "+------------------------+-------------+\n";
    foreach ($stats as $table => $count) {
        printf("| %-22s | %11s |\n", $table, number_format($count, 0, ',', ' '));
    }
    echo "+------------------------+-------------+\n";
    
    echo "\n================================================================\n";
    echo "              IMPORTATION TERMINEE AVEC SUCCES                  \n";
    echo "================================================================\n\n";
    
} catch (Exception $e) {
    logError($e->getMessage());
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
