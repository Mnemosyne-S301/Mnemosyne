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
    <nav class="bg-white p-2"><!-- Navbar -->
        <!-- Boutton Retour navbar -->
        <svg xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke-width="1.5" 
            stroke="currentColor" 
            class="w-8 h-8">
        <path stroke-linecap="round" stroke-linejoin="round"
                d="M2.25 12l8.954-8.955a1.125 1.125 0 011.59 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75V15a1.5 1.5 0 011.5-1.5h1.5a1.5 1.5 0 011.5 1.5v6h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
        </svg>

    </nav>

        <section id="Sankey" class="mt-6 flex justify-center"><!-- Diagramme Sankey -->
            <div id="sankey_container" class="flex justify-center mt-10">
                <!-- Conteneur du diagramme Sankey -->
                 <script src="Js/sankey.js"></script>
            </div>
        </section>
        <section id="stats" class="my-8 text-white"><!-- Affichage des stats -->
            <input  class="items-start" type="checkbox">  Afficher les stats <!-- Checkbox pour faire apparaitre les stats -->
            <div class="container border bg-gray"></div><!-- Conteneur des stats -->
        </section>
</body>
</html>