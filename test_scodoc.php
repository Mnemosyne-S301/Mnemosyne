<?php

require_once __DIR__ . "/Models/ScodocDAO.php";

$apiUrl = "https://scodoc.univ-paris13.fr/ScoDoc/api";
$token = readline("Token ScoDoc : ");

$dao = new ScodocDAO($apiUrl, $token);
$data = $dao->recupererDonneesCohortes([2021, 2022, 2023, 2024]);

echo "Départements : " . count($data['departements']) . PHP_EOL;
echo "Formations : " . count($data['formations']) . PHP_EOL;
echo "Formations BUT : " . count($data['formations_but']) . PHP_EOL;
echo "Référentiels : " . count($data['referentiels_competences']) . PHP_EOL;

$totalSemestresBut = 0;
foreach ($data['formsemestres_but'] as $semestres) {
    $totalSemestresBut += count($semestres);
}

$totalDecisions = 0;
foreach ($data['decisions_jury'] as $semestres) {
    foreach ($semestres as $decisions) {
        $totalDecisions += count($decisions);
    }
}

echo "Semestres BUT : " . $totalSemestresBut . PHP_EOL;
echo "Décisions jury : " . $totalDecisions . PHP_EOL;

if (!empty($data['erreurs'])) {
    echo "Erreurs :" . PHP_EOL;
    foreach ($data['erreurs'] as $erreur) {
        echo "- " . $erreur . PHP_EOL;
    }
} else {
    echo "Récupération ScoDoc OK." . PHP_EOL;
}