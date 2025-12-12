<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <title>Sankey</title>
</head>
<body class="bg-[#0A1E2F]"><!-- Corps de la page -->
    <nav>
        <div class="flex items-center justify-between p-4 bg-[#0E2233] shadow-lg">
            <a href="/" class="text-white text-xl font-bold flex items-center">
                <i class="fas fa-home mr-2"></i> Accueil
            </a>
        </div>
    </nav>

        <section id="Sankey" class="mt-6 flex justify-center"><!-- Diagramme Sankey -->
            <div id='sankey-annee-unique' class="flex justify-center mt-10">
                <!-- Conteneur du diagramme Sankey -->
            </div>
        </section>
        <section id="stats" class="m-12 text-white"><!-- Affichage des stats -->
            <input  class="items-start" type="checkbox">  Afficher les stats <!-- Checkbox pour faire apparaitre les stats -->
            <div class="container border bg-gray"></div><!-- Conteneur des stats -->
        </section>
</body>
<script src="../Js/Sankey.js"></script>
</html>