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
            <img src="img/Retour.png" alt="Retour" class="w-10 h-10 group-hover:scale-110 transition-transform">
        </a>
        <h1 class="text-3xl font-bold tracking-wide">Suivi de Cohorte BUT</h1>
    </header>

    <main class="flex-1 overflow-y-auto px-10 pb-10 space-y-8">
        
        <section class="w-full">
            <div id="sankey-container" class="w-full h-[700px] bg-[#FFFFFF0A] rounded-2xl backdrop-blur-md shadow-2xl border border-white/10 relative">
                <div id="loader" class="absolute inset-0 flex items-center justify-center z-10">
                    <p class="animate-pulse text-xl">Analyse des flux de cohorte...</p>
                </div>
                <div id="sankey-plot" class="w-full h-full"></div>
            </div>
        </section>

        <section class="w-full max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <input type="checkbox" id="toggle-legend" checked class="accent-[#E3BF81] w-5 h-5 cursor-pointer">
                <label for="toggle-legend" class="text-lg font-medium cursor-pointer">Afficher la légende</label>
            </div>

            <div id="legend-container" class="grid grid-cols-1 lg:grid-cols-2 gap-8 transition-all duration-500">
                <!-- Origine des étudiants -->
                <div>
                    <h3 class="text-xl font-semibold mb-4">Origine des étudiants</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div class="bg-blue-500/10 border border-blue-500/30 p-3 rounded-lg">
                            <span class="font-bold text-blue-400">Parcoursup:</span> Admission via Parcoursup
                        </div>
                        <div class="bg-purple-500/10 border border-purple-500/30 p-3 rounded-lg">
                            <span class="font-bold text-purple-400">Hors Parcoursup:</span> Autres modes d'admission
                        </div>
                    </div>
                </div>

                <!-- Légende des décisions jury -->
                <div>
                    <h3 class="text-xl font-semibold mb-4">Légende des décisions jury</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                        <div class="bg-green-500/10 border border-green-500/30 p-3 rounded-lg">
                            <span class="font-bold text-green-400">ADM:</span> Admis
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
                            <span class="font-bold text-orange-400">AJ:</span> Ajourné
                        </div>
                        <div class="bg-orange-500/10 border border-orange-500/30 p-3 rounded-lg">
                            <span class="font-bold text-orange-400">RED:</span> Redoublement
                        </div>
                        <div class="bg-orange-500/10 border border-orange-500/30 p-3 rounded-lg">
                            <span class="font-bold text-orange-400">ADJ:</span> Ajourné avec jury
                        </div>
                        <div class="bg-red-500/10 border border-red-500/30 p-3 rounded-lg">
                            <span class="font-bold text-red-400">NAR:</span> Non autorisé à redoubler
                        </div>
                        <div class="bg-red-500/10 border border-red-500/30 p-3 rounded-lg">
                            <span class="font-bold text-red-400">DEF:</span> Défaillant
                        </div>
                        <div class="bg-red-500/10 border border-red-500/30 p-3 rounded-lg">
                            <span class="font-bold text-red-400">DEM:</span> Démission
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        window.SANKEY_CONFIG = [
            '/Database/example/json/testdata/test_promo_2021_v2.json',
            '/Database/example/json/testdata/test_promo_2022_v2.json',
            '/Database/example/json/testdata/test_promo_2023_v2.json'
        ];
    </script>
    
    <script>
/**
 * Module de visualisation Sankey pour le suivi de cohorte BUT
 * VERSION OPTIMISÉE - Corrigée et améliorée
 */

const SankeyCohort = (function() {
    'use strict';

    const COLORS = {
        'Parcoursup': '#3B82F6',
        'Hors Parcoursup': '#8B5CF6',
        'BUT1': '#60A5FA', 
        'BUT2': '#93C5FD',
        'BUT3': '#DBEAFE',
        'ADM': '#10B981',
        'PASD': '#34D399',
        'ADSUP': '#6EE7B7',
        'CMP': '#A7F3D0',
        'RED': '#F59E0B',
        'ADJ': '#FBBF24',
        'AJ': '#FCD34D',
        'NAR': '#EF4444',
        'DEF': '#DC2626',
        'DEM': '#B91C1C',
        'Diplômé': '#8B5CF6',
        'En cours': '#60A5FA',
        'DEFAULT': '#6B7280'
    };

    function hexToRgba(hex, alpha = 1) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    function processCohortData(data2021, data2022, data2023) {
        const etudiants = new Map();

        processYearData(data2021, 2021, etudiants);
        processYearData(data2022, 2022, etudiants);
        processYearData(data2023, 2023, etudiants);

        const links = buildLinks(etudiants);
        const nodes = extractNodes(links);

        console.log('Total étudiants:', etudiants.size);
        console.log('Liens créés:', links.size);

        return { 
            nodes: Array.from(nodes), 
            links,
            totalEtudiants: etudiants.size
        };
    }

    function processYearData(data, year, etudiants) {
        if (!data || !Array.isArray(data)) return;

        data.forEach(etud => {
            if (!etud.etudid || !etud.annee || !etud.annee.code) return;
            
            if (!etudiants.has(etud.etudid)) {
                etudiants.set(etud.etudid, { annees: [] });
            }
            
            etudiants.get(etud.etudid).annees.push({
                annee: year,
                ordre: etud.annee.ordre || 1,
                code: etud.annee.code,
                etat: etud.etat
            });
        });
    }

    function buildLinks(etudiants) {
        const links = new Map();
        
        const addLink = (source, target, count = 1) => {
            if (source === target) return;
            const key = `${source}→${target}`;
            links.set(key, (links.get(key) || 0) + count);
        };

        etudiants.forEach((etudiant) => {
            etudiant.annees.sort((a, b) => a.annee - b.annee || a.ordre - b.ordre);
            
            const firstStep = etudiant.annees[0];
            const origine = (firstStep.ordre === 1 && firstStep.code === 'ADM') 
                ? 'Parcoursup' 
                : 'Hors Parcoursup';
            
            const premierNiveau = `BUT${firstStep.ordre}`;
            addLink(origine, premierNiveau);
            
            for (let idx = 0; idx < etudiant.annees.length; idx++) {
                const step = etudiant.annees[idx];
                const niveau = `BUT${step.ordre}`;
                const code = step.code;
                const isLastStep = idx === etudiant.annees.length - 1;
                
                // Cas d'abandon définitif
                if (['NAR', 'DEM', 'DEF'].includes(code)) {
                    addLink(niveau, code);
                    break;
                }
                
                // Cas de redoublement
                if (['RED', 'AJ', 'ADJ'].includes(code)) {
                    if (!isLastStep) {
                        const next = etudiant.annees[idx + 1];
                        const nextNiveau = `BUT${next.ordre}`;
                        
                        if (next.ordre === step.ordre) {
                            addLink(niveau, code);
                        } else {
                            addLink(niveau, nextNiveau);
                        }
                    } else {
                        addLink(niveau, code);
                    }
                    continue;
                }
                
                // Cas de passage validé
                if (['ADM', 'PASD', 'ADSUP', 'CMP'].includes(code)) {
                    if (!isLastStep) {
                        const next = etudiant.annees[idx + 1];
                        const nextNiveau = `BUT${next.ordre}`;
                        addLink(niveau, nextNiveau);
                    } else {
                        if (step.ordre >= 3) {
                            addLink(niveau, 'Diplômé');
                        } else {
                            addLink(niveau, 'En cours');
                        }
                    }
                }
            }
        });

        return links;
    }

    function extractNodes(links) {
        const nodes = new Set();
        links.forEach((count, key) => {
            const [src, tgt] = key.split('→');
            nodes.add(src);
            nodes.add(tgt);
        });
        return nodes;
    }

    function getNodeColor(label) {
        return COLORS[label] || COLORS.DEFAULT;
    }

    function getLinkColor(target) {
        const colorMap = {
            'NAR': COLORS.NAR,
            'DEF': COLORS.DEF,
            'DEM': COLORS.DEM,
            'RED': COLORS.RED,
            'AJ': COLORS.AJ,
            'ADJ': COLORS.ADJ,
            'ADM': COLORS.ADM,
            'PASD': COLORS.PASD,
            'ADSUP': COLORS.ADSUP,
            'CMP': COLORS.CMP,
            'Diplômé': COLORS.Diplômé,
            'En cours': COLORS['En cours']
        };
        
        if (colorMap[target]) {
            return hexToRgba(colorMap[target], 0.4);
        }
        
        if (target.includes('BUT')) {
            return hexToRgba(COLORS.Parcoursup, 0.4);
        }
        
        return hexToRgba(COLORS.DEFAULT, 0.4);
    }

    function renderChart(data) {
        const nodeLabels = data.nodes;
        const nodeIndices = new Map(nodeLabels.map((n, i) => [n, i]));
        
        const sources = [];
        const targets = [];
        const values = [];
        const colors = [];
        
        data.links.forEach((val, key) => {
            const [src, tgt] = key.split('→');
            const srcIdx = nodeIndices.get(src);
            const tgtIdx = nodeIndices.get(tgt);
            
            if (srcIdx !== undefined && tgtIdx !== undefined) {
                sources.push(srcIdx);
                targets.push(tgtIdx);
                values.push(val);
                colors.push(getLinkColor(tgt));
            }
        });

        const nodeColors = nodeLabels.map(getNodeColor);

        const plotData = [{
            type: "sankey",
            orientation: "h",
            node: { 
                pad: 30,
                thickness: 20,
                label: nodeLabels,
                color: nodeColors,
                line: { color: "white", width: 1 }
            },
            link: { 
                source: sources, 
                target: targets, 
                value: values, 
                color: colors 
            }
        }];

        const layout = {
            font: { color: "#FBEDD3", size: 13, family: 'Arial' },
            paper_bgcolor: "rgba(0,0,0,0)",
            plot_bgcolor: "rgba(0,0,0,0)",
            margin: { l: 20, r: 150, t: 40, b: 40 },
            title: { 
                text: `Parcours de ${data.totalEtudiants} étudiants`,
                font: { size: 18, color: "#E3BF81" }
            }
        };

        const config = { 
            responsive: true,
            displayModeBar: true 
        };

        Plotly.newPlot('sankey-plot', plotData, layout, config);
    }

    async function init() {
        const loader = document.getElementById('loader');
        
        try {
            const files = window.SANKEY_CONFIG || [];
            
            if (files.length === 0) {
                throw new Error('Aucun fichier de données configuré');
            }

            const dataPromises = files.map(f => 
                fetch(f).then(r => {
                    if (!r.ok) throw new Error(`Erreur HTTP ${r.status} pour ${f}`);
                    return r.json();
                })
            );
            
            const [data2021, data2022, data2023] = await Promise.all(dataPromises);
            
            const processed = processCohortData(data2021, data2022, data2023);
            
            loader.classList.add('hidden');
            
            renderChart(processed);
            
            setupLegendToggle();

        } catch (err) {
            console.error('Erreur lors du chargement:', err);
            loader.innerHTML = `⚠ Erreur : ${err.message}`;
        }
    }

    function setupLegendToggle() {
        const toggle = document.getElementById('toggle-legend');
        const legendContainer = document.getElementById('legend-container');
        
        if (toggle && legendContainer) {
            toggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    legendContainer.classList.remove('opacity-0', 'pointer-events-none', 'h-0');
                } else {
                    legendContainer.classList.add('opacity-0', 'pointer-events-none', 'h-0');
                }
            });
        }
    }

    return { init };

})();

document.addEventListener('DOMContentLoaded', SankeyCohort.init);
    </script>
</body>
</html>