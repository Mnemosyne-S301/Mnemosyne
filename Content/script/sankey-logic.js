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
        'Passerelle BUT2': '#8B5CF6',
        'Passerelle BUT3': '#A855F7',
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
        'Inconnu': '#9CA3AF',
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
    function chargerReglesAdmin() {
  
    if (window.SANKEY_REGLES) return window.SANKEY_REGLES;

   
    try {
        return JSON.parse(localStorage.getItem("SANKEY_REGLES") || "null");
    } catch {
        return null;
    }
}

function appliquerReglesSurCode(codeDecision) {
    const configurationRegles = chargerReglesAdmin();
    if (!configurationRegles || !configurationRegles.actif) return codeDecision;

 
    const activerScenarioReussite = (configurationRegles.regles || [])
        .some(regle => regle.resultat === "reussite");

    const activerScenarioEchec = (configurationRegles.regles || [])
        .some(regle => regle.resultat === "echec");

   
    if (activerScenarioReussite) {
        // Exemple demandé : CMP => ADM
        if (codeDecision === "CMP") return "ADM";

  
    }


    if (activerScenarioEchec) {
     
        if (codeDecision === "ADJ") return "AJ";

   
    }

    return codeDecision;
}


    function processCohortData(dataByYear) {
        const etudiants = new Map();

        // Traiter dynamiquement toutes les années disponibles
        Object.entries(dataByYear).forEach(([year, data]) => {
            processYearData(data, parseInt(year), etudiants);
        });

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
                code: appliquerReglesSurCode(etud.annee.code),
                etat: etud.etat,
                annee_scolaire: etud.annee.annee_scolaire
            });
            
            if (!etudData.premierNiveau) {
                etudData.premierNiveau = etud.annee.ordre;
            }
        });
    }

    function determineOrigine(firstStep, premierNiveau) {
        // Les étudiants qui commencent en BUT1 viennent de Parcoursup
        if (premierNiveau === 1) {
            return 'Parcoursup';
        }
        // Les étudiants qui arrivent directement en BUT2 ou BUT3 sont des passerelles
        if (premierNiveau === 2) {
            return 'Passerelle BUT2';
        }
        if (premierNiveau === 3) {
            return 'Passerelle BUT3';
        }
        // Cas par défaut (ne devrait pas arriver)
        return 'Parcoursup';
    }

    function buildLinks(etudiants) {
        const links = new Map();
        const stats = {
            totalEtudiants: etudiants.size,
            diplomes: 0,
            enCours: 0,
            abandons: 0,
            effectifsParNiveau: { BUT1: 0, BUT2: 0, BUT3: 0 }
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
            
            // Utiliser firstStep.ordre (après tri) pour déterminer l'origine réelle
            const origine = determineOrigine(firstStep, firstStep.ordre);
            const premierNiveau = `BUT${firstStep.ordre}`;
            addLink(origine, premierNiveau);
            
            let niveauActuel = premierNiveau;
            let hasAbandon = false;
            
            for (let i = 0; i < etudiant.annees.length; i++) {
                const step = etudiant.annees[i];
                const niveauStep = `BUT${step.ordre}`;
                const isLastStep = i === etudiant.annees.length - 1;
                const nextStep = i < etudiant.annees.length - 1 ? etudiant.annees[i + 1] : null;
                
                // Vérifier si c'est un abandon définitif
                if (CODES_ABANDON_DEFINITIF.includes(step.code)) {
                    // Créer un nœud de sortie spécifique au niveau (ex: NAR_BUT1)
                    const sortieNode = `${step.code}_${niveauActuel}`;
                    addLink(niveauActuel, sortieNode);
                    stats.abandons++;
                    hasAbandon = true;
                    break; // Sortir de la boucle car l'étudiant a abandonné
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
                        // Redoublement : affichage spécifique par niveau
                        addLink(niveauActuel, `RED_${niveauActuel}`);
                        stats.enCours++;
                    } else if (step.code === 'AJ' || step.code === 'ADJ') {
                        // Ajournés : affichage spécifique par niveau
                        addLink(niveauActuel, `${step.code}_${niveauActuel}`);
                        stats.enCours++;
                    } else if (CODES_ABANDON_DEFINITIF.includes(step.code)) {
                        // Abandons : affichage spécifique par niveau
                        addLink(niveauActuel, `${step.code}_${niveauActuel}`);
                        stats.abandons++;
                    } else {
                        // Autres cas : comptés mais pas d'affichage dans le diagramme
                        stats.enCours++;
                    }
                } else {
                    if (CODES_REDOUBLEMENT.includes(step.code) && nextStep) {
                        const nextNiveau = `BUT${nextStep.ordre}`;
                        if (nextNiveau === niveauActuel) {
                            addLink(niveauActuel, `${step.code}_${niveauActuel}`);
                        }
                    }
                    
                    // Note: Les abandons définitifs sont déjà gérés plus haut dans la boucle
                    // Ce bloc gère les cas où un étudiant disparaît entre deux années (gap > 1 an)
                    if (!nextStep || nextStep.annee - step.annee > 1) {
                        // Étudiant disparu sans code d'abandon explicite -> Inconnu
                        if (!hasAbandon && !CODES_VALIDATION.includes(step.code) && !CODES_PASSAGE_DIFFICILE.includes(step.code)) {
                            addLink(niveauActuel, `Inconnu_${niveauActuel}`);
                            stats.abandons++;  // Comptabilisé comme abandon pour les stats
                            hasAbandon = true;
                            break;
                        }
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

        // Calculer les effectifs par niveau BUT depuis les flux entrants du Sankey
        // (somme de tous les liens qui pointent vers BUT1, BUT2, BUT3)
        links.forEach((count, key) => {
            const [src, tgt] = key.split('→');
            if (tgt === 'BUT1' || tgt === 'BUT2' || tgt === 'BUT3') {
                stats.effectifsParNiveau[tgt] += count;
            }
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
        // Ordre de base pour les nœuds principaux
        const nodeOrder = [
            // Origines - classées par niveau d'entrée
            'Parcoursup',
            'Passerelle BUT2',
            'Passerelle BUT3',
            // Niveaux BUT
            'BUT1', 'BUT2', 'BUT3',
            // Codes de validation
            'ADM', 'PASD', 'ADSUP', 'CMP',
            // Redoublements par niveau
            'RED_BUT1', 'RED_BUT2', 'RED_BUT3',
            'AJ_BUT1', 'AJ_BUT2', 'AJ_BUT3',
            'ADJ_BUT1', 'ADJ_BUT2', 'ADJ_BUT3',
            // Abandons par niveau
            'NAR_BUT1', 'NAR_BUT2', 'NAR_BUT3',
            'DEF_BUT1', 'DEF_BUT2', 'DEF_BUT3',
            'DEM_BUT1', 'DEM_BUT2', 'DEM_BUT3',
            // Inconnus (disparus sans code explicite)
            'Inconnu_BUT1', 'Inconnu_BUT2', 'Inconnu_BUT3',
            // Sorties finales
            'Diplômé', 'En cours'
        ];
        
        nodeOrder.forEach(n => {
            if (nodes.has(n)) orderedNodes.push(n);
        });
        
        // Ajouter les nœuds restants qui ne sont pas dans l'ordre prédéfini
        nodes.forEach(n => {
            if (!orderedNodes.includes(n)) orderedNodes.push(n);
        });
        
        return orderedNodes;
    }

    function getNodeColor(label) {
        // Gérer les nœuds composés (ex: NAR_BUT1)
        if (label.includes('_')) {
            const baseCode = label.split('_')[0];
            return COLORS[baseCode] || COLORS.DEFAULT;
        }
        return COLORS[label] || COLORS.DEFAULT;
    }

    function getLinkColor(target) {
        // Gérer les nœuds composés (ex: NAR_BUT1)
        let colorKey = target;
        if (target.includes('_')) {
            colorKey = target.split('_')[0];
        }
        const base = COLORS[colorKey] || COLORS.DEFAULT;
        return hexToRgba(base, 0.4);
    }

    function getDisplayLabel(nodeId) {
        // Convertir l'identifiant interne en label d'affichage
        // Ex: NAR_BUT1 -> NAR, RED_BUT2 -> RED
        if (nodeId.includes('_BUT')) {
            return nodeId.split('_')[0];
        }
        return nodeId;
    }

    function getNodePositions(nodeLabels) {
        // Définir les positions x et y pour chaque nœud
        // x: position horizontale (0 = gauche, 1 = droite)
        // y: position verticale (0 = haut, 1 = bas)
        
        // Positions de base pour les nœuds principaux
        const basePositions = {
            // Origines (colonne 0) - alignées avec leur niveau de destination
            'Parcoursup': { x: 0.01, y: 0.25 },
            'Passerelle BUT2': { x: 0.25, y: 0.8 },
            'Passerelle BUT3': { x: 0.50, y: 1.0 },
            
            // Niveaux BUT - positions de référence
            'BUT1': { x: 0.25, y: 0.25 },
            'BUT2': { x: 0.50, y: 0.25 },
            'BUT3': { x: 0.75, y: 0.25 },
            
            // Diplômé (fin)
            'Diplômé': { x: 0.99, y: 0.35 },
            'En cours': { x: 0.99, y: 0.45 },
        };
        
        // Les sorties de chaque année vont VERS l'année suivante
        // BUT1 → sorties vers BUT2, BUT2 → sorties vers BUT3, BUT3 → sorties vers Diplômé
        const sortieDestinations = {
            'BUT1': basePositions['BUT2'].x,   // Sorties BUT1 vont vers la colonne de BUT2
            'BUT2': basePositions['BUT3'].x,   // Sorties BUT2 vont vers la colonne de BUT3
            'BUT3': basePositions['Diplômé'].x, // Sorties BUT3 vont vers la colonne de Diplômé
        };
        
        // Décalages verticaux pour les différents types de sortie
        const sortieOffsets = {
            'RED': 0.50,
            'AJ': 0.58,
            'ADJ': 0.66,
            'NAR': 0.74,
            'DEM': 0.82,
            'DEF': 0.90,
            'Abandon': 0.98,
        };
        
        const positions = { ...basePositions };
        
        // Générer dynamiquement les positions des sorties basées sur les destinations
        ['BUT1', 'BUT2', 'BUT3'].forEach(but => {
            const destX = sortieDestinations[but];
            
            Object.entries(sortieOffsets).forEach(([code, yOffset]) => {
                const nodeId = `${code}_${but}`;
                positions[nodeId] = { x: destX, y: yOffset };
            });
        });
        
        const xPositions = [];
        const yPositions = [];
        
        nodeLabels.forEach((label, index) => {
            if (positions[label]) {
                xPositions.push(positions[label].x);
                yPositions.push(positions[label].y);
            } else {
                // Position par défaut pour les nœuds non définis
                // Les placer progressivement à droite
                xPositions.push(0.5 + (index * 0.02));
                yPositions.push(0.5);
            }
        });
        
        return { x: xPositions, y: yPositions };
    }

    function renderChart(data) {
        const nodeLabels = data.nodes;
        const nodeIndices = new Map(nodeLabels.map((n, i) => [n, i]));
        
        // Labels d'affichage (simplifiés)
        const displayLabels = nodeLabels.map(getDisplayLabel);
        
        // Positions des nœuds
        const nodePositions = getNodePositions(nodeLabels);
        
        const sources = [];
        const targets = [];
        const values = [];
        const colors = [];
        const linkLabels = [];
        
        data.links.forEach((val, key) => {
            const [src, tgt] = key.split('→');
            const srcIdx = nodeIndices.get(src);
            const tgtIdx = nodeIndices.get(tgt);
            
            if (srcIdx !== undefined && tgtIdx !== undefined) {
                sources.push(srcIdx);
                targets.push(tgtIdx);
                values.push(val);
                colors.push(getLinkColor(tgt));
                linkLabels.push(val);  // Ajouter le nombre d'étudiants
            }
        });

        const nodeColors = nodeLabels.map(getNodeColor);

        const plotData = [{
            type: "sankey",
            orientation: "h",
            arrangement: "freeform",
            node: { 
                pad: 30,
                thickness: 20,
                label: displayLabels,
                color: nodeColors,
                x: nodePositions.x,
                y: nodePositions.y,
                line: { color: "white", width: 1 },
                hovertemplate: '%{label}<br>%{value} étudiants<extra></extra>'
            },
            link: { 
                source: sources, 
                target: targets, 
                value: values, 
                color: colors,
                label: linkLabels,
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

        // Animation de fade-in
        const sankey = document.getElementById('sankey-plot');
        sankey.style.transition = 'opacity 0.3s ease';
        sankey.style.opacity = '0';
        
        Plotly.newPlot('sankey-plot', plotData, layout, config);
        
        // Fade-in après le rendu
        setTimeout(() => {
            sankey.style.opacity = '1';
        }, 50);
    }

    // Extraire les années disponibles depuis SANKEY_DATA
    function extractAvailableYears(data) {
        const years = {};
        Object.keys(data).forEach(key => {
            const match = key.match(/^data(\d{4})$/);
            if (match && Array.isArray(data[key])) {
                years[match[1]] = data[key];
            }
        });
        return years;
    }

    async function init() {
                    // Activer le bouton 'Toutes les années' par défaut
                    document.querySelectorAll('.but-filter').forEach(btn => {
                        btn.classList.remove('bg-[#60A5FA]', 'bg-[#93C5FD]', 'bg-[#DBEAFE]', 'bg-[#E3BF81]', 'text-white', 'text-[#0A1E2F]');
                        btn.classList.add('bg-transparent');
                    });
                    document.getElementById('btn-all')?.classList.add('bg-[#E3BF81]', 'text-[#0A1E2F]');
        const loader = document.getElementById('loader');
        try {
            console.log('init() appelé');
            const data = window.SANKEY_DATA;
            console.log('SANKEY_DATA:', data);
            const availableYears = extractAvailableYears(data);
            const yearKeys = Object.keys(availableYears);
            if (!data || yearKeys.length === 0) {
                throw new Error('Données non disponibles');
            }
            console.log(`Années disponibles: ${yearKeys.join(', ')}`);
            loader && (loader.innerHTML = '<p class="animate-pulse text-xl">Analyse des parcours...</p>');
            const processed = processCohortData(availableYears);
            
            // Exposer les stats globalement pour le graphique d'évolution
            window.SANKEY_STATS = processed.stats;
            
            if (loader) loader.remove();
            renderChart(processed);
            setupLegendToggle();

            // Réinitialiser les handlers des boutons filtres pour utiliser les données courantes
            document.querySelectorAll('.but-filter').forEach(btn => {
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
            });
            setupBUTFilters(availableYears);
        } catch (err) {
            console.error('Erreur lors du chargement:', err);
            loader && (loader.innerHTML = `<p class="text-red-400">⚠ Erreur : ${err.message}</p>`);
        }
    }
    
    

    function setupBUTFilters(availableYears) {
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
                    Object.entries(availableYears).forEach(([year, data]) => {
                        processYearData(data, parseInt(year), etudiants);
                    });
                } else {
                    // Filtrer par niveau BUT
                    const numLevel = parseInt(level);
                    const colors = { 1: '#60A5FA', 2: '#93C5FD', 3: '#DBEAFE' };
                    e.target.classList.add(`bg-[${colors[numLevel]}]`, 'text-white');
                    
                    Object.entries(availableYears).forEach(([year, data]) => {
                        const filteredData = data.filter(etud => 
                            etud.annee && etud.annee.ordre === numLevel
                        );
                        processYearData(filteredData, parseInt(year), etudiants);
                    });
                }
                
                // Animation fade-out avant la mise à jour
                const sankey = document.getElementById('sankey-plot');
                sankey.style.opacity = '0';
                sankey.style.transition = 'opacity 0.3s ease';
                
                setTimeout(() => {
                    const processed = buildLinks(etudiants);
                    const nodes = extractNodes(processed.links);
                    
                    const chartData = {
                        nodes: Array.from(nodes),
                        links: processed.links,
                        stats: processed.stats
                    };
                    
                    renderChart(chartData);
                }, 300);
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


// (Suppression de l'auto-init : l'init doit être déclenchée uniquement après le chargement effectif des données via loadSankeyData)