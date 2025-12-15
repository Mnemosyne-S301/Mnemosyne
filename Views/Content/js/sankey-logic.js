/**
 * Module de visualisation Sankey pour le suivi de cohorte BUT
 * VERSION CORRIGÉE - Logique de calcul revue
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
        const stats = {
            total2021: 0,
            total2022: 0,
            total2023: 0,
            passages: 0,
            redoublements: 0,
            abandons: 0,
            demissions: 0,
            diplomes: 0,
            enCours: 0
        };

        processYearData(data2021, 2021, etudiants, stats, 'total2021');
        processYearData(data2022, 2022, etudiants, stats, 'total2022');
        processYearData(data2023, 2023, etudiants, stats, 'total2023');

        const links = buildLinks(etudiants, stats);
        const nodes = extractNodes(links);

        console.log('=== STATISTIQUES FINALES ===');
        console.log('Total étudiants uniques:', etudiants.size);
        console.log('Passages:', stats.passages);
        console.log('Redoublements:', stats.redoublements);
        console.log('Abandons:', stats.abandons);
        console.log('Démissions:', stats.demissions);
        console.log('Diplômés:', stats.diplomes);
        console.log('En cours:', stats.enCours);

        return { 
            nodes: Array.from(nodes), 
            links, 
            stats: {
                ...stats,
                totalEtudiants: etudiants.size
            }
        };
    }

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
     * LOGIQUE CORRIGÉE : Construction des liens sans double comptage
     */
    function buildLinks(etudiants, stats) {
        const links = new Map();
        
        const addLink = (source, target) => {
            if (source === target) return;
            const key = `${source}→${target}`;
            links.set(key, (links.get(key) || 0) + 1);
        };

        etudiants.forEach((etudiant, etudId) => {
            // Trier les années par ordre chronologique
            etudiant.annees.sort((a, b) => a.annee - b.annee || a.ordre - b.ordre);
            
            // Déterminer l'origine (première entrée)
            const firstStep = etudiant.annees[0];
            const origine = (firstStep.ordre === 1 && firstStep.code === 'ADM') 
                ? 'Parcoursup' 
                : 'Hors Parcoursup';
            
            // Créer le lien depuis l'origine vers le premier niveau
            const premierNiveau = `BUT${firstStep.ordre}`;
            addLink(origine, premierNiveau);
            
            // Parcourir chaque étape du parcours
            for (let idx = 0; idx < etudiant.annees.length; idx++) {
                const step = etudiant.annees[idx];
                const niveau = `BUT${step.ordre}`;
                const code = step.code;
                const isLastStep = idx === etudiant.annees.length - 1;
                
                // CAS 1 : Abandon définitif (NAR, DEM, DEF)
                if (['NAR', 'DEM', 'DEF'].includes(code)) {
                    if (code === 'NAR') {
                        stats.abandons++;
                    } else {
                        stats.demissions++;
                    }
                    addLink(niveau, code);
                    break; // L'étudiant quitte le cursus
                }
                
                // CAS 2 : Redoublement (RED, AJ, ADJ)
                if (['RED', 'AJ', 'ADJ'].includes(code)) {
                    stats.redoublements++;
                    
                    // Si pas la dernière étape, il peut continuer après redoublement
                    if (!isLastStep) {
                        const next = etudiant.annees[idx + 1];
                        const nextNiveau = `BUT${next.ordre}`;
                        
                        // Si même niveau, c'est un vrai redoublement visible
                        if (next.ordre === step.ordre) {
                            addLink(niveau, code);
                            // Le lien du redoublement vers le même niveau sera créé au prochain tour
                        } else {
                            // Il a redoublé mais les données montrent qu'il passe au niveau suivant
                            // (cas de validation ultérieure)
                            addLink(niveau, nextNiveau);
                        }
                    } else {
                        // Dernière donnée disponible = redoublant en cours
                        addLink(niveau, code);
                        stats.enCours++;
                    }
                    continue;
                }
                
                // CAS 3 : Passage validé (ADM, PASD, ADSUP, CMP)
                if (['ADM', 'PASD', 'ADSUP', 'CMP'].includes(code)) {
                    if (!isLastStep) {
                        // Il y a une étape suivante dans les données
                        const next = etudiant.annees[idx + 1];
                        const nextNiveau = `BUT${next.ordre}`;
                        
                        // Créer le lien vers le niveau suivant
                        addLink(niveau, nextNiveau);
                        stats.passages++;
                    } else {
                        // Dernière étape dans les données
                        if (step.ordre >= 3) {
                            // BUT3 validé = Diplômé
                            addLink(niveau, 'Diplômé');
                            stats.diplomes++;
                        } else {
                            // BUT1 ou BUT2 validé mais pas de suite = En cours (probablement données incomplètes)
                            addLink(niveau, 'En cours');
                            stats.enCours++;
                        }
                    }
                }
            }
        });

        // Afficher les liens pour debug
        console.log('=== LIENS CRÉÉS ===');
        links.forEach((count, key) => {
            console.log(`${key}: ${count} étudiants`);
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
        if (['NAR', 'DEF', 'DEM'].includes(target)) {
            return hexToRgba(COLORS[target] || COLORS.DEFAULT, 0.4);
        }
        if (['RED', 'AJ', 'ADJ'].includes(target)) {
            return hexToRgba(COLORS[target] || COLORS.DEFAULT, 0.4);
        }
        if (['ADM', 'PASD', 'ADSUP', 'CMP'].includes(target)) {
            return hexToRgba(COLORS[target] || COLORS.DEFAULT, 0.4);
        }
        if (target.includes('BUT')) {
            return hexToRgba(COLORS.Parcoursup, 0.4);
        }
        if (target === 'Diplômé') {
            return hexToRgba(COLORS.Diplômé, 0.4);
        }
        if (target === 'En cours') {
            return hexToRgba(COLORS['En cours'], 0.4);
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
            sources.push(nodeIndices.get(src));
            targets.push(nodeIndices.get(tgt));
            values.push(val);
            colors.push(getLinkColor(tgt));
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

    function renderStats(stats) {
        const grid = document.getElementById('stats-grid');
        if (!grid) return;

        const statsHTML = `
            <div class="bg-blue-500/10 border border-blue-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">Total étudiants</p>
                <p class="text-3xl font-bold text-blue-400">${stats.totalEtudiants || 0}</p>
            </div>
            <div class="bg-green-500/10 border border-green-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">Passages</p>
                <p class="text-3xl font-bold text-green-400">${stats.passages || 0}</p>
            </div>
            <div class="bg-orange-500/10 border border-orange-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">Redoublements</p>
                <p class="text-3xl font-bold text-orange-400">${stats.redoublements || 0}</p>
            </div>
            <div class="bg-purple-500/10 border border-purple-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">Diplômés</p>
                <p class="text-3xl font-bold text-purple-400">${stats.diplomes || 0}</p>
            </div>
            <div class="bg-red-500/10 border border-red-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">Abandons</p>
                <p class="text-3xl font-bold text-red-400">${(stats.abandons || 0) + (stats.demissions || 0)}</p>
            </div>
            <div class="bg-yellow-500/10 border border-yellow-500/30 p-4 rounded-lg">
                <p class="text-sm text-gray-400">En cours</p>
                <p class="text-3xl font-bold text-yellow-400">${stats.enCours || 0}</p>
            </div>
        `;
        
        grid.innerHTML = statsHTML;
    }

    async function init() {
        const loader = document.getElementById('loader');
        
        try {
            const files = window.SANKEY_CONFIG || [];
            
            if (files.length === 0) {
                throw new Error('Aucun fichier de données configuré');
            }

            const [data2021, data2022, data2023] = await Promise.all(
                files.map(f => fetch(f).then(r => {
                    if (!r.ok) throw new Error(`Erreur HTTP ${r.status} pour ${f}`);
                    return r.json();
                }))
            );
            
            const processed = processCohortData(data2021, data2022, data2023);
            
            loader.classList.add('hidden');
            
            renderChart(processed);
            renderStats(processed.stats);
            
            setupStatsToggle();

        } catch (err) {
            console.error('Erreur lors du chargement:', err);
            loader.innerHTML = `❌ Erreur : ${err.message}`;
        }
    }

    function setupStatsToggle() {
        const toggle = document.getElementById('toggle-stats');
        const statsGrid = document.getElementById('stats-grid');
        
        if (toggle && statsGrid) {
            toggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    statsGrid.classList.remove('opacity-0', 'pointer-events-none');
                } else {
                    statsGrid.classList.add('opacity-0', 'pointer-events-none');
                }
            });
        }
    }

    return {
        init,
        processCohortData,
        renderChart,
        renderStats
    };

})();

document.addEventListener('DOMContentLoaded', SankeyCohort.init);