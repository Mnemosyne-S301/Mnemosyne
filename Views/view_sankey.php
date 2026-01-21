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
        <a href="/accueil/default" class="absolute left-8 top-8 group z-10">
            <img src="/Content/image/logo.png" alt="Accueil" class="w-10 h-10 group-hover:scale-110 transition-transform">
        </a>
        <h1 class="text-3xl font-bold tracking-wide">Suivi de Cohorte BUT</h1>
    </header>

    <main class="flex-1 overflow-y-auto px-10 pb-10 space-y-8">
        
        <section class="w-full">
            <!-- Sélecteurs de formation et source de données -->
            <div class="flex items-center justify-center gap-6 mb-4 flex-wrap">
                <!-- Sélecteur de formation -->
                <div class="flex items-center gap-2">
                    <label for="formation-select" class="text-sm font-medium">Formation :</label>
                    <select id="formation-select" class="bg-[#0A1E2F] border border-[#E3BF81] text-[#FBEDD3] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#E3BF81]">
                        <option value="INFO" <?= ($formation ?? '') === 'INFO' ? 'selected' : '' ?>>BUT Informatique</option>
                        <option value="GEA" <?= ($formation ?? 'GEA') === 'GEA' ? 'selected' : '' ?>>BUT GEA</option>
                        <option value="RT" <?= ($formation ?? '') === 'RT' ? 'selected' : '' ?>>BUT R&T</option>
                        <option value="GEII" <?= ($formation ?? '') === 'GEII' ? 'selected' : '' ?>>BUT GEII</option>
                        <option value="CJ" <?= ($formation ?? '') === 'CJ' ? 'selected' : '' ?>>BUT Carrières Juridiques</option>
                        <option value="SD" <?= ($formation ?? '') === 'SD' ? 'selected' : '' ?>>BUT Science des Données</option>
                    </select>
                </div>
                
                <!-- Sélecteur d'année de départ -->
                <div class="flex items-center gap-2">
                    <label for="annee-select" class="text-sm font-medium">Cohorte :</label>
                    <select id="annee-select" class="bg-[#0A1E2F] border border-[#E3BF81] text-[#FBEDD3] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#E3BF81]">
                        <?php for ($y = 2021; $y <= 2024; $y++): ?>
                            <option value="<?= $y ?>" <?= ($anneeDepart ?? 2021) == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <!-- Sélecteur de source de données -->
                <div class="flex items-center gap-2">
                    <label for="source-select" class="text-sm font-medium">Source :</label>
                    <select id="source-select" class="bg-[#0A1E2F] border border-[#E3BF81] text-[#FBEDD3] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#E3BF81]">
                        <option value="json" <?= ($source ?? 'json') === 'json' ? 'selected' : '' ?>>Fichiers JSON Test</option>
                        <option value="testdata" <?= ($source ?? '') === 'testdata' ? 'selected' : '' ?>>Fichiers JSON Testdata</option>
                        <option value="bdd" <?= ($source ?? '') === 'bdd' ? 'selected' : '' ?>>Base de données</option>
                    </select>
                </div>
                
                <!-- Bouton recharger -->
                <button id="reload-data" class="px-4 py-2 bg-[#E3BF81] text-[#0A1E2F] rounded-lg font-semibold hover:bg-[#d4a85c] transition-colors">
                    Recharger
                </button>
            </div>
            
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
                    <!-- Section stats Sankey -->
                    <div id="sankey-stats" class="mt-8 p-6 bg-[#1A2B3C] rounded-xl shadow-lg border border-[#E3BF81]/20 flex flex-col gap-2 text-lg">
                        <div class="font-bold text-[#E3BF81] text-xl mb-2">Statistiques de la cohorte</div>
                        <div id="sankey-stats-content" class="flex flex-wrap gap-8"></div>
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
        // Configuration initiale depuis PHP
        window.SANKEY_CONFIG = {
            formation: '<?php echo $formation ?? 'GEA'; ?>',
            anneeDepart: <?php echo $anneeDepart ?? 2021; ?>,
            source: '<?php echo $source ?? 'json'; ?>'
        };
        
        // Variable globale pour les données (sera remplie par l'API)
        window.SANKEY_DATA = null;
        window.SANKEY_SOURCE = window.SANKEY_CONFIG.source;

        /**
         * Charge les données depuis l'API
         */
        async function loadSankeyData(formation, anneeDepart, source) {
            // Réinitialiser le diagramme et les filtres
            const plotDiv = document.getElementById('sankey-plot');
            if (plotDiv) {
                // Purge Plotly si déjà affiché
                if (window.Plotly && plotDiv.data) {
                    window.Plotly.purge(plotDiv);
                }
                plotDiv.innerHTML = '';
            }
            // Réinitialiser les boutons filtres (mettre "Toutes les années" actif)
            document.querySelectorAll('.but-filter').forEach(btn => {
                btn.classList.remove('bg-[#60A5FA]', 'bg-[#93C5FD]', 'bg-[#DBEAFE]', 'bg-[#E3BF81]', 'text-white', 'text-[#0A1E2F]');
                btn.classList.add('bg-transparent');
            });
            document.getElementById('btn-all')?.classList.add('bg-[#E3BF81]', 'text-[#0A1E2F]');
            // Afficher le loader
            if (plotDiv) {
                plotDiv.innerHTML = '<div class="flex items-center justify-center h-full"><span class="text-xl">Chargement des données...</span></div>';
            }

            try {
                const url = `index.php?controller=api&action=sankey&formation=${formation}&anneeDepart=${anneeDepart}&source=${source}`;
                console.log('Appel API:', url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                // Transformer les données pour le format attendu par sankey-logic.js
                const sankeyData = {
                    annee_depart: data.annee_depart,
                    annees: data.annees
                };
                
                // Ajouter les données par année (data2021, data2022, etc.)
                data.annees.forEach(annee => {
                    sankeyData['data' + annee] = data.data[annee] || [];
                });

                window.SANKEY_DATA = sankeyData;
                window.SANKEY_SOURCE = source;

                console.log('Données chargées depuis:', source === 'json' ? 'Fichiers JSON' : 'Base de données');
                console.log('Année de départ:', sankeyData.annee_depart);
                sankeyData.annees.forEach((annee, i) => {
                    const dataKey = 'data' + annee;
                    console.log(`Année ${i + 1} (${annee}):`, sankeyData[dataKey]?.length || 0, 'étudiants');
                });

                // Masquer le loader
                const loader = document.getElementById('loader');
                if (loader) loader.style.display = 'none';
                
                // Initialiser le diagramme Sankey
                if (typeof SankeyCohort !== 'undefined') {
                    SankeyCohort.init();
                        // Charger et afficher les statistiques de cohorte (depuis l'API stats)
                        try {
                            const statsUrl = `index.php?controller=api&action=stats&formation=${formation}&anneeDepart=${anneeDepart}`;
                            const statsResp = await fetch(statsUrl);
                            if (!statsResp.ok) throw new Error(`Erreur HTTP stats: ${statsResp.status}`);
                            const stats = await statsResp.json();
                            if (stats.error) throw new Error(stats.error);
                            // Affichage dynamique dans la section stats
                            const statsDiv = document.getElementById('sankey-stats-content');
                            if (statsDiv) {
                                // Construire le HTML des statistiques enrichies
                                let html = `
                                    <div class=\"flex flex-col items-center\">
                                        <span class=\"text-3xl font-bold text-[#E3BF81]\">${stats.effectif}</span>
                                        <span class=\"text-base text-[#FBEDD3]\">Effectif total</span>
                                    </div>
                                    <div class=\"flex flex-col items-center\">
                                        <span class=\"text-3xl font-bold text-green-400\">${stats.diplomes}</span>
                                        <span class=\"text-base text-[#FBEDD3]\">Diplômés</span>
                                    </div>
                                    <div class=\"flex flex-col items-center\">
                                        <span class=\"text-3xl font-bold text-blue-400\">${stats.encours}</span>
                                        <span class=\"text-base text-[#FBEDD3]\">En cours</span>
                                    </div>
                                    <div class=\"flex flex-col items-center\">
                                        <span class=\"text-3xl font-bold text-red-400\">${stats.abandons}</span>
                                        <span class=\"text-base text-[#FBEDD3]\">Abandons</span>
                                    </div>
                                    <div class=\"flex flex-col items-center\">
                                        <span class=\"text-3xl font-bold text-purple-400\">${stats.tauxReussite}%</span>
                                        <span class=\"text-base text-[#FBEDD3]\">Taux diplômés</span>
                                    </div>
                                `;
                                
                                // Ajouter les stats de la BDD stats si disponibles
                                if (stats.tauxValidation6UE !== null) {
                                    html += `
                                        <div class=\"flex flex-col items-center\">
                                            <span class=\"text-3xl font-bold text-cyan-400\">${stats.tauxValidation6UE}%</span>
                                            <span class=\"text-base text-[#FBEDD3]\">6 UE validées</span>
                                        </div>
                                    `;
                                }
                                if (stats.moyenneUE !== null) {
                                    html += `
                                        <div class=\"flex flex-col items-center\">
                                            <span class=\"text-3xl font-bold text-amber-400\">${stats.moyenneUE}</span>
                                            <span class=\"text-base text-[#FBEDD3]\">Moy. UE validées</span>
                                        </div>
                                    `;
                                }
                                
                                statsDiv.innerHTML = html;
                            }
                        } catch (err) {
                            const statsDiv = document.getElementById('sankey-stats-content');
                            if (statsDiv) {
                                statsDiv.innerHTML = `<span class='text-red-400'>Erreur stats: ${err.message}</span>`;
                            }
                        }
                }

            } catch (error) {
                console.error('Erreur lors du chargement:', error);
                // Masquer le loader et afficher l'erreur
                const loader = document.getElementById('loader');
                if (loader) loader.style.display = 'none';
                const plotDiv = document.getElementById('sankey-plot');
                if (plotDiv) {
                    plotDiv.innerHTML = `<div class="flex items-center justify-center h-full text-red-400"><span class="text-xl">Erreur: ${error.message}</span></div>`;
                }
            }
        }

        // Gestionnaire pour le bouton de rechargement (sans recharger la page)
        document.getElementById('reload-data')?.addEventListener('click', async function() {
            const formation = document.getElementById('formation-select').value;
            const annee = document.getElementById('annee-select').value;
            const source = document.getElementById('source-select').value;
            
            // Mettre à jour l'URL sans recharger
            const url = new URL(window.location.href);
            url.searchParams.set('formation', formation);
            url.searchParams.set('anneeDepart', annee);
            url.searchParams.set('source', source);
            history.pushState({}, '', url);
            
            // Charger les nouvelles données via l'API
            await loadSankeyData(formation, annee, source);
        });

        // Ajout : recharger automatiquement quand on change un sélecteur
        ['formation-select', 'annee-select', 'source-select'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', async function() {
                const formation = document.getElementById('formation-select').value;
                const annee = document.getElementById('annee-select').value;
                const source = document.getElementById('source-select').value;
                // Mettre à jour l'URL sans recharger
                const url = new URL(window.location.href);
                url.searchParams.set('formation', formation);
                url.searchParams.set('anneeDepart', annee);
                url.searchParams.set('source', source);
                history.pushState({}, '', url);
                await loadSankeyData(formation, annee, source);
            });
        });

        // Chargement initial des données au démarrage
        document.addEventListener('DOMContentLoaded', function() {
            const formation = document.getElementById('formation-select').value;
            const annee = document.getElementById('annee-select').value;
            const source = document.getElementById('source-select').value;
            loadSankeyData(formation, annee, source);
        });
    </script>
   
        
   

    <script>
    // charger les règles admin sauvegardées
    try {
        window.SANKEY_REGLES = JSON.parse(localStorage.getItem("SANKEY_REGLES") || "null");
    } catch {
        window.SANKEY_REGLES = null;
    }
</script>

    <!-- Charger le fichier JavaScript externe -->
    <script src="/Content/script/sankey-logic.js"></script>
</body>
</html>
