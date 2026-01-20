<?php
require_once __DIR__ . "/../Models/ScolariteDAO.php";
//require_once __DIR__ . "../Models/SourceDataDAO.php";
require_once __DIR__ . "/../Models/JsonDAO.php";
//require_once __DIR__ . "../Models/ScodocDAO.php";

class Service_syn
{
    private static ?ScolariteDAO $scolariteDAO = null;
    private static $sourcedataDAO = null;

    public function __construct()
    {
        if (self::$scolariteDAO === null)
        {
            self::$scolariteDAO = ScolariteDAO::getModel();
        }

        if (self::$sourcedataDAO === null)
        {
            self::$sourcedataDAO = new JsonDAO();
        }
    }

    public function sync_etudiant()
    {
        $all_etudiant = self::$sourcedataDAO->findall_etudiant();

        $all_etudiant_dict = [];
        foreach($all_etudiant as $e)
        {
            $all_etudiant_dict[] = $e->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addEtudiant($all_etudiant_dict);
    }

    public function sync_departement()
    {
        $all_departement = self::$sourcedataDAO->findall_departement();

        $all_departement_dict = [];
        foreach($all_departement as $d)
        {
            $all_departement_dict[] = $d->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addDepartement($all_departement_dict);
    }

    public function sync_formation()
    {
        $all_formation = self::$sourcedataDAO->findall_formation();

        $all_formation_dict = [];
        foreach($all_formation as $f)
        {
            $all_formation_dict[] = $f->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addFormation($all_formation_dict);
    }

    public function sync_parcours()
    {
        $all_parcours = self::$sourcedataDAO->findall_parcours();

        $all_parcours_dict = [];
        foreach($all_parcours as $p)
        {
            $all_parcours_dict[] = $p->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addParcours($all_parcours_dict);
    }


    /** Permet d'ajouter un parcours pas défaut au formation ne possédant de parcours. Pour que la base de donnée de Mnemosyne fonctionne,
     * chaque formation doit au minimum avoir un parcours. Ce parcours par défaut peut être utile dans le cas de la première année d'une
     * formation où aucun parcours n'a encore été choisi, par exemple. 
     * Les valeurs de ce parcours par défaut sont écrite en dures dans le code. 
     */
    /*
    public function add_default_parcours()
    {
        $all_formation = self::$sourcedataDAO->findall_formation();
        $all_formation_id = []; 
        $all_default_parcours = [];

        // on récupère uniquement les id, reste pas necessaire
        foreach($all_formation as $formation)
        {
            $all_formation_id[] = $formation->getFormationId();
        }

        foreach($all_formation_id as $formation_id)
        {
            $current_default_parcours = array(
                'parcours_id' => ($formation_id) * 10, // PAS PROPRE À CHANGER (deso) (cf. JsonDAO.php ligne 256)
                'code' => 'DEFAULT',
                'libelle' => 'Parcours par defaut',
                'formation_id' => $formation_id 
            );
            $all_default_parcours[] = $current_default_parcours;
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addParcours($all_default_parcours);
    }
    */

    public function sync_anneeFormation()
    {
        $all_anneeFormation = self::$sourcedataDAO->findall_anneeFormation();

        $all_anneeFormation_dict = [];
        foreach($all_anneeFormation as $af)
        {
            $all_anneeFormation_dict[] = $af->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addAnneeFormation($all_anneeFormation_dict);
    }

    public function sync_rcue()
    {
        $all_rcue = self::$sourcedataDAO->findall_rcue();

        $all_rcue_dict = [];
        foreach($all_rcue as $rcue)
        {
            $all_rcue_dict[] = $rcue->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addRCUE($all_rcue_dict);
    }

    public function sync_formsemestre()
    {
        $all_formsemestre = self::$sourcedataDAO->findall_formsemestre();

        $all_formsemestre_dict = [];
        foreach($all_formsemestre as $f)
        {
            $all_formsemestre_dict[] = $f->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addFormSemestre($all_formsemestre_dict);
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
    public function sync_ue()
    {
        $all_ue = self::$sourcedataDAO->findall_ue();

        $all_ue_dict = [];
        foreach($all_ue as $ue)
        {
            $all_ue_dict[] = $ue->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addUE($all_ue_dict);
    }

    public function sync_code_annee()
    {
        $all_code_annee_dict = [
            ['code' => 'ABAN',      'signification' => 'Abandon constaté (sans lettre de démission)'],
            ['code' => 'ABL',       'signification' => 'Année Blanche'],
            ['code' => 'ADM' ,      'signification' => 'Admis'],
            ['code' => 'ADJ' ,      'signification' => 'Admis par décision de jury'],
            ['code' => 'ATJ' ,      'signification' => 'Non validé pour une autre raison, voir règlement local'],
            ['code' => 'DEF' ,      'signification' => '(défaillance) Non évalué par manque assiduité'],
            ['code' => 'DEM' ,      'signification' => 'Démission'],
            ['code' => 'EXC' ,      'signification' => 'EXClusion, décision réservée à des décisions disciplinaires'],
            ['code' => 'NAR' ,      'signification' => 'Non admis, réorientation'],
            ['code' => 'PAS1NCI' ,  'signification' => 'Non admis, mais passage par décision de jury (Passage en Année Supérieure avec au moins 1 Niveau de Compétence Insuffisant (RCUE<8))'],
            ['code' => 'PASD' ,     'signification' => 'Non admis, mais passage de droit'],
            ['code' => 'RAT' ,      'signification' => 'En attente d’un rattrapage'],
            ['code' => 'RED' ,      'signification' => 'Ajourné, mais autorisé à redoubler']
        ];

        // remplissage de la base de donnée
        self::$scolariteDAO->addCodeAnnee($all_code_annee_dict);
    }

    public function sync_decision_annee()
    {
        $all_decision = self::$sourcedataDAO->findall_decision();

        $all_decision_dict = [];
        foreach($all_decision as $d)
        {
            $all_decision_dict[] = $d->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addEffectuerAnnee($all_decision_dict);
    }

}
?>