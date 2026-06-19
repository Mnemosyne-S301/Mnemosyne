<?php
require_once __DIR__ . "/SourceDataDAO.php";
require_once __DIR__ . "/Etudiant.php";
require_once __DIR__ . "/Departement.php";
require_once __DIR__ . "/Formsemestre.php";
require_once __DIR__ . "/Decision.php";
require_once __DIR__ . "/Formation.php";
require_once __DIR__ . "/Parcours.php";
require_once __DIR__ . "/AnneeFormation.php";
require_once __DIR__ . "/RCUE.php";
require_once __DIR__ . "/UE.php";
require_once __DIR__ . "/Decision.php";

/**
 * Le DAO permettant de récupéré les données depuis les fichiers Json. 
 * @package DAO
 */
class JsonDAO
{
    private $jsonPath;
    private $JSON_PATH;

    public function __construct($jsonPath = null)
    {
        require_once __DIR__ . "/Departement.php";
        require_once __DIR__ . "/Formsemestre.php";
        require_once __DIR__ . "/Decision.php";

        $this->JSON_PATH = __DIR__ . "/../Database/example/json";
        if ($jsonPath !== null) {
            $this->JSON_PATH = $jsonPath;
        }
    }

    private function __getAllJsonFiles()
    {
        $allFiles = [];
        $json_dir = new DirectoryIterator($this->JSON_PATH);
        foreach($json_dir as $file)
        {
            if(!$file->isDot())
            {
                $allFiles[] = $file->getFilename();
            }
        }
        sort($allFiles, SORT_STRING);
        return $allFiles;
    }

    /**
     * Charge les metadonnees des semestres, indexees par identifiant ScoDoc.
     * Un meme semestre peut apparaitre dans plusieurs instantanes annuels.
     */
    private function getFormsemestreMetadata(): array
    {
        $metadata = [];

        foreach ($this->__getAllJsonFiles() as $filename) {
            if (!preg_match('/^formsemestres_[0-9]{4}\.json$/', $filename)) {
                continue;
            }

            $content = file_get_contents($this->JSON_PATH . '/' . $filename);
            $formsemestres = json_decode($content, true);
            if (!is_array($formsemestres)) {
                continue;
            }

            foreach ($formsemestres as $formsemestre) {
                if (!isset($formsemestre['id'])) {
                    continue;
                }
                $metadata[(int) $formsemestre['id']] = $formsemestre;
            }
        }

        return $metadata;
    }

    private function getAcademicYear(array $formsemestre): ?int
    {
        if (isset($formsemestre['annee_scolaire'])) {
            return (int) $formsemestre['annee_scolaire'];
        }

        $startDate = $formsemestre['date_debut_iso'] ?? null;
        if ($startDate === null) {
            return null;
        }

        $timestamp = strtotime($startDate);
        if ($timestamp === false) {
            return null;
        }

        $year = (int) date('Y', $timestamp);
        return (int) date('n', $timestamp) >= 8 ? $year : $year - 1;
    }

    /**
     * Departage plusieurs decisions du meme etudiant pour une annee universitaire.
     * La decision du semestre se terminant le plus tard est la plus representative.
     */
    private function shouldReplaceDecision(array $current, array $candidate): bool
    {
        foreach (['date_fin', 'semestre_num', 'formsemestre_id'] as $criterion) {
            if ($candidate[$criterion] === $current[$criterion]) {
                continue;
            }
            return $candidate[$criterion] > $current[$criterion];
        }

        return false;
    }

    public function findall_etudiant()
    {
        $allFiles = $this->__getAllJsonFiles();
        $allCodeNip = [];
        foreach($allFiles as $filename)
        {
            if(preg_match("/^decisions_jury_[0-9]{4}_fs/", $filename)) // verifie qu'on lit bien que les json de decisions de jury ici
            {
                $current_file_path = $this->JSON_PATH . "/" . $filename;
                $current_file_content = file_get_contents($current_file_path);
                $current_data = json_decode($current_file_content, true);

                // parcours des différents array de chaques fichiers
                foreach($current_data as $current_decision)
                {
                    $current_code_nip = $current_decision["code_nip"];
                    if(!in_array($current_code_nip, $allCodeNip) && $current_code_nip != null) // s'assure qu'il n'y ai pas de doublon
                    {
                        $allCodeNip[] = $current_code_nip;
                    }
                }
            }
        }

        // instanciation des objets
        $instances = [];
        foreach($allCodeNip as $nip)
        {
            $instances[] = new Etudiant(array("code_nip" => $nip, "etat" => "")); // etat jsp c'est quoi (😅)
        }

        return $instances;
    }

