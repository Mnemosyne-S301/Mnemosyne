<?php
require_once 'config/config.php';
require_once 'Models/ScolariteDAO.php';

$dao = ScolariteDAO::getModel();

echo "=== TEST COHORTE CJ 2021 (CORRIGE) ===\n\n";

// Tester la nouvelle requête
echo "Cohorte 2021 - etudiants entres en BUT1 en 2021:\n";
$data2021 = $dao->getCohorteParAnneeEtFormation(2021, 'CJ', 2021);
echo "  2021: " . count($data2021) . " etudiants\n";

$data2022 = $dao->getCohorteParAnneeEtFormation(2022, 'CJ', 2021);
echo "  2022: " . count($data2022) . " etudiants (meme cohorte)\n";

$data2023 = $dao->getCohorteParAnneeEtFormation(2023, 'CJ', 2021);
echo "  2023: " . count($data2023) . " etudiants (meme cohorte)\n";

// Compter les étudiants uniques
$allIds = [];
foreach ([$data2021, $data2022, $data2023] as $data) {
    foreach ($data as $row) {
        $allIds[$row['etudid']] = true;
    }
}
echo "\nTotal etudiants UNIQUES dans la cohorte 2021: " . count($allIds) . "\n";

// Détail par niveau
echo "\nDetail par niveau:\n";
foreach (['2021' => $data2021, '2022' => $data2022, '2023' => $data2023] as $year => $data) {
    $byOrdre = [];
    foreach ($data as $row) {
        $ordre = $row['ordre'];
        if (!isset($byOrdre[$ordre])) $byOrdre[$ordre] = 0;
        $byOrdre[$ordre]++;
    }
    ksort($byOrdre);
    echo "  $year: ";
    foreach ($byOrdre as $ordre => $count) {
        echo "BUT$ordre=$count ";
    }
    echo "\n";
}
