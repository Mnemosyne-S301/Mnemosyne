<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mnemosyne – Diagramme Sankey Cohorte BUT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        ::-webkit-scrollbar-thumb { background: #E3BF81; border-radius: 10px; }
    </style>
</head>

<body class="h-screen bg-[#0A1E2F] flex flex-col overflow-hidden text-[#FBEDD3]">

    <header class="relative flex flex-col items-center justify-center gap-2 py-6">
        <a href="index.php?action=home" class="absolute left-8 top-8 group">
            <img src="img/Retour.png" alt="Retour" class="w-10 h-10 group-hover:scale-110 transition-transform">
        </a>
        <h1 class="text-3xl font-bold tracking-wide"><?= htmlspecialchars($title ?? 'Suivi de Cohorte') ?></h1>
    </header>

    <main class="flex-1 overflow-y-auto px-10 pb-10 space-y-8">
        
        <section class="w-full">
            <div id="sankey-container" class="w-full h-[700px] bg-[#FFFFFF0A] rounded-2xl backdrop-blur-md shadow-2xl border border-white/10 relative">
                <div id="loader" class="absolute inset-0 flex items-center justify-center">
                    <p class="animate-pulse text-xl">Analyse des flux de cohorte...</p>
                </div>
                <div id="sankey-plot" class="w-full h-full"></div>
            </div>
        </section>

        <section class="w-full max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-4">
                <input type="checkbox" id="toggle-stats" checked class="accent-[#E3BF81] w-5 h-5 cursor-pointer">
                <label for="toggle-stats" class="text-lg font-medium cursor-pointer">Afficher les indicateurs clés</label>
            </div>

            <div id="stats-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 transition-all duration-500">
                <!-- Les statistiques seront ajoutées dynamiquement -->
            </div>
        </section>

        <section class="w-full max-w-7xl mx-auto">
            <h3 class="text-xl font-semibold mb-4">- Origine des étudiants - </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mb-6">
                <div class="bg-blue-500/10 border border-blue-500/30 p-3 rounded-lg">
                    <span class="font-bold text-blue-400">Parcoursup:</span> Admission via Parcoursup
                </div>
                <div class="bg-purple-500/10 border border-purple-500/30 p-3 rounded-lg">
                    <span class="font-bold text-purple-400">Hors Parcoursup:</span> Autres modes d'admission
                </div>
            </div>

            <h3 class="text-xl font-semibold mb-4">- Légende des décisions jury - </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                <div class="bg-green-500/10 border border-green-500/30 p-3 rounded-lg">
                    <span class="font-bold text-green-400">ADM:</span> Admis - Passage validé
                </div>
                <div class="bg-green-500/10 border border-green-500/30 p-3 rounded-lg">
                    <span class="font-bold text-green-400">PASD:</span> Passage avec dettes
                </div>
                <div class="bg-green-500/10 border border-green-500/30 p-3 rounded-lg">
                    <span class="font-bold text-green-400">ADSUP:</span> Admis supérieur
                </div>
                <div class="bg-green-500/10 border border-green-500/30 p-3 rounded-lg">
                    <span class="font-bold text-green-400">CMP:</span> Compensé
                </div>
                <div class="bg-orange-500/10 border border-orange-500/30 p-3 rounded-lg">
                    <span class="font-bold text-orange-400">AJ:</span> Ajourné - Redoublement
                </div>
                <div class="bg-orange-500/10 border border-orange-500/30 p-3 rounded-lg">
                    <span class="font-bold text-orange-400">RED:</span> Redoublement autorisé
                </div>
                <div class="bg-orange-500/10 border border-orange-500/30 p-3 rounded-lg">
                    <span class="font-bold text-orange-400">ADJ:</span> Ajourné avec possibilité jury
                </div>
                <div class="bg-red-500/10 border border-red-500/30 p-3 rounded-lg">
                    <span class="font-bold text-red-400">NAR:</span> Non autorisé à redoubler (exclusion)
                </div>
                <div class="bg-red-500/10 border border-red-500/30 p-3 rounded-lg">
                    <span class="font-bold text-red-400">DEF:</span> Défaillant (absent examens)
                </div>
                <div class="bg-red-500/10 border border-red-500/30 p-3 rounded-lg">
                    <span class="font-bold text-red-400">DEM:</span> Démission volontaire
                </div>
            </div>
        </section>
    </main>

    <!-- Configuration passée du PHP au JS -->
    <script>
        window.SANKEY_CONFIG = [
            '/Database/example/json/testdata/test_promo_2021_v2.json',
            '/Database/example/json/testdata/test_promo_2022_v2.json',
            '/Database/example/json/testdata/test_promo_2023_v2.json'
        ];
    </script>
    
    <!-- Import du fichier JS avec la logique -->
    <script src="Content/js/sankey-logic.js"></script>
</body>
</html>