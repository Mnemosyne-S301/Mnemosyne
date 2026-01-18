<?php
require_once 'config/config.php';
require_once 'Models/StatsDAO.php';

$dao = StatsDAO::getModel();

echo "=== TEST REQUÊTE COHORTE ===\n\n";

$formations = ['INFO', 'GEA', 'GEII', 'RT', 'CJ', 'SD'];

foreach ([2021, 2022, 2023] as $annee) {
    echo "--- ANNÉE $annee ---\n";
    foreach ($formations as $formation) {
        $result = $dao->getCohorteParAnneeEtFormation($annee, $formation);
        echo "  $formation: " . count($result) . " étudiants\n";
    }
    echo "\n";
}