    public function findall_departement()
    {
        $allDepartementsInstances = [];
        $departements_filename = "departements.json";
        $departements_file_path = $this->JSON_PATH . "/" . $departements_filename;

        $departements_content = file_get_contents($departements_file_path);
        $departements_data = json_decode($departements_content, true);

        foreach($departements_data as $dpt)
        {
            // on crée dico nous même car les noms de la base de donnée et du modèle ne sont pas exactement les mêmes
            // que dans les jsons
            // Clés JSON réelles: id, acronym, description, visible, date_creation, dept_name
            $dpt_array = array(
                'dep_id'        => $dpt['id'],
                'accronyme'     => $dpt['acronym'],
                'description'   => $dpt['description'] ?? 'N/A',
                'visible'       => $dpt['visible'] ?? true,
                'date_creation' => $dpt['date_creation'] ?? date('Y-m-d H:i:s'),
                'nom_dep'       => $dpt['dept_name'] ?? $dpt['acronym']
            );
            $allDepartementsInstances[] = new Departement($dpt_array);
        }

        return $allDepartementsInstances;
    }

    public function findall_formsemestre()
    {
        $allFiles = $this->__getAllJsonFiles();
        $allFormsemestresInstances = [];
        $allFormsemestresId = []; // les id des formsemestre lues, au cas ou il y aurait des doublons

        foreach($allFiles as $filename)
        {
            if(preg_match("/^formsemestres_[0-9]{4}.json$/", $filename)) // verifie qu'on lit bien que les json des formsemstres ici
            {
                $current_file_path = $this->JSON_PATH . "/" . $filename;
                $current_file_content = file_get_contents($current_file_path);
                $current_data = json_decode($current_file_content, true);

                // parcours des différents array de chaques fichiers
                foreach($current_data as $current_formsemestre)
                {
                    $current_formsemstre_id = $current_formsemestre["id"];
                    // on s'assure qu'il n'y ai pas de doublons, normalement y en a pas
                    if(!in_array($current_formsemstre_id, $allFormsemestresId))
                    {                                                                                        
                        $allFormsemestresId[] = $current_formsemstre_id;

                        /*** IMPLEMENTATION LOGIQUE METIER ICI ****/
                        // on ne garde que les BUT 
                        if(!str_contains($current_formsemestre['titre'], "BUT"))
                        {
                            continue; // skip this iteration
                        }
                        $current_ordre_anneeFormation = intdiv((int)$current_formsemestre['semestre_id'] + 1, 2);
                        // x_annee = (x_semestre + 1) // 2  ; division euclidienne
                        $current_code_parcours = $current_formsemestre['parcours'][0]['code'];
                        // le parcours du formsemestre est le premier dans le cas
                        // ou il y en a plusieurs (choix arbitraire)
                        /**** FIN LOGIQUE METIER ****************/

                        // création du dico nous même
                        $current_formsemestre_array = array(
                            'formsemestre_id'       => $current_formsemestre['id'],
                            'titre'                 => $current_formsemestre['titre'],
                            'semestre_num'          => $current_formsemestre['semestre_id'],
                            'date_debut'            => $current_formsemestre['date_debut_iso'],
                            'date_fin'              => $current_formsemestre['date_fin_iso'],
                            'titre_long'            => $current_formsemestre['titre_num'],
                            'etape_apo'             => $current_formsemestre['etape_apo'],
                            'ordre_anneeFormation'  => $current_ordre_anneeFormation,
                            'code_parcours'         => $current_code_parcours,
                            'formation_id'          => $current_formsemestre['formation']['formation_id']
                        );

                        // instanciate the object
                        $allFormsemestresInstances[] = new Formsemestre($current_formsemestre_array);
                    }
                }
            }
        }

        return $allFormsemestresInstances;
    }

