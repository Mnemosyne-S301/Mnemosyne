<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Sankey</title>
</head>
<body class="bg-[#0A1E2F]"><!-- Corps de la page -->
    <nav><!-- Navbar -->
        <!-- Boutton Retour navbar -->
        <a href="view_acceuil.php"><img src="img/Mnemosyne_logo.png" alt="Retour Acceuil"></a>
    </nav>

    <section class="flex min-h-screen justify-center items-center">
        <div id="Sankey" class="mt-6"><!-- Diagramme Sankey -->
            <div id="sankey_container" class="flex justify-center mt-10">
            </div>
        </div>
        <div id="stats"><!-- Affichage des stats -->
            <input  class="items-start" type="checkbox">Afficher les stats <!-- Checkbox pour faire apparaitre les stats -->
            <div class="container border bg-gray"></div><!-- Conteneur des stats -->
        </div>
    </section>
</body>
</html>