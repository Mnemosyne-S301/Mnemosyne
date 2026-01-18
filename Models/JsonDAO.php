<?php
require_once __DIR__ . "/SourceDataDAO.php";
require_once __DIR__ . "/Etudiant.php";
require_once __DIR__ . "/Departement.php";
require_once __DIR__ . "/Formsemestre.php";
require_once __DIR__ . "/Decision.php";

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
        return $allFiles;
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
            $dpt_array = array(
                'dep_id'        => $dpt['id'],
                'accronyme'     => $dpt['acronym'],
                'nom'           => $dpt['name'],
                'ville'         => $dpt['city'],
                'region'        => $dpt['region'],
                'academie'      => $dpt['academy'],
                'uai'           => $dpt['uai']
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

                        // création du dico nous même
                        $current_formsemestre_array = array(
                            'formsemestre_id'       => $current_formsemestre['id'],
                            'titre'                 => $current_formsemestre['titre'],
                            'etape_apo'             => $current_formsemestre['etape_apo'],
                            'anneeformation_id'     => $current_formsemestre['formation']['formation_id']
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
        $allDecisionInstances = [];

        foreach($allFiles as $filename)
        {
            if(preg_match("/^decisions_jury_[0-9]{4}_fs_[0-9]{3,4}_/", $filename)) // verifie qu'on lit bien que les decisions de jury ici
            {
                $current_file_path = $this->JSON_PATH . "/" . $filename;
                $current_file_content = file_get_contents($current_file_path);
                $current_data = json_decode($current_file_content, true);

                // parcours des différents array de chaques fichiers
                foreach($current_data as $current_decision)
                {
                    // on vérifie pas les doublons ici, non

                    if($current_decision['annee'] == null)
                    {
                        continue; // on skip l'iteration actuelle, pas très propre mais temporaire
                    }

                    // recherche des valeurs de l'année scolaire et de l'id formsemestre dans le nom de fichier
                    preg_match("/^decisions_jury_([0-9]{4})_fs_([0-9])/", $filename, $matches);
                    $current_annee_scolaire = $matches[1];  // qui a matché le 1er groupe de la regex
                    $current_formsemstre_id = $matches[2];  // qui a matché le 2eme groupe
                    // création du dico nous même
                    $current_decision_array = array(
                        'code'              => $current_decision['annee']['code'],
                        'code_nip'          => $current_decision['code_nip'],
                        'annee_scolaire'    => $current_annee_scolaire,
                        'formsemestre_id'   => $current_formsemstre_id
                    );

                    // instanciate the object
                    $allDecisionInstances[] = new Decision($current_decision_array);
            
                }
            }
        }

        return $allDecisionInstances;
    }

    // (Méthodes à implémenter ou à supprimer)
    // public function findformation_by_id(string $id) {}
    // public function finddepartement_by_accronyme(string $accronyme) {}
    // public function findformsemestre_by_id(string $id) {}
    // public function findDecisionsByFormsemestre(string $id) {}
    // public function findEtudiantsByDepartement(string $dept) {}
    // public function findFormsemestresByFormation(string $acronyme) {}
    // public function findUEByCode(string $code) {}
}

