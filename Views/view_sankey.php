<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mnemosyne — Diagramme Sankey Cohorte BUT</title>
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
        <a href="index.php?controller=accueil" class="absolute left-8 top-8 group z-10">
            <img src="Content/image/logo.png" alt="Accueil" class="w-10 h-10 group-hover:scale-110 transition-transform">
        </a>
        <h1 class="text-3xl font-bold tracking-wide">Suivi de Cohorte BUT</h1>
    </header>

    <main class="flex-1 overflow-y-auto px-10 pb-10 space-y-8">
        
        <section class="w-full">
            <!-- Contrôle par niveau BUT avec boutons -->
            <div class="flex items-center justify-center gap-4 mb-6 flex-wrap">
                <span class="text-lg font-semibold">Filtrer par niveau :</span>
                <button id="btn-all" class="but-filter px-6 py-2 rounded-lg font-semibold transition-all bg-[#E3BF81] text-[#0A1E2F] border-2 border-[#E3BF81]" data-level="all">
                    Toutes les années
                </button>
                <button id="btn-but1" class="but-filter px-6 py-2 rounded-lg font-semibold transition-all bg-transparent text-[#60A5FA] border-2 border-[#60A5FA] hover:bg-[#60A5FA] hover:text-white" data-level="1">
                    BUT 1
                </button>
                <button id="btn-but2" class="but-filter px-6 py-2 rounded-lg font-semibold transition-all bg-transparent text-[#60A5FA] border-2 border-[#60A5FA] hover:bg-[#60A5FA] hover:text-white" data-level="2">
                    BUT 2
                </button>
                <button id="btn-but3" class="but-filter px-6 py-2 rounded-lg font-semibold transition-all bg-transparent text-[#60A5FA] border-2 border-[#60A5FA] hover:bg-[#60A5FA] hover:text-white" data-level="3">
                    BUT 3
                </button>
            </div>

            <div class="flex gap-8 items-start">
                <!-- Diagramme Sankey -->
                <div class="flex-1">
                    <div id="sankey-container" class="w-full h-[700px] bg-[#FFFFFF0A] rounded-2xl backdrop-blur-md shadow-2xl border border-white/10 relative">
                        <div id="loader" class="absolute inset-0 flex items-center justify-center z-10">
                            <p class="animate-pulse text-xl">Analyse des flux de cohorte...</p>
                        </div>
                        <div id="sankey-plot" class="w-full h-full"></div>
                    </div>
                </div>

                <!-- Légende à droite -->
                <div class="w-80 flex-shrink-0">
                    <div class="flex items-center gap-3 mb-6">
                        <input type="checkbox" id="toggle-legend" checked class="accent-[#E3BF81] w-5 h-5 cursor-pointer">
                        <label for="toggle-legend" class="text-lg font-medium cursor-pointer">Afficher la légende</label>
                    </div>

                    <div id="legend-container" class="flex flex-col gap-6 transition-all duration-500 overflow-y-auto max-h-[700px] pr-2">
                        <!-- Origine des étudiants -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3">Origine des étudiants</h3>
                            <div class="flex flex-col gap-2 text-sm">
                                <div class="bg-blue-500/10 border border-blue-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-blue-400">Parcoursup:</span> Admission via Parcoursup
                                </div>
                                <div class="bg-purple-500/10 border border-purple-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-purple-400">Hors Parcoursup:</span> Autres modes d'admission
                                </div>
                            </div>
                        </div>

                        <!-- Légende des décisions jury -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3">Codes des décisions jury</h3>
                            <div class="flex flex-col gap-2 text-sm">
                                <div class="bg-green-500/10 border border-green-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-green-400">ADM:</span> Admis
                                </div>
                                <div class="bg-green-500/10 border border-green-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-green-400">PASD:</span> Passage avec dettes
                                </div>
                                <div class="bg-green-500/10 border border-green-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-green-400">ADSUP:</span> Admis supérieur
                                </div>
                                <div class="bg-green-500/10 border border-green-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-green-400">CMP:</span> Compensé
                                </div>
                                <div class="bg-orange-500/10 border border-orange-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-orange-400">AJ:</span> Ajourné
                                </div>
                                <div class="bg-orange-500/10 border border-orange-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-orange-400">RED:</span> Redoublement
                                </div>
                                <div class="bg-orange-500/10 border border-orange-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-orange-400">ADJ:</span> Ajourné avec jury
                                </div>
                                <div class="bg-red-500/10 border border-red-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-red-400">NAR:</span> Non autorisé à redoubler
                                </div>
                                <div class="bg-red-500/10 border border-red-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-red-400">DEF:</span> Défaillant
                                </div>
                                <div class="bg-red-500/10 border border-red-500/30 p-2 rounded-lg">
                                    <span class="font-bold text-red-400">DEM:</span> Démission
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Configuration des données -->
    <script>
        // Les données sont passées directement par le contrôleur
        window.SANKEY_DATA = {
            data2021: <?php echo isset($data2021) ? json_encode($data2021) : '[]'; ?>,
            data2022: <?php echo isset($data2022) ? json_encode($data2022) : '[]'; ?>,
            data2023: <?php echo isset($data2023) ? json_encode($data2023) : '[]'; ?>
        };
        
        console.log('Données chargées depuis le contrôleur');
        console.log('2021:', window.SANKEY_DATA.data2021.length, 'étudiants');
        console.log('2022:', window.SANKEY_DATA.data2022.length, 'étudiants');
        console.log('2023:', window.SANKEY_DATA.data2023.length, 'étudiants');
    </script>
    
    <!-- Charger le fichier JavaScript externe -->
    <script src="/Content/script/sankey-logic.js"></script>
</body>
</html>