    public function findall_decision()
    {
        $allFiles = $this->__getAllJsonFiles();
        $formsemestreMetadata = $this->getFormsemestreMetadata();
        $selectedDecisions = [];

        foreach($allFiles as $filename)
        {
            if(preg_match("/^decisions_jury_[0-9]{4}_fs_([0-9]{3,4})_/", $filename, $matches))
            {
                $current_formsemestre_id = (int) $matches[1];
                $formsemestre = $formsemestreMetadata[$current_formsemestre_id] ?? null;
                if ($formsemestre === null || !str_contains($formsemestre['titre'] ?? '', 'BUT')) {
                    continue;
                }

                $current_annee_scolaire = $this->getAcademicYear($formsemestre);
                if ($current_annee_scolaire === null) {
                    continue;
                }

                $current_file_path = $this->JSON_PATH . "/" . $filename;
                $current_file_content = file_get_contents($current_file_path);
                $current_data = json_decode($current_file_content, true);
                if (!is_array($current_data)) {
                    continue;
                }

                foreach($current_data as $current_decision)
                {
                    $code = $current_decision['annee']['code'] ?? null;
                    $codeNip = $current_decision['code_nip'] ?? null;
                    if ($code === null || $codeNip === null) {
                        continue;
                    }

                    $candidate = [
                        'code' => $code,
                        'code_nip' => $codeNip,
                        'annee_scolaire' => $current_annee_scolaire,
                        'formsemestre_id' => $current_formsemestre_id,
                        'semestre_num' => (int) ($formsemestre['semestre_id'] ?? 0),
                        'date_fin' => strtotime($formsemestre['date_fin_iso'] ?? '') ?: 0,
                    ];
                    $decisionKey = $current_annee_scolaire . "\0" . $codeNip;

                    if (!isset($selectedDecisions[$decisionKey])
                        || $this->shouldReplaceDecision($selectedDecisions[$decisionKey], $candidate)) {
                        $selectedDecisions[$decisionKey] = $candidate;
                    }
                }
            }
        }

        ksort($selectedDecisions, SORT_STRING);
        $allDecisionInstances = [];
        foreach ($selectedDecisions as $decision) {
            $allDecisionInstances[] = new Decision([
                'code' => $decision['code'],
                'code_nip' => $decision['code_nip'],
                'annee_scolaire' => $decision['annee_scolaire'],
                'formsemestre_id' => $decision['formsemestre_id'],
            ]);
        }

        return $allDecisionInstances;
    }

    public function findall_formation()
    {
        $formation_filename = "formations.json";
        $formation_file_path = $this->JSON_PATH . '/' . $formation_filename;

        $formation_content = file_get_contents($formation_file_path);
        $formation_data = json_decode($formation_content, true);
        $allFormationInstances = [];

        if (!is_array($formation_data)) {
            return $allFormationInstances;
        }

        foreach($formation_data as $formation)
        {
            $formation_array = array(
                'formation_id' => $formation['formation_id'],
                'accronyme' => $formation['acronyme'], // accronyme un seul c , erreur dans le MEA et Modele relationnel etc. (flemme de corriger ça mnt, à faire)
                'titre' => $formation['titre'],
                'version' => $formation['version'],
                'formation_code' => $formation['formation_code'],
                'type_parcours' => $formation['type_parcours'],
                'titre_officiel' => $formation['titre_officiel'],
                'commentaire' => $formation['commentaire'] ?? '',
                'code_specialite' => $formation['code_specialite'] ?? '',
                'dep_id' => $formation['departement']['id']
            );

            // instanciate the object
            $allFormationInstances[] = new Formation($formation_array);
        }
        
        return $allFormationInstances;
    }

    
    public function findall_parcours()
    {
        $allFiles = $this->__getAllJsonFiles();
        $allParcoursInstances = [];

        foreach($allFiles as $filename)
        {
            if(preg_match("/^referentiel_competences_BUT_[0-9]{2,3}/", $filename)) // verifie qu'on lit bien que les referentiel competences ici
            {
                $current_file_path = $this->JSON_PATH . "/" . $filename;
                $current_file_content = file_get_contents($current_file_path);
                $current_formation = json_decode($current_file_content, true);

                if(empty($current_formation))
                {
                    continue; // skip l'iteration actuelle
                }

                preg_match("/^referentiel_competences_BUT_([0-9]{2,3})/", $filename, $matches);
                $current_formation_id = (int)$matches[1];

                $i = 0;
                foreach($current_formation['parcours'] as $current_parcours) // parcours du dictionnaire contenant tout les parcours
                {
                    $i++;
                    $current_parcours_array = array(
                        'parcours_id'   =>  ($current_formation_id * 10) + $i, // qu'on me pardonne, c'est pas propre, faudrait les cles primaires sur la BD, a faire plus tard (pas le tps)
                        'code'          =>  $current_parcours['code'],
                        'libelle'       =>  $current_parcours['libelle'],
                        'formation_id'  => $current_formation_id
                    );

                    $allParcoursInstances[] = new Parcours($current_parcours_array);
                }
                
            }
        }
        
        return $allParcoursInstances;
    }

