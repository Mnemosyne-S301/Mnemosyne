/**
 * Module de visualisation Sankey pour le suivi de cohorte BUT
 * VERSION CORRIGÉE - Logique robuste et cohérente
 * 
 * À placer dans : /js/sankey-logic.js
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
        'Abandon': '#EF4444',
        'DEFAULT': '#6B7280'
    };

    // Codes qui indiquent une validation complète
    const CODES_VALIDATION = ['ADM', 'ADSUP'];
    // Codes qui indiquent un passage avec difficultees
    const CODES_PASSAGE_DIFFICILE = ['PASD', 'CMP'];
    // Codes qui indiquent un redoublement
    const CODES_REDOUBLEMENT = ['RED', 'AJ', 'ADJ'];
    // Codes qui indiquent un abandon ou situation spéciale
    const CODES_ABANDON_DEFINITIF = ['NAR', 'DEM', 'DEF'];

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

        console.log(`=== ${etudiants.size} étudiants uniques trouvés ===`);

        const { links, stats } = buildLinks(etudiants);
        const nodes = extractNodes(links);

        console.log('=== STATISTIQUES FINALES ===');
        console.log('Total étudiants:', stats.totalEtudiants);
        console.log('Diplômés:', stats.diplomes);
        console.log('En cours:', stats.enCours);
        console.log('Abandons:', stats.abandons);
        console.log('Liens créés:', links.size);

        return { 
            nodes: Array.from(nodes), 
            links,
            stats
        };
    }

    function processYearData(data, year, etudiants) {
        if (!data || !Array.isArray(data)) {
            console.warn(`Données invalides pour l'année ${year}`);
            return;
        }

        data.forEach(etud => {
            if (!etud.etudid || !etud.annee || !etud.annee.code) {
                console.warn('Étudiant avec données incomplètes:', etud);
                return;
            }
            
            if (!etudiants.has(etud.etudid)) {
                etudiants.set(etud.etudid, { 
                    annees: [],
                    premierNiveau: null 
                });
            }
            
            const etudData = etudiants.get(etud.etudid);
            etudData.annees.push({
                annee: year,
                ordre: etud.annee.ordre || 1,
                code: etud.annee.code,
                etat: etud.etat,
                annee_scolaire: etud.annee.annee_scolaire
            });
            
            if (!etudData.premierNiveau) {
                etudData.premierNiveau = etud.annee.ordre;
            }
        });
    }

    function determineOrigine(firstStep, premierNiveau) {
        if (premierNiveau === 1 && CODES_VALIDATION.includes(firstStep.code)) {
            return 'Parcoursup';
        }
        return 'Hors Parcoursup';
    }

    function buildLinks(etudiants) {
        const links = new Map();
        const stats = {
            totalEtudiants: etudiants.size,
            diplomes: 0,
            enCours: 0,
            abandons: 0
        };
        
        const addLink = (source, target) => {
            if (source === target) return;
            const key = `${source}→${target}`;
            links.set(key, (links.get(key) || 0) + 1);
        };

        etudiants.forEach((etudiant, etudId) => {
            etudiant.annees.sort((a, b) => a.annee - b.annee || a.ordre - b.ordre);
            
            if (etudiant.annees.length === 0) return;

            const firstStep = etudiant.annees[0];
            const lastStep = etudiant.annees[etudiant.annees.length - 1];
            
            const origine = determineOrigine(firstStep, etudiant.premierNiveau);
            const premierNiveau = `BUT${firstStep.ordre}`;
            addLink(origine, premierNiveau);
            
            let niveauActuel = premierNiveau;
            let hasAbandon = false;
            
            for (let i = 0; i < etudiant.annees.length; i++) {
                const step = etudiant.annees[i];
                const niveauStep = `BUT${step.ordre}`;
                const isLastStep = i === etudiant.annees.length - 1;
                const nextStep = i < etudiant.annees.length - 1 ? etudiant.annees[i + 1] : null;
                
                if (CODES_ABANDON_DEFINITIF.includes(step.code)) {
                    addLink(niveauActuel, step.code);
                    stats.abandons++;
                    hasAbandon = true;
                    break;
                }
                
                if (i > 0 && niveauStep !== niveauActuel) {
                    addLink(niveauActuel, niveauStep);
                    niveauActuel = niveauStep;
                }
                
                if (isLastStep) {
                    if (CODES_VALIDATION.includes(step.code) || CODES_PASSAGE_DIFFICILE.includes(step.code)) {
                        if (step.ordre >= 3) {
                            addLink(niveauActuel, 'Diplômé');
                            stats.diplomes++;
                        } else {
                            // Validés en BUT1 ou BUT2 : comptés mais pas d'affichage dans le diagramme
                            stats.enCours++;
                        }
                    } else if (step.code === 'RED') {
                        // Redoublement : affichage spécifique sans retour
                        addLink(niveauActuel, 'RED');
                        stats.enCours++;
                    } else if (step.code === 'AJ' || step.code === 'ADJ') {
                        // Ajournés : affichage spécifique sans retour
                        addLink(niveauActuel, step.code);
                        stats.enCours++;
                    } else if (CODES_ABANDON_DEFINITIF.includes(step.code)) {
                        addLink(niveauActuel, step.code);
                        stats.abandons++;
                    } else {
                        // Autres cas : comptés mais pas d'affichage dans le diagramme
                        stats.enCours++;
                    }
                } else {
                    if (CODES_REDOUBLEMENT.includes(step.code) && nextStep) {
                        const nextNiveau = `BUT${nextStep.ordre}`;
                        if (nextNiveau === niveauActuel) {
                            addLink(niveauActuel, step.code);
                        }
                    }
                    
                    if (CODES_ABANDON_DEFINITIF.includes(step.code) && (!nextStep || nextStep.annee - step.annee > 1)) {
                        addLink(niveauActuel, step.code);
                        stats.abandons++;
                        hasAbandon = true;
                        break;
                    }
                }
            }
        });

        console.log('=== LIENS CRÉÉS (top 20) ===');
        const sortedLinks = Array.from(links.entries())
            .sort((a, b) => b[1] - a[1])
            .slice(0, 20);
        sortedLinks.forEach(([key, count]) => {
            console.log(`${key}: ${count} étudiants`);
        });

        return { links, stats };
    }

    function extractNodes(links) {
        const nodes = new Set();
        links.forEach((count, key) => {
            const [src, tgt] = key.split('→');
            nodes.add(src);
            nodes.add(tgt);
        });
        
        const orderedNodes = [];
        const nodeOrder = [
            'Parcoursup', 'Hors Parcoursup',
            'BUT1', 'BUT2', 'BUT3',
            'ADM', 'PASD', 'ADSUP', 'CMP',
            'RED', 'AJ', 'ADJ',
            'NAR', 'DEF', 'DEM',
            'Diplômé', 'En cours'
        ];
        
        nodeOrder.forEach(n => {
            if (nodes.has(n)) orderedNodes.push(n);
        });
        
        return orderedNodes;
    }

    function getNodeColor(label) {
        return COLORS[label] || COLORS.DEFAULT;
    }

    function getLinkColor(target) {
        const base = COLORS[target] || COLORS.DEFAULT;
        return hexToRgba(base, 0.4);
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
                line: { color: "white", width: 1 },
                hovertemplate: '%{label}<br>%{value} étudiants<extra></extra>'
            },
            link: { 
                source: sources, 
                target: targets, 
                value: values, 
                color: colors,
                hovertemplate: '%{source.label} → %{target.label}<br>%{value} étudiants<extra></extra>'
            }
        }];

        const layout = {
            font: { color: "#FBEDD3", size: 13, family: 'Arial' },
            paper_bgcolor: "rgba(0,0,0,0)",
            plot_bgcolor: "rgba(0,0,0,0)",
            margin: { l: 20, r: 150, t: 60, b: 40 },
            title: { 
                text: `Parcours de ${data.stats.totalEtudiants} étudiants<br><sub>Diplômés: ${data.stats.diplomes} | En cours: ${data.stats.enCours} | Abandons: ${data.stats.abandons}</sub>`,
                font: { size: 18, color: "#E3BF81" }
            }
        };

        const config = { 
            responsive: true,
            displayModeBar: true,
            displaylogo: false,
            modeBarButtonsToRemove: ['lasso2d', 'select2d']
        };

        Plotly.newPlot('sankey-plot', plotData, layout, config);
    }

    async function init() {
        const loader = document.getElementById('loader');
        
        try {
            console.log('init() appelé');
            const data = window.SANKEY_DATA;
            console.log('SANKEY_DATA:', data);
            
            if (!data || !data.data2021 || !data.data2022 || !data.data2023) {
                throw new Error('Données non disponibles');
            }

            console.log('Données valides, traitement en cours...');
            loader.innerHTML = '<p class="animate-pulse text-xl">Analyse des parcours...</p>';
            
            const processed = processCohortData(data.data2021, data.data2022, data.data2023);
            
            loader.classList.add('hidden');
            renderChart(processed);
            
            setupLegendToggle();
            setupBUTFilters(data);

        } catch (err) {
            console.error('Erreur lors du chargement:', err);
            loader.innerHTML = `<p class="text-red-400">⚠ Erreur : ${err.message}</p>`;
        }
    }

    function setupBUTFilters(allData) {
        const buttons = document.querySelectorAll('.but-filter');
        
        buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const level = e.target.dataset.level;
                
                // Mettre à jour l'apparence des boutons
                buttons.forEach(b => {
                    b.classList.remove('bg-[#60A5FA]', 'bg-[#93C5FD]', 'bg-[#DBEAFE]', 'bg-[#E3BF81]', 'text-white', 'text-[#0A1E2F]');
                    b.classList.add('bg-transparent');
                });
                e.target.classList.remove('bg-transparent');
                
                // Déterminer les données à afficher
                let etudiants = new Map();
                
                if (level === 'all') {
                    // Afficher toutes les années
                    e.target.classList.add('bg-[#E3BF81]', 'text-[#0A1E2F]');
                    processYearData(allData.data2021, 2021, etudiants);
                    processYearData(allData.data2022, 2022, etudiants);
                    processYearData(allData.data2023, 2023, etudiants);
                } else {
                    // Filtrer par niveau BUT
                    const numLevel = parseInt(level);
                    const colors = { 1: '#60A5FA', 2: '#93C5FD', 3: '#DBEAFE' };
                    e.target.classList.add(`bg-[${colors[numLevel]}]`, 'text-white');
                    
                    const filteredData = {};
                    [2021, 2022, 2023].forEach(year => {
                        filteredData[year] = allData[`data${year}`].filter(etud => 
                            etud.annee && etud.annee.ordre === numLevel
                        );
                    });
                    
                    processYearData(filteredData[2021], 2021, etudiants);
                    processYearData(filteredData[2022], 2022, etudiants);
                    processYearData(filteredData[2023], 2023, etudiants);
                }
                
                const processed = buildLinks(etudiants);
                const nodes = extractNodes(processed.links);
                
                const chartData = {
                    nodes: Array.from(nodes),
                    links: processed.links,
                    stats: processed.stats
                };
                
                renderChart(chartData);
            });
        });
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

// Appeler init() immédiatement si le DOM est déjà chargé, sinon attendre
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', SankeyCohort.init);
} else {
    SankeyCohort.init();
}