<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mnemosyne — Diagramme Sankey Cohorte BUT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <!-- Chart.js pour visualisations stats -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        ::-webkit-scrollbar-thumb { background: #E3BF81; border-radius: 10px; }
    </style>
</head>

<body class="h-screen bg-[#0A1E2F] flex flex-col overflow-hidden text-[#FBEDD3]">

    <header class="sticky top-0 z-50 bg-[#0A1E2F] relative flex flex-col items-center justify-center gap-2 py-6 border-b border-[#E3BF81]/10">
        <a href="/accueil/default" class="absolute left-8 top-8 group z-10">
            <img src="/Content/image/logo.png" alt="Accueil" class="w-10 h-10 group-hover:scale-110 transition-transform">
        </a>
        <h1 class="text-3xl font-bold tracking-wide">Suivi de Cohorte BUT</h1>
    </header>

    <main class="flex-1 overflow-y-auto px-10 pb-10 space-y-8">
        
        <section class="w-full">
            <!-- Section Sankey + Configuration -->
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

                <!-- Colonne droite : Configuration et filtres -->
                <div class="w-72 flex-shrink-0">
                    <div class="bg-[#1A2B3C] rounded-xl p-5 border border-[#E3BF81]/20 shadow-lg">
                        <h3 class="text-lg font-semibold text-[#E3BF81] mb-4">Configuration</h3>
                        
                        <!-- Sélecteur de formation -->
                        <div class="flex flex-col gap-2 mb-4">
                            <label for="formation-select" class="text-sm font-medium">Formation :</label>
                            <select id="formation-select" class="bg-[#0A1E2F] border border-[#E3BF81] text-[#FBEDD3] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#E3BF81]">
                                <option value="INFO" <?= ($formation ?? 'INFO') === 'INFO' ? 'selected' : '' ?>>BUT Informatique</option>
                                <option value="GEA" <?= ($formation ?? '') === 'GEA' ? 'selected' : '' ?>>BUT GEA</option>
                                <option value="RT" <?= ($formation ?? '') === 'RT' ? 'selected' : '' ?>>BUT R&T</option>
                                <option value="GEII" <?= ($formation ?? '') === 'GEII' ? 'selected' : '' ?>>BUT GEII</option>
                                <option value="CJ" <?= ($formation ?? '') === 'CJ' ? 'selected' : '' ?>>BUT Carrières Juridiques</option>
                                <option value="SD" <?= ($formation ?? '') === 'SD' ? 'selected' : '' ?>>BUT Science des Données</option>
                            </select>
                        </div>
                        
                        <!-- Sélecteur d'année de départ -->
                        <div class="flex flex-col gap-2 mb-4">
                            <label for="annee-select" class="text-sm font-medium">Cohorte :</label>
                            <select id="annee-select" class="bg-[#0A1E2F] border border-[#E3BF81] text-[#FBEDD3] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#E3BF81]">
                                <?php for ($y = 2021; $y <= 2024; $y++): ?>
                                    <option value="<?= $y ?>" <?= ($anneeDepart ?? 2021) == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <!-- Sélecteur de modalité (FI / Apprentissage) -->
                        <div class="flex flex-col gap-2 mb-4">
                            <label for="modalite-select" class="text-sm font-medium">Modalité :</label>
                            <select id="modalite-select" class="bg-[#0A1E2F] border border-[#E3BF81] text-[#FBEDD3] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#E3BF81]">
                                <option value="FI" selected>Formation Initiale</option>
                                <option value="FAP">Apprentissage</option>
                            </select>
                        </div>
                        
                        <!-- Sélecteur de source de données -->
                        <div class="flex flex-col gap-2 mb-5">
                            <label for="source-select" class="text-sm font-medium">Source :</label>
                            <select id="source-select" class="bg-[#0A1E2F] border border-[#E3BF81] text-[#FBEDD3] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#E3BF81]">
                                <option value="json" <?= ($source ?? '') === 'json' ? 'selected' : '' ?>>Fichiers JSON Test</option>
                                <option value="testdata" <?= ($source ?? 'testdata') === 'testdata' ? 'selected' : '' ?>>Fichiers JSON Testdata</option>
                                <option value="bdd" <?= ($source ?? '') === 'bdd' ? 'selected' : '' ?>>Base de données</option>
                            </select>
                        </div>
                        
                        <!-- Bouton recharger -->
                        <button id="reload-data" class="w-full px-4 py-2 bg-[#E3BF81] text-[#0A1E2F] rounded-lg font-semibold hover:bg-[#d4a85c] transition-colors mb-6">
                            Recharger
                        </button>

                        <!-- Séparateur -->
                        <div class="border-t border-[#E3BF81]/30 my-4"></div>

                        <!-- Contrôle par niveau BUT avec boutons -->
                        <div class="flex flex-col gap-3">
                            <span class="text-sm font-semibold text-[#E3BF81]">Filtrer par niveau :</span>
                            <div class="grid grid-cols-2 gap-2">
                                <button id="btn-all" class="but-filter col-span-2 px-4 py-2 rounded-lg font-semibold transition-all bg-[#E3BF81] text-[#0A1E2F] border-2 border-[#E3BF81] text-sm" data-level="all">
                                    Toutes les années
                                </button>
                                <button id="btn-but1" class="but-filter px-4 py-2 rounded-lg font-semibold transition-all bg-transparent text-[#60A5FA] border-2 border-[#60A5FA] hover:bg-[#60A5FA] hover:text-white text-sm" data-level="1">
                                    BUT 1
                                </button>
                                <button id="btn-but2" class="but-filter px-4 py-2 rounded-lg font-semibold transition-all bg-transparent text-[#60A5FA] border-2 border-[#60A5FA] hover:bg-[#60A5FA] hover:text-white text-sm" data-level="2">
                                    BUT 2
                                </button>
                                <button id="btn-but3" class="but-filter col-span-2 px-4 py-2 rounded-lg font-semibold transition-all bg-transparent text-[#60A5FA] border-2 border-[#60A5FA] hover:bg-[#60A5FA] hover:text-white text-sm" data-level="3">
                                    BUT 3
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Stats + Légende (en dessous du Sankey) -->
            <div class="flex gap-8 mt-8">
                <!-- Stats (s'étire pour remplir l'espace) -->
                <div id="sankey-stats" class="flex-1 p-6 bg-[#1A2B3C] rounded-xl shadow-lg border border-[#E3BF81]/20 flex flex-col gap-4 text-lg">
                    <div class="font-bold text-[#E3BF81] text-xl mb-2">Statistiques de la cohorte</div>
                    
                    <!-- KPIs principaux -->
                    <div id="sankey-stats-content" class="flex flex-wrap gap-6 justify-center"></div>
                    
                    <!-- Graphiques de statistiques -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                        
                        <!-- Graphique 1 : Répartition UE validées -->
                        <div class="bg-[#0A1E2F] p-4 rounded-xl border border-[#E3BF81]/10">
                            <h5 class="text-[#FBEDD3] font-semibold mb-2 text-sm">Répartition des UE validées</h5>
                            <div class="h-40">
                                <canvas id="chartUE"></canvas>
                            </div>
                        </div>
                        
                        <!-- Graphique 2 : Répartition des statuts (Doughnut) -->
                        <div class="bg-[#0A1E2F] p-4 rounded-xl border border-[#E3BF81]/10">
                            <h5 class="text-[#FBEDD3] font-semibold mb-2 text-sm">Répartition des statuts</h5>
                            <div class="h-40 flex items-center justify-center">
                                <canvas id="chartStatuts"></canvas>
                            </div>
                        </div>
                        
                        <!-- Graphique 3 : Nuage de points - Comparaison formations -->
                        <div class="bg-[#0A1E2F] p-4 rounded-xl border border-[#E3BF81]/10">
                            <h5 class="text-[#FBEDD3] font-semibold mb-2 text-sm">Taux réussite vs Effectif</h5>
                            <div class="h-40">
                                <canvas id="chartScatter"></canvas>
                            </div>
                        </div>
                        
                        <!-- Graphique 4 : Evolution effectifs BUT1->BUT3 -->
                        <div class="bg-[#0A1E2F] p-4 rounded-xl border border-[#E3BF81]/10">
                            <h5 class="text-[#FBEDD3] font-semibold mb-2 text-sm">Évolution effectifs cohorte</h5>
                            <div class="h-40">
                                <canvas id="chartEvolution"></canvas>
                            </div>
                        </div>
                        
                        <!-- Graphique 5 : Radar comparatif formations -->
                        <div class="bg-[#0A1E2F] p-4 rounded-xl border border-[#E3BF81]/10 md:col-span-2">
                            <h5 class="text-[#FBEDD3] font-semibold mb-2 text-sm">Comparaison formations (taux réussite)</h5>
                            <div class="h-40">
                                <canvas id="chartRadar"></canvas>
                            </div>
                        </div>
                        
                    </div>
                </div>

                <!-- Légende (fixe à droite, même largeur que la config) -->
                <div class="w-72 flex-shrink-0">
                    <div class="flex items-center gap-3 mb-4">
                        <input type="checkbox" id="toggle-legend" checked class="accent-[#E3BF81] w-5 h-5 cursor-pointer">
                        <label for="toggle-legend" class="text-lg font-medium cursor-pointer">Afficher la légende</label>
                    </div>

                    <div id="legend-container" class="flex flex-col gap-4 transition-all duration-500 bg-[#1A2B3C] rounded-xl p-4 border border-[#E3BF81]/20">
                        <!-- Origine des étudiants -->
                        <div>
                            <h3 class="text-base font-semibold mb-2">Origine des étudiants</h3>
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
                            <h3 class="text-base font-semibold mb-2">Codes des décisions jury</h3>
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
            formation: '<?php echo $formation ?? 'INFO'; ?>',
            anneeDepart: <?php echo $anneeDepart ?? 2021; ?>,
            source: '<?php echo $source ?? 'json'; ?>',
            modalite: 'FI'
        };
        
        // Variable globale pour les données (sera remplie par l'API)
        window.SANKEY_DATA = null;
        window.SANKEY_SOURCE = window.SANKEY_CONFIG.source;

        /**
         * Charge les données depuis l'API
         */
        async function loadSankeyData(formation, anneeDepart, source, modalite = 'FI') {
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
                plotDiv.innerHTML = '<div id="loader" class="flex items-center justify-center h-full"><span class="text-xl">Chargement des données...</span></div>';
            }

            try {
                const url = `index.php?controller=api&action=sankey&formation=${formation}&anneeDepart=${anneeDepart}&source=${source}&modalite=${modalite}`;
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

                // Masquer le loader et effacer le message de chargement
                const loader = document.getElementById('loader');
                if (loader) loader.style.display = 'none';
                
                // Effacer le message "Chargement des données..." avant d'afficher le diagramme
                if (plotDiv) plotDiv.innerHTML = '';
                
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
                                // Construire le HTML des KPIs
                                let html = `
                                    <div class=\"flex flex-col items-center bg-[#0A1E2F] px-4 py-3 rounded-lg border border-[#E3BF81]/20\">
                                        <span class=\"text-3xl font-bold text-[#E3BF81]\">${stats.effectif}</span>
                                        <span class=\"text-xs text-[#FBEDD3]/70\">Effectif total</span>
                                    </div>
                                    <div class=\"flex flex-col items-center bg-[#0A1E2F] px-4 py-3 rounded-lg border border-green-500/20\">
                                        <span class=\"text-3xl font-bold text-green-400\">${stats.diplomes}</span>
                                        <span class=\"text-xs text-[#FBEDD3]/70\">Diplômés</span>
                                    </div>
                                    <div class=\"flex flex-col items-center bg-[#0A1E2F] px-4 py-3 rounded-lg border border-blue-500/20\">
                                        <span class=\"text-3xl font-bold text-blue-400\">${stats.encours}</span>
                                        <span class=\"text-xs text-[#FBEDD3]/70\">En cours</span>
                                    </div>
                                    <div class=\"flex flex-col items-center bg-[#0A1E2F] px-4 py-3 rounded-lg border border-red-500/20\">
                                        <span class=\"text-3xl font-bold text-red-400\">${stats.abandons}</span>
                                        <span class=\"text-xs text-[#FBEDD3]/70\">Abandons</span>
                                    </div>
                                    <div class=\"flex flex-col items-center bg-[#0A1E2F] px-4 py-3 rounded-lg border border-purple-500/20\">
                                        <span class=\"text-3xl font-bold text-purple-400\">${stats.tauxReussite}%</span>
                                        <span class=\"text-xs text-[#FBEDD3]/70\">Taux diplômés</span>
                                    </div>
                                    <div class=\"flex flex-col items-center bg-[#0A1E2F] px-4 py-3 rounded-lg border border-orange-500/20\">
                                        <span class=\"text-3xl font-bold text-orange-400\">${stats.tauxAbandon || 0}%</span>
                                        <span class=\"text-xs text-[#FBEDD3]/70\">Taux abandon</span>
                                    </div>
                                `;
                                
                                // Ajouter les stats de la BDD stats si disponibles
                                if (stats.tauxValidation6UE !== null) {
                                    html += `
                                        <div class=\"flex flex-col items-center bg-[#0A1E2F] px-4 py-3 rounded-lg border border-cyan-500/20\">
                                            <span class=\"text-3xl font-bold text-cyan-400\">${stats.tauxValidation6UE}%</span>
                                            <span class=\"text-xs text-[#FBEDD3]/70\">6 UE validées</span>
                                        </div>
                                    `;
                                }
                                if (stats.moyenneUE !== null) {
                                    html += `
                                        <div class=\"flex flex-col items-center bg-[#0A1E2F] px-4 py-3 rounded-lg border border-amber-500/20\">
                                            <span class=\"text-3xl font-bold text-amber-400\">${stats.moyenneUE}</span>
                                            <span class=\"text-xs text-[#FBEDD3]/70\">Moy. UE validées</span>
                                        </div>
                                    `;
                                }
                                
                                statsDiv.innerHTML = html;
                                
                                // Mettre à jour les graphiques Chart.js
                                updateStatsCharts(stats, formation, anneeDepart);
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
            const modalite = document.getElementById('modalite-select').value;
            
            // Mettre à jour l'URL sans recharger
            const url = new URL(window.location.href);
            url.searchParams.set('formation', formation);
            url.searchParams.set('anneeDepart', annee);
            url.searchParams.set('source', source);
            url.searchParams.set('modalite', modalite);
            history.pushState({}, '', url);
            
            // Charger les nouvelles données via l'API
            await loadSankeyData(formation, annee, source, modalite);
        });

        // Ajout : recharger automatiquement quand on change un sélecteur
        ['formation-select', 'annee-select', 'source-select', 'modalite-select'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', async function() {
                const formation = document.getElementById('formation-select').value;
                const annee = document.getElementById('annee-select').value;
                const source = document.getElementById('source-select').value;
                const modalite = document.getElementById('modalite-select').value;
                // Mettre à jour l'URL sans recharger
                const url = new URL(window.location.href);
                url.searchParams.set('formation', formation);
                url.searchParams.set('anneeDepart', annee);
                url.searchParams.set('source', source);
                url.searchParams.set('modalite', modalite);
                history.pushState({}, '', url);
                await loadSankeyData(formation, annee, source, modalite);
            });
        });

        // Chargement initial des données au démarrage
        document.addEventListener('DOMContentLoaded', function() {
            const formation = document.getElementById('formation-select').value;
            const annee = document.getElementById('annee-select').value;
            const source = document.getElementById('source-select').value;
            const modalite = document.getElementById('modalite-select').value;
            loadSankeyData(formation, annee, source, modalite);
        });
    </script>
   
        
   

    <script>
    // charger les règles admin sauvegardées
    try {
        window.SANKEY_REGLES = JSON.parse(localStorage.getItem("SANKEY_REGLES") || "null");
    } catch {
        window.SANKEY_REGLES = null;
    }
    
    // ===============================
    // Configuration des graphiques Chart.js
    // ===============================
    
    Chart.defaults.color = '#FBEDD3';
    Chart.defaults.borderColor = 'rgba(251, 237, 211, 0.1)';
    
    let chartUE, chartStatuts, chartScatter, chartEvolution, chartRadar;
    
    const chartColors = {
        primary: '#4ECDC4',
        secondary: '#FFE66D', 
        success: '#95E1A3',
        danger: '#FF6B6B',
        info: '#45B7D1',
        purple: '#A78BFA',
        orange: '#FB923C',
        cyan: '#22D3EE'
    };
    
    // Initialisation des graphiques au chargement
    function initStatsCharts() {
        // Graphique 1 : Barres UE validées
        const ctxUE = document.getElementById('chartUE');
        if (ctxUE) {
            chartUE = new Chart(ctxUE.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['0', '1', '2', '3', '4', '5', '6'],
                    datasets: [{
                        label: 'Étudiants',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        backgroundColor: [
                            chartColors.danger,
                            chartColors.orange,
                            chartColors.secondary,
                            chartColors.info,
                            chartColors.primary,
                            chartColors.purple,
                            chartColors.success
                        ],
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(251,237,211,0.1)' }, ticks: { font: { size: 9 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                    }
                }
            });
        }

        // Graphique 2 : Doughnut statuts
        const ctxStatuts = document.getElementById('chartStatuts');
        if (ctxStatuts) {
            chartStatuts = new Chart(ctxStatuts.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Diplômés', 'En cours', 'Abandons'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: [chartColors.success, chartColors.info, chartColors.danger],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '55%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { boxWidth: 10, padding: 6, font: { size: 9 } }
                        }
                    }
                }
            });
        }

        // Graphique 3 : Nuage de points
        const ctxScatter = document.getElementById('chartScatter');
        if (ctxScatter) {
            chartScatter = new Chart(ctxScatter.getContext('2d'), {
                type: 'scatter',
                data: {
                    datasets: [{
                        label: 'Formations',
                        data: [],
                        backgroundColor: chartColors.primary,
                        pointRadius: 8,
                        pointHoverRadius: 12
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.raw.label + ': ' + ctx.raw.y + '% réussite, ' + ctx.raw.x + ' étudiants'
                            }
                        }
                    },
                    scales: {
                        x: { 
                            title: { display: true, text: 'Effectif', color: '#FBEDD3', font: { size: 9 } },
                            grid: { color: 'rgba(251,237,211,0.1)' },
                            ticks: { font: { size: 8 } }
                        },
                        y: { 
                            title: { display: true, text: 'Réussite %', color: '#FBEDD3', font: { size: 9 } },
                            beginAtZero: true, max: 100,
                            grid: { color: 'rgba(251,237,211,0.1)' },
                            ticks: { font: { size: 8 } }
                        }
                    }
                }
            });
        }

        // Graphique 4 : Ligne évolution effectifs
        const ctxEvol = document.getElementById('chartEvolution');
        if (ctxEvol) {
            chartEvolution = new Chart(ctxEvol.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['BUT1', 'BUT2', 'BUT3'],
                    datasets: [{
                        label: 'Effectif',
                        data: [0, 0, 0],
                        borderColor: chartColors.primary,
                        backgroundColor: 'rgba(78, 205, 196, 0.2)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: chartColors.primary
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(251,237,211,0.1)' }, ticks: { font: { size: 9 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                    }
                }
            });
        }

        // Graphique 5 : Radar comparatif
        const ctxRadar = document.getElementById('chartRadar');
        if (ctxRadar) {
            chartRadar = new Chart(ctxRadar.getContext('2d'), {
                type: 'radar',
                data: {
                    labels: ['INFO', 'GEA', 'RT', 'GEII', 'CJ', 'SD'],
                    datasets: [{
                        label: 'Taux réussite',
                        data: [0, 0, 0, 0, 0, 0],
                        backgroundColor: 'rgba(78, 205, 196, 0.3)',
                        borderColor: chartColors.primary,
                        borderWidth: 2,
                        pointBackgroundColor: chartColors.primary
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { stepSize: 20, color: '#FBEDD3', font: { size: 8 } },
                            grid: { color: 'rgba(251,237,211,0.2)' },
                            angleLines: { color: 'rgba(251,237,211,0.2)' },
                            pointLabels: { color: '#FBEDD3', font: { size: 10 } }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    }
    
    // Mise à jour des graphiques avec les stats reçues
    async function updateStatsCharts(stats, formation, annee) {
        // Graphique UE
        if (chartUE && stats.repartitionUE) {
            const ueData = [
                stats.repartitionUE[0] || 0,
                stats.repartitionUE[1] || 0,
                stats.repartitionUE[2] || 0,
                stats.repartitionUE[3] || 0,
                stats.repartitionUE[4] || 0,
                stats.repartitionUE[5] || 0,
                stats.repartitionUE[6] || 0
            ];
            chartUE.data.datasets[0].data = ueData;
            chartUE.update();
        }

        // Graphique statuts
        if (chartStatuts) {
            chartStatuts.data.datasets[0].data = [
                stats.diplomes || 0,
                stats.encours || 0,
                stats.abandons || 0
            ];
            chartStatuts.update();
        }

        // Charger les stats multi-formations pour scatter et radar
        const formations = ['INFO', 'GEA', 'RT', 'GEII', 'CJ', 'SD'];
        const scatterData = [];
        const radarData = [];
        
        const formationColors = {
            'INFO': chartColors.primary,
            'GEA': chartColors.secondary,
            'RT': chartColors.success,
            'GEII': chartColors.danger,
            'CJ': chartColors.info,
            'SD': chartColors.purple
        };

        for (const form of formations) {
            try {
                const res = await fetch(`index.php?controller=api&action=stats&formation=${form}&anneeDepart=${annee}`);
                const data = await res.json();
                
                if (data.effectif && data.tauxReussite !== undefined) {
                    scatterData.push({
                        x: data.effectif,
                        y: data.tauxReussite,
                        label: form
                    });
                    radarData.push(data.tauxReussite);
                } else {
                    radarData.push(0);
                }
            } catch (e) {
                radarData.push(0);
            }
        }

        // Mise à jour scatter
        if (chartScatter) {
            chartScatter.data.datasets[0].data = scatterData;
            chartScatter.data.datasets[0].pointBackgroundColor = scatterData.map(d => formationColors[d.label]);
            chartScatter.update();
        }

        // Mise à jour radar
        if (chartRadar) {
            chartRadar.data.datasets[0].data = radarData;
            chartRadar.update();
        }

        // Évolution BUT1->BUT3 basée sur les effectifs calculés par le Sankey
        if (chartEvolution && window.SANKEY_STATS && window.SANKEY_STATS.effectifsParNiveau) {
            const effectifsParNiveau = window.SANKEY_STATS.effectifsParNiveau;
            
            // Récupérer les années pour les labels (limité à 3 max pour le BUT)
            const sankeyData = window.SANKEY_DATA || {};
            const anneesDisponibles = Object.keys(sankeyData)
                .filter(key => key.match(/^data\d{4}$/))
                .map(key => key.replace('data', ''))
                .sort((a, b) => parseInt(a) - parseInt(b))
                .slice(0, 3);
            
            // Utiliser les effectifs calculés depuis les nœuds du Sankey
            const effectifs = [
                effectifsParNiveau.BUT1 || 0,
                effectifsParNiveau.BUT2 || 0,
                effectifsParNiveau.BUT3 || 0
            ].slice(0, anneesDisponibles.length); // Limiter aux années disponibles
            
            // Générer les labels BUT1, BUT2, BUT3 avec les années correspondantes
            const labels = anneesDisponibles.map((annee, i) => `BUT${i+1} (${annee})`);
            
            chartEvolution.data.datasets[0].data = effectifs;
            chartEvolution.data.labels = labels;
            chartEvolution.update();
        }
    }
    
    // Initialiser les graphiques au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        initStatsCharts();
    });
</script>

    <!-- Charger le fichier JavaScript externe -->
    <script src="/Content/script/sankey-logic.js"></script>
</body>
</html>
