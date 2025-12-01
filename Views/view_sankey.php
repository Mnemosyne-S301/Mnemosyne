<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Sankey</title>
</head>
<body>
    <nav><!-- Navbar -->
        <a href="index.php?action=home"><img src="img/Retour.png"></a><!-- Boutton Retour navbar -->
    </nav>
    <section id="Sankey"><!-- Diagramme Sankey -->
        <div id="sankey_container" class="flex justify-center mt-10">
        </div>
    </section>
    <section id="stats"><!-- Affichage des stats -->
        <input  class="items-start" type="checkbox">Afficher les stats <!-- Checkbox pour faire apparaitre les stats -->
        <div class="container border bg-gray"></div><!-- Conteneur des stats -->
    </section>
</body>
</html>