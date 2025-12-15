/**
 * Module de visualisation Sankey pour le suivi de cohorte BUT
 * Gère le chargement, le traitement et l'affichage des données
 */

const SankeyCohort = (function() {
    'use strict';

    // Configuration des couleurs
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
        'DEFAULT': '#6B7280'
    };

    /**
     * Convertit une couleur hexadécimale en RGBA
     */
    function hexToRgba(hex, alpha = 1) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    /**
     * Traite les données brutes des trois années pour construire les flux
     */
    function processCohortData(data2021, data2022, data2023) {
        const etudiants = new Map();
        const stats = {
            total2021: 0,
            total2022: 0,
            total2023: 0,
            passages: 0,
            redoublements: 0,
            abandons: 0,
            demissions: 0
        };

        // Traiter les données 2021
        processYearData(data2021, 2021, etudiants, stats, 'total2021');
        
        // Traiter les données 2022
        processYearData(data2022, 2022, etudiants, stats, 'total2022');
        
        // Traiter les données 2023
        processYearData(data2023, 2023, etudiants, stats, 'total2023');

        // Construire les liens entre nœuds
        const links = buildLinks(etudiants, stats);
        
        // Extraire tous les nœuds uniques
        const nodes = extractNodes(links);

        return { 
            nodes: Array.from(nodes), 
            links, 
            stats: {
                ...stats,
                totalEtudiants: etudiants.size
            }
        };
    }

    /**
     * Traite les données d'une année spécifique
     */
    function processYearData(data, year, etudiants, stats, statKey) {
        if (!data || !Array.isArray(data)) return;

        data.forEach(etud => {
            if (!etud.etudid || !etud.annee || !etud.annee.code) return;
            
            if (!etudiants.has(etud.etudid)) {
                etudiants.set(etud.etudid, { annees: [] });
                stats[statKey]++;
            }
            
            etudiants.get(etud.etudid).annees.push({
                annee: year,
                ordre: etud.annee.ordre || 1,
                code: etud.annee.code,
                etat: etud.etat
            });
        });
    }

    /**
     * Construit la map des liens entre nœuds
     */
    function buildLinks(etudiants, stats) {
    const links = new Map();
    
    const addLink = (source, target) => {
        if (source === target) return;
        const key = `${source}→${target}`;
        links.set(key, (links.get(key) || 0) + 1);
    };

    etudiants.forEach(etudiant => {
        etudiant.annees.sort((a, b) => a.annee - b.annee || a.ordre - b.ordre);
        
        // Déterminer l'origine : Parcoursup ou Hors Parcoursup
        const firstStep = etudiant.annees[0];
        let previous = (firstStep.ordre === 1 && firstStep.code === 'ADM') ? 'Parcoursup' : 'Hors Parcoursup';
        
        etudiant.annees.forEach((step, idx) => {
            const niveau = `BUT${step.ordre}`;
            
            // Si c'est la première année, venir de l'origine déterminée
            if (idx === 0) {
                addLink(previous, niveau);
            }
            
            const code = step.code;
            const isLastStep = idx === etudiant.annees.length - 1;
            
            // Déterminer la destination finale
            if (code === 'DEM' || code === 'DEF') {
                stats.demissions++;
                addLink(niveau, code);
            } else if (code === 'NAR') {
                stats.abandons++;
                addLink(niveau, code);
            } else if (['RED', 'AJ', 'ADJ'].includes(code)) {
                stats.redoublements++;
                addLink(niveau, code);
            } else if (['ADM', 'PASD', 'ADSUP', 'CMP'].includes(code)) {
                if (!isLastStep) {
                    // Il y a une année suivante dans les données
                    const next = etudiant.annees[idx + 1];
                    const nextNiveau = `BUT${next.ordre}`;
                    stats.passages++;
                    addLink(niveau, nextNiveau);
                } else if (step.ordre >= 3) {
                    // Dernière année = diplômé
                    addLink(niveau, 'Diplômé');
                } else {
                    // Passage validé mais pas de données suivantes
                    addLink(niveau, code);
                }
            }
        });
    });

    return links;
}

    /**
     * Détermine la destination d'un étudiant selon sa décision jury
     */
    function determineDestination(step, idx, allSteps, stats) {
        const code = step.code;
        
        // Cas d'abandon/démission
        if (code === 'DEM') {
            stats.demissions++;
            return 'DEM';
        }
        if (code === 'DEF') {
            stats.demissions++;
            return 'DEF';
        }
        if (code === 'NAR') {
            stats.abandons++;
            return 'NAR';
        }
        
        // Cas de redoublement
        if (['RED', 'AJ', 'ADJ'].includes(code)) {
            stats.redoublements++;
            return code;
        }
        
        // Cas de passage
        if (['ADM', 'PASD', 'ADSUP', 'CMP'].includes(code)) {
            if (idx < allSteps.length - 1) {
                const next = allSteps[idx + 1];
                stats.passages++;
                return `BUT${next.ordre}`;
            } else if (step.ordre >= 3) {
                return 'Diplômé';
            } else {
                return code;
            }
        }
        
        return null;
    }

    /**
     * Extrait tous les nœuds uniques des liens
     */
    function extractNodes(links) {
        const nodes = new Set();
        links.forEach((count, key) => {
            const [src, tgt] = key.split('→');
            nodes.add(src);
            nodes.add(tgt);
        });
        return nodes;
    }

    /**
     * Détermine la couleur d'un nœud selon son label
     */
    function getNodeColor(label) {
        if (label === 'Parcoursup') return COLORS.Parcoursup;
        if (label === 'Hors Parcoursup') return COLORS['Hors Parcoursup'];
        if (label === 'Diplômé') return COLORS.Diplômé;
        
        // Niveaux BUT
        if (label === 'BUT1') return COLORS.BUT1;
        if (label === 'BUT2') return COLORS.BUT2;
        if (label === 'BUT3') return COLORS.BUT3;
        
        // Décisions jury
        return COLORS[label] || COLORS.DEFAULT;
    }

    /**
     * Détermine la couleur d'un lien selon sa destination
     */
    function getLinkColor(target) {
        if (['NAR', 'DEF', 'DEM'].includes(target)) {
            return hexToRgba(COLORS[target], 0.4);
        }
        if (['RED', 'AJ', 'ADJ'].includes(target)) {
            return hexToRgba(COLORS[target], 0.4);
        }
        if (['ADM', 'PASD', 'ADSUP', 'CMP'].includes(target)) {
            return hexToRgba(COLORS[target], 0.4);
        }
        if (target.includes('BUT')) {
            return hexToRgba(COLORS.Parcoursup, 0.4);
        }
        if (target === 'Diplômé') {
            return hexToRgba(COLORS.Diplômé, 0.4);
        }
        return hexToRgba(COLORS.DEFAULT, 0.4);
    }

    /**
     * Génère le diagramme Sankey avec Plotly
     */
    function renderChart(data) {
        const nodeLabels = data.nodes;
        const nodeIndices = new Map(nodeLabels.map((n, i) => [n, i]));
        
        // Préparer les données des liens
        const sources = [];
        const targets = [];
        const values = [];
        const colors = [];
        
        data.links.forEach((val, key) => {
            const [src, tgt] = key.split('→');
            sources.push(nodeIndices.get(src));
            targets.push(nodeIndices.get(tgt));
            values.push(val);
            colors.push(getLinkColor(tgt));
        });

        // Préparer les couleurs des nœuds
        const nodeColors = nodeLabels.map(getNodeColor);

        // Configuration du graphique
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
                text: `Parcours de ${data.stats.totalEtudiants} étudiants`,
                font: { size: 18, color: "#E3BF81" }
            }
        };

        const config = { 
            responsive: true,
            displayModeBar: true 
        };

        Plotly.newPlot('sankey-plot', plotData, layout, config);
    }

    /**
     * Affiche les statistiques clés
     * TODO: À implémenter par quelqu'un d'autre
     */
    function renderStats(stats) {
        const grid = document.getElementById('stats-grid');
        if (!grid) return;

        // Code commenté pour référence future
        /*
        const statsHTML = `
            <div class="bg-blue-500/10 border border-blue-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">Total étudiants</p>
                <p class="text-3xl font-bold text-blue-400">${stats.totalEtudiants}</p>
            </div>
            <div class="bg-green-500/10 border border-green-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">Passages</p>
                <p class="text-3xl font-bold text-green-400">${stats.passages}</p>
            </div>
            <div class="bg-orange-500/10 border border-orange-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">Redoublements</p>
                <p class="text-3xl font-bold text-orange-400">${stats.redoublements}</p>
            </div>
            <div class="bg-red-500/10 border border-red-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">Abandons</p>
                <p class="text-3xl font-bold text-red-400">${stats.abandons + stats.demissions}</p>
            </div>
        `;
        
        grid.innerHTML = statsHTML;
        */
    }

    /**
     * Initialisation principale
     */
    async function init() {
        const loader = document.getElementById('loader');
        
        try {
            // Récupérer la configuration depuis la variable globale
            const files = window.SANKEY_CONFIG || [];
            
            console.log('Configuration chargée:', files);
            
            if (files.length === 0) {
                throw new Error('Aucun fichier de données configuré');
            }

            // Charger tous les fichiers en parallèle
            console.log('Début du chargement des fichiers...');
            const [data2021, data2022, data2023] = await Promise.all(
                files.map(f => {
                    console.log('Chargement de:', f);
                    return fetch(f).then(r => {
                        console.log(`Réponse pour ${f}:`, r.status, r.ok);
                        if (!r.ok) throw new Error(`Erreur HTTP ${r.status} pour ${f}`);
                        return r.json();
                    });
                })
            );
            
            console.log('Données chargées:', {
                data2021: data2021?.length,
                data2022: data2022?.length,
                data2023: data2023?.length
            });
            
            // Traiter les données
            const processed = processCohortData(data2021, data2022, data2023);
            
            // Masquer le loader
            loader.classList.add('hidden');
            
            // Afficher le graphique et les stats
            renderChart(processed);
            // renderStats(processed.stats); // TODO: À implémenter ultérieurement
            
            // Gérer le toggle des statistiques
            setupStatsToggle();

        } catch (err) {
            console.error('Erreur lors du chargement:', err);
            loader.innerHTML = `❌ Erreur : ${err.message}`;
        }
    }

    /**
     * Configure l'interrupteur d'affichage des statistiques
     */
    function setupStatsToggle() {
        const toggle = document.getElementById('toggle-stats');
        const statsGrid = document.getElementById('stats-grid');
        
        if (toggle && statsGrid) {
            toggle.addEventListener('change', (e) => {
                statsGrid.classList.toggle('opacity-0', !e.target.checked);
                statsGrid.classList.toggle('pointer-events-none', !e.target.checked);
            });
        }
    }

    // API publique du module
    return {
        init,
        processCohortData,
        renderChart,
        renderStats
    };

})();

// Lancement automatique au chargement de la page
document.addEventListener('DOMContentLoaded', SankeyCohort.init);