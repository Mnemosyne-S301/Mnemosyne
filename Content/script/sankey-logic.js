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


    const CODES_VALIDATION = ['ADM', 'ADSUP'];

    const CODES_PASSAGE_DIFFICILE = ['PASD', 'CMP'];

    const CODES_REDOUBLEMENT = ['RED', 'AJ', 'ADJ'];

    const CODES_ABANDON_DEFINITIF = ['NAR', 'DEM', 'DEF'];

    function hexToRgba(hex, alpha = 1) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    function chargerReglesAdmin() {
        const config = window.SANKEY_REGLES;
        if (config && typeof config === 'object' && Array.isArray(config.regles)) {
            return config;
        }

        try {
            const parsed = JSON.parse(localStorage.getItem("SANKEY_REGLES") || "null");
            if (parsed && typeof parsed === 'object' && Array.isArray(parsed.regles)) {
                return parsed;
            }
        } catch {
            // fallback
        }

        return { actif: false, regles: [] };
    }


    function regleMatchCode(regle, codeNorm, etudiant) {
        const formationCourante = String(window.SANKEY_FORMATION || '').toUpperCase();

        if (regle.formation) {
            if (String(regle.formation).toUpperCase() !== formationCourante) return false;
        }

        // Nouveau format
        if (regle.code !== undefined) {
            return String(regle.code || '').toUpperCase() === codeNorm;
        }
        if (regle.seuilSens) {
            if (!etudiant || typeof etudiant !== 'object') return false;
            const sens   = String(regle.seuilSens).toLowerCase();
            const thresh = parseFloat(regle.seuilValeur ?? NaN);
            if (Number.isNaN(thresh)) return false;

            if ((regle.seuilType || '').toLowerCase().includes('moyenne')) {
                const v = parseFloat(etudiant?.annee?.moyenne ?? etudiant?.moyenne ?? NaN);
                if (Number.isNaN(v)) return false;
                return sens === 'plus' ? v > thresh : v < thresh;
            }
            if ((regle.seuilType || '').toLowerCase().includes('ue')) {
                const v = parseInt(etudiant?.annee?.ues_validees ?? etudiant?.ues_validees ?? NaN, 10);
                if (Number.isNaN(v)) return false;
                return sens === 'plus' ? v > thresh : v < thresh;
            }
            return false;
        }
        // Si la règle n'a pas de code ou de seuil elle compte pout tous les étudiants
        if (regle.formation) return true;

        // ancien format
        const condition  = String(regle.condition || '').toLowerCase();
        const valeur     = String(regle.valeur || '').toUpperCase();
        const valeurType = String(regle.valeurType || '').toLowerCase();
        if (condition === 'formation' || condition === 'code_annuel') return valeur === codeNorm;
        if (condition === 'plus' || condition === 'moins') {
            if (!etudiant || typeof etudiant !== 'object') return false;
            if (valeurType === 'moyenne') {
                const v = parseFloat(etudiant?.annee?.moyenne ?? etudiant?.moyenne ?? NaN);
                if (Number.isNaN(v)) return false;
                const t = parseFloat(valeur);
                if (Number.isNaN(t)) return false;
                return condition === 'plus' ? v > t : v < t;
            }
            if (valeurType.includes('ue')) {
                const v = parseInt(etudiant?.annee?.ues_validees ?? etudiant?.ues_validees ?? NaN, 10);
                if (Number.isNaN(v)) return false;
                const t = parseInt(valeur, 10);
                if (Number.isNaN(t)) return false;
                return condition === 'plus' ? v > t : v < t;
            }
        }
        return false;
    }



    function appliquerReglesSurCode(codeDecision, etudiant) {
        const config = chargerReglesAdmin();
        if (!config.actif || !Array.isArray(config.regles) || config.regles.length === 0) {
            return codeDecision;
        }
        const codeNorm = String(codeDecision || '').toUpperCase();

        const matching = config.regles
            .filter(r => r.actif !== false)
            .filter(r => regleMatchCode(r, codeNorm, etudiant));

        if (matching.some(r => r.resultat === 'reussite')) return 'ADM';
        if (matching.some(r => r.resultat === 'echec'))    return 'AJ';
        return codeDecision;
    }

    /**

     */
    function filtrerEtudiantsParRegles(etudiants) {
        const config = chargerReglesAdmin();
        if (!config.actif || !Array.isArray(config.regles) || config.regles.length === 0) {
            return etudiants;
        }

        const formationCourante = String(window.SANKEY_FORMATION || '').toUpperCase();


        const reglesActives = config.regles.filter(r => {
            if (r.actif === false) return false;

            if (r.formation && String(r.formation).toUpperCase() !== formationCourante) return false;
            return true;
        });

        if (reglesActives.length === 0) return etudiants;

        const reglesIgnorer = reglesActives.filter(r => r.resultat === 'ignorer' || r.resultat === 'supprimer');
        const reglesInclure = reglesActives.filter(r => r.resultat !== 'ignorer' && r.resultat !== 'supprimer');

        if (reglesIgnorer.length === 0 && reglesInclure.length === 0) return etudiants;


        const codesIgnorer = new Set(
            reglesIgnorer.map(r => String(r.code || r.valeur || '').toUpperCase()).filter(Boolean)
        );

  
        const inclusionSansCode = reglesInclure.some(r => !r.code && !r.seuilSens && !r.valeur);
        const codesInclure = inclusionSansCode
            ? null
            : new Set(reglesInclure.map(r => String(r.code || r.valeur || '').toUpperCase()).filter(Boolean));

        const filtered = new Map();
        etudiants.forEach((etudiant, etudId) => {
            const codes = etudiant.annees.map(a => String(a.codeOriginal || a.code || '').toUpperCase());

            if (codesIgnorer.size > 0 && codes.some(c => codesIgnorer.has(c))) return;

            if (codesInclure !== null && codesInclure.size > 0 && !codes.some(c => codesInclure.has(c))) return;

            filtered.set(etudId, etudiant);
        });

        console.log(`[Règles] ${filtered.size}/${etudiants.size} étudiants conservés` +
            (codesInclure ? ` (codes requis: ${[...codesInclure]})` : ' (tous codes acceptés)') +
            (codesIgnorer.size ? `, ignorés: ${[...codesIgnorer]}` : ''));
        return filtered;
    }


    function processCohortData(dataByYear) {
        const etudiants = new Map();


        Object.entries(dataByYear).forEach(([year, data]) => {
            processYearData(data, parseInt(year), etudiants);
        });

        console.log(`=== ${etudiants.size} étudiants uniques trouvés ===`);


        const etudiantsFiltres = filtrerEtudiantsParRegles(etudiants);

        const { links, stats } = buildLinks(etudiantsFiltres);
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
            const codeOriginal = String(etud.annee.code || '').toUpperCase();
            etudData.annees.push({
                annee: year,
                ordre: etud.annee.ordre || 1,
                code: appliquerReglesSurCode(etud.annee.code, etud),
                codeOriginal,
                etat: etud.etat,
                annee_scolaire: etud.annee.annee_scolaire
            });
            
            if (!etudData.premierNiveau) {
                etudData.premierNiveau = etud.annee.ordre;
            }
        });
    }

    function determineOrigine(firstStep, premierNiveau) {
        if (premierNiveau === 1) {
            return 'Parcoursup';
        }
        // Les étudiants directement en BUT2 ou + sont des passerelles
        if (premierNiveau === 2) {
            return 'Passerelle BUT2';
        }
        if (premierNiveau === 3) {
            return 'Passerelle BUT3';
        }
    
        return 'Parcoursup';
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
                
                // Vérif si c'est un abandon définitif
                if (CODES_ABANDON_DEFINITIF.includes(step.code)) {
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

                            stats.enCours++;
                        }
                    } else if (step.code === 'RED') {
  
                        addLink(niveauActuel, `RED_${niveauActuel}`);
                        stats.enCours++;
                    } else if (step.code === 'AJ' || step.code === 'ADJ') {
    
                        addLink(niveauActuel, `${step.code}_${niveauActuel}`);
                        stats.enCours++;
                    } else if (CODES_ABANDON_DEFINITIF.includes(step.code)) {

                        addLink(niveauActuel, `${step.code}_${niveauActuel}`);
                        stats.abandons++;
                    } else {

                        stats.enCours++;
                    }
                } else {
                    if (CODES_REDOUBLEMENT.includes(step.code) && nextStep) {
                        const nextNiveau = `BUT${nextStep.ordre}`;
                        if (nextNiveau === niveauActuel) {
                            addLink(niveauActuel, `${step.code}_${niveauActuel}`);
                        }
                    }
                    

                    if (!nextStep || nextStep.annee - step.annee > 1) {

                        if (!hasAbandon && !CODES_VALIDATION.includes(step.code) && !CODES_PASSAGE_DIFFICILE.includes(step.code)) {
                            addLink(niveauActuel, `Inconnu_${niveauActuel}`);
                            stats.abandons++; 
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

            'Parcoursup',
            'Passerelle BUT2',
            'Passerelle BUT3',

            'BUT1', 'BUT2', 'BUT3',

            'ADM', 'PASD', 'ADSUP', 'CMP',

            'RED_BUT1', 'RED_BUT2', 'RED_BUT3',
            'AJ_BUT1', 'AJ_BUT2', 'AJ_BUT3',
            'ADJ_BUT1', 'ADJ_BUT2', 'ADJ_BUT3',
 
            'NAR_BUT1', 'NAR_BUT2', 'NAR_BUT3',
            'DEF_BUT1', 'DEF_BUT2', 'DEF_BUT3',
            'DEM_BUT1', 'DEM_BUT2', 'DEM_BUT3',

            'Inconnu_BUT1', 'Inconnu_BUT2', 'Inconnu_BUT3',
 
            'Diplômé', 'En cours'
        ];
        
        nodeOrder.forEach(n => {
            if (nodes.has(n)) orderedNodes.push(n);
        });
        
   
        nodes.forEach(n => {
            if (!orderedNodes.includes(n)) orderedNodes.push(n);
        });
        
        return orderedNodes;
    }

    function getNodeColor(label) {
    
        if (label.includes('_')) {
            const baseCode = label.split('_')[0];
            return COLORS[baseCode] || COLORS.DEFAULT;
        }
        return COLORS[label] || COLORS.DEFAULT;
    }

    function getLinkColor(target) {

        let colorKey = target;
        if (target.includes('_')) {
            colorKey = target.split('_')[0];
        }
        const base = COLORS[colorKey] || COLORS.DEFAULT;
        return hexToRgba(base, 0.4);
    }

    function getDisplayLabel(nodeId) {

        if (nodeId.includes('_BUT')) {
            return nodeId.split('_')[0];
        }
        return nodeId;
    }

    function getNodePositions(nodeLabels) {

        
        // Positions de base 
        const basePositions = {
            // Origine
            'Parcoursup': { x: 0.01, y: 0.25 },
            'Passerelle BUT2': { x: 0.25, y: 0.8 },
            'Passerelle BUT3': { x: 0.50, y: 1.0 },
            

            'BUT1': { x: 0.25, y: 0.25 },
            'BUT2': { x: 0.50, y: 0.25 },
            'BUT3': { x: 0.75, y: 0.25 },
            
  
            'Diplômé': { x: 0.99, y: 0.35 },
            'En cours': { x: 0.99, y: 0.45 },
        };
        

        const sortieDestinations = {
            'BUT1': basePositions['BUT2'].x, 
            'BUT2': basePositions['BUT3'].x, 
            'BUT3': basePositions['Diplômé'].x, 
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
        
        // Générer  les positions des noeuds qui sortent par rapport aux destinations
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
                //On les pousse à droite
                xPositions.push(0.5 + (index * 0.02));
                yPositions.push(0.5);
            }
        });
        
        return { x: xPositions, y: yPositions };
    }

    function renderChart(data) {
        const nodeLabels = data.nodes;
        const nodeIndices = new Map(nodeLabels.map((n, i) => [n, i]));
        

        const displayLabels = nodeLabels.map(getDisplayLabel);
        
  
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
                linkLabels.push(val);  
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

 
        const sankey = document.getElementById('sankey-plot');
        sankey.style.transition = 'opacity 0.3s ease';
        sankey.style.opacity = '0';
        
        Plotly.newPlot('sankey-plot', plotData, layout, config);
        
        // Fade-in après le rendu
        setTimeout(() => {
            sankey.style.opacity = '1';
        }, 50);
    }


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
            if (loader) loader.remove();
            renderChart(processed);
            setupLegendToggle();

    
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
                
    
                buttons.forEach(b => {
                    b.classList.remove('bg-[#60A5FA]', 'bg-[#93C5FD]', 'bg-[#DBEAFE]', 'bg-[#E3BF81]', 'text-white', 'text-[#0A1E2F]');
                    b.classList.add('bg-transparent');
                });
                e.target.classList.remove('bg-transparent');
                

                let etudiants = new Map();
                
                if (level === 'all') {
      
                    e.target.classList.add('bg-[#E3BF81]', 'text-[#0A1E2F]');
                    Object.entries(availableYears).forEach(([year, data]) => {
                        processYearData(data, parseInt(year), etudiants);
                    });
                } else {
  
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
                
      
                const sankey = document.getElementById('sankey-plot');
                sankey.style.opacity = '0';
                sankey.style.transition = 'opacity 0.3s ease';
                
                setTimeout(() => {
                    const etudiantsFiltres = filtrerEtudiantsParRegles(etudiants);
                    const processed = buildLinks(etudiantsFiltres);
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