    public function findall_anneeFormation()
    {
        $allFiles = $this->__getAllJsonFiles();
        $allAnneeFormationInstances = [];

        foreach($allFiles as $filename)
        {
            if(preg_match("/^referentiel_competences_BUT_[0-9]{2,3}/", $filename)) // verifie qu'on lit bien que les referentiel competences ici
            {
                $current_file_path = $this->JSON_PATH . "/" . $filename;
                $current_file_content = file_get_contents($current_file_path);
                $current_formation = json_decode($current_file_content, true);

                if(empty($current_formation))
                {
                    continue; // skip l'iteration actuelle
                }

                preg_match("/^referentiel_competences_BUT_([0-9]{2,3})/", $filename, $matches);
                $current_formation_id = (int)$matches[1];

                $i = 0;
                foreach($current_formation['parcours'] as $current_parcours) // parcours du dictionnaire contenant tout les parcours
                {
                    $i++;
                    $current_parcours_id = ($current_formation_id * 10) + $i; // qu'on me pardonne, pas propre, à changer
                    
                    foreach($current_parcours['annees'] as $current_anneeFormation)
                    {
                        $current_anneeFormation_array = array(
                            'ordre' => $current_anneeFormation['ordre'],
                            'parcours_id' => $current_parcours_id
                        );

                        // instanciate the object
                        $allAnneeFormationInstances[] = new AnneeFormation($current_anneeFormation_array);
                    }
                }
                
            }
        }
        return $allAnneeFormationInstances;
    }

    public function findall_rcue()
    {
        $allFiles = $this->__getAllJsonFiles();
        $allCompetenceInstances = [];

        foreach($allFiles as $filename)
        {
            if(preg_match("/^referentiel_competences_BUT_[0-9]{2,3}/", $filename)) // verifie qu'on lit bien que les referentiel competences ici
            {
                $current_file_path = $this->JSON_PATH . "/" . $filename;
                $current_file_content = file_get_contents($current_file_path);
                $current_formation = json_decode($current_file_content, true);

                if(empty($current_formation))
                {
                    continue; // skip l'iteration actuelle
                }

                preg_match("/^referentiel_competences_BUT_([0-9]{2,3})/", $filename, $matches);
                $current_formation_id = (int)$matches[1];

                $i = 0;
                foreach($current_formation['parcours'] as $current_parcours) // parcours du dictionnaire contenant tout les parcours
                {
                    $i++;
                    $current_parcours_id = ($current_formation_id * 10) + $i; // qu'on me pardonne, pas propre, à changer
                    
                    foreach($current_parcours['annees'] as $current_anneeFormation)
                    {
                        // on considera ici qu'une competence est une rcue, et inversement
                        // ATTENTION parcours clé valeur cette fois-ci
                        foreach($current_anneeFormation['competences'] as $current_competence_name => $current_competence)
                        {
                            $current_competence_array = array(
                                'nomCompetence' => $current_competence_name,
                                'niveau' => $current_competence['niveau'],
                                'ordre_anneeFormation' => $current_anneeFormation['ordre'],
                                'code_parcours' => $current_parcours['code'],
                                'formation_id' => $current_formation_id
                            );
                            /* Les champs 'ordre_anneeFormation' , 'code_parcours' et 'formation_id' servent
                             * à identifier un UNIQUE anneeFormation. On peut donc en déduire le 'anneeFormation_id'. 
                             * On aurait pas pu l'obtenir autrement car type SERIAL, connu que par la base de donnée.
                             */

                            // instanciate the objet
                            $allCompetenceInstances[] = new RCUE($current_competence_array);
                        }
                    }
                }
                
            }
        }
        return $allCompetenceInstances;
    }

