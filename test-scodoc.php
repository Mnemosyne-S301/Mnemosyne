<?php

require_once __DIR__ . "/Services/ScodocDAO.php";

echo "=== TEST API SCODOC ===\n\n";

$baseUrl = readline("URL ScoDoc : ");
$token = readline("Token API : ");

$baseUrl = rtrim($baseUrl, "/");

try {
    $scodoc = new ScodocDAO($baseUrl, $token);

    echo "\nTest connexion API...\n";

    $departements = $scodoc->getDepartements();

    echo "Connexion réussie.\n";
    echo "Nombre de départements récupérés : " . count($departements) . "\n\n";

    foreach ($departements as $departement) {
        echo "- " . ($departement["acronym"] ?? $departement["acronyme"] ?? "Sans nom") . "\n";
    }

    echo "\nTest terminé avec succès.\n";

} catch (Exception $e) {
    echo "\nErreur pendant le test API :\n";
    echo $e->getMessage() . "\n";
}