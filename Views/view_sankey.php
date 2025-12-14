<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Mnemosyne — Diagramme Sankey</title>

    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="h-screen overflow-hidden bg-[#0A1E2F] flex flex-col">

    <!-- ============================
         HEADER : Logo + Slogan
    ============================= -->
    <header class="relative flex flex-col items-center justify-center gap-4 py-8">

        <!-- Bouton retour -->
        <a href="index.php?action=home"
           class="absolute left-8 top-8">
            <img src="img/Retour.png"
                 alt="Retour"
                 class="w-10 h-10 hover:scale-105 transition-transform">
        </a>

        <!-- Logo principal -->
        <img src="/Statics/img/logo.png"
             alt="Logo Mnemosyne"
             class="w-48 h-48 object-contain" />

        <!-- Slogan -->
        <h2 class="text-[#FBEDD3] text-2xl font-semibold">
            Gardez la mémoire, éclairez les parcours
        </h2>
    </header>

    <!-- ============================
         MAIN : Sankey + Stats
    ============================= -->
    <main class="flex flex-col flex-1 items-center justify-start gap-10 overflow-auto pb-10">

        <!-- ============================
             SECTION SANKEY
        ============================= -->
        <section id="Sankey" class="w-full flex justify-center">

            <!-- Conteneur du diagramme Sankey -->
            <div id="sankey_container"
                 class="w-4/5 h-[500px] bg-[#FFFFFF0A] rounded-2xl
                        backdrop-blur-md shadow-xl flex items-center justify-center">
                <!-- Sankey injecté en JS -->
            </div>

        </section>

        <!-- ============================
             SECTION STATS
        ============================= -->
        <section id="stats"
                 class="w-4/5 flex flex-col gap-4 text-[#FBEDD3]">

            <!-- Checkbox affichage stats -->
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox"
                       class="accent-[#E3BF81] w-4 h-4">
                Afficher les statistiques
            </label>

            <!-- Conteneur des stats -->
            <div class="container min-h-[150px]
                        border border-[#FBEDD340]
                        rounded-xl bg-[#FFFFFF0A]
                        backdrop-blur-md shadow-lg p-4">
                <!-- Stats injectées dynamiquement -->
            </div>

        </section>

    </main>

</body>
</html>