    /**
     * Fonctionnalité NON IMPLÉMENTÉE suite à une erreur d'analyse dans la conception.
     * En effet, il était prévu qu'une UE ne soit associé qu'à un FormSemestre.
     * Après erreur de peuplement de la base de donnée, il s'est avérer 
     * qu'une même UE peut être présente sur plusieurs FormSemestre. 
     * 
     * Faute de temps, les changements necessaire n'ont pu être fait. 
     * 
     * - le 19 janvier 2026 à minuit
     */
    public function findall_ue()
    {
        $allFiles = $this->__getAllJsonFiles();
        $allUeInstances = [];

        foreach($allFiles as $filename)
        {
            if(preg_match("/^decisions_jury_[0-9]{4}_fs_[0-9]{3,4}_/", $filename)) // verifie qu'on lit bien que les referentiel competences ici
            {
                $current_file_path = $this->JSON_PATH . "/" . $filename;
                $current_file_content = file_get_contents($current_file_path);
                $current_data = json_decode($current_file_content, true);

                if(empty($current_data))
                {
                    continue; // skip l'iteration actuelle
                }

                // recherche id formsemestre dans le nom de fichier
                preg_match("/^decisions_jury_([0-9]{4})_fs_([0-9]{3,4})_/", $filename, $matches);
                $current_annee_scolaire = $matches[1];
                $current_formsemstre_id = $matches[2]; 
                
                // recherche des competences du premier parcours (choix par defaut) du l'annee de ce formsemestre
                // pas du tout optimise, dsl la complexite (plus le temps)
                $current_formsemestre_competences = [];
                $formsemestre_file_path = $this->JSON_PATH . '/formsemestres_' . $current_annee_scolaire . '.json';
                $formsemestre_file_content = file_get_contents($formsemestre_file_path);
                $formsemestre_file_data = json_decode($formsemestre_file_content, true);
                foreach($formsemestre_file_data as $formsemestre)
                {
                    if((int)$formsemestre['id'] == (int)$current_formsemstre_id)
                    {
                        $current_formsemetre_ordre_annee = intdiv((int)$formsemestre['semestre_id'] + 1, 2);
                        // cf. un fichier formsemestre_XXXX.json pour comprendre (pas propre, je sais)
                        $competences = $formsemestre['parcours'][0]['annees'][$current_formsemetre_ordre_annee]['competences'];
                        foreach($competences as $nom_competence => $competence)
                        {
                            $current_formsemestre_competences[] = $nom_competence;
                        }
                    }
                }

                $current_decision = $current_data[0]; // on prend qu'un echantillon pour recuperer les UE
                $current_ues = $current_decision['ues'];
                $i = 0;
                foreach($current_ues as $ue)
                {
                    $current_ue_array = array(
                        'ue_id' => $ue['ue_id'],
                        'formsemestre_id' => $current_formsemstre_id,
                        'nomCompetence' => $current_formsemestre_competences[$i] ?? ""
                    );

                    // instanciate the object
                    $current_ue = new UE($current_ue_array);
                    $allUeInstances[] = $current_ue;

                    $i++;
                }
                
            }
        }
        return $allUeInstances;
    }

    /*
    public function findformation_by_id(string $id);
    public function finddepartement_by_accronyme(string $accronyme);
    public function findformsemestre_by_id(string $id);

    public function findDecisionsByFormsemestre(string $id);
    public function findEtudiantsByDepartement(string $dept);
    public function findFormsemestresByFormation(string $acronyme);
    public function findUEByCode(string $code);
    */
}

