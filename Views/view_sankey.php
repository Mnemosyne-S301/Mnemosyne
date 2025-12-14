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
        <h1 class="text-3xl font-bold tracking-wide">Suivi de Cohorte d'étudiants</h1>
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
                <!-- Les statistiques seront ajoutées ici ultérieurement -->
            </div>
        </section>

        <section class="w-full max-w-7xl mx-auto">
            <h3 class="text-xl font-semibold mb-4">📚 Légende des décisions jury</h3>
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

<script>
const CONFIG = {
    files: [
        '/Database/example/json/decisions_jury_2022_fs_1095_BUT_Informatique_en_FI_classique.json',
        '/Database/example/json/decisions_jury_2023_fs_1174_BUT_Informatique_en_FI_classique.json'
    ],
    colors: {
        'Parcoursup': '#3B82F6',
        'BUT1_2022': '#60A5FA', 
        'BUT2_2022': '#93C5FD',
        'BUT1_2023': '#FBBF24',
        'BUT2_2023': '#FCD34D',
        'BUT3_2023': '#FDE68A',
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
    }
};

const hexToRgba = (hex, a) => {
    const r = parseInt(hex.slice(1,3), 16);
    const g = parseInt(hex.slice(3,5), 16);
    const b = parseInt(hex.slice(5,7), 16);
    return `rgba(${r}, ${g}, ${b}, ${a})`;
};

function processCohortData(data2022, data2023) {
    const etudiants = new Map();
    const stats = {
        total2022: 0,
        total2023: 0,
        passages: 0,
        redoublements: 0,
        abandons: 0,
        demissions: 0
    };

    // Traiter les données 2022
    data2022.forEach(etud => {
        if (!etud.etudid || !etud.annee || !etud.annee.code) return;
        
        if (!etudiants.has(etud.etudid)) {
            etudiants.set(etud.etudid, { annees: [] });
            stats.total2022++;
        }
        
        etudiants.get(etud.etudid).annees.push({
            annee: 2022,
            ordre: etud.annee.ordre || 1,
            code: etud.annee.code,
            etat: etud.etat
        });
    });

    // Traiter les données 2023
    data2023.forEach(etud => {
        if (!etud.etudid || !etud.annee || !etud.annee.code) return;
        
        if (!etudiants.has(etud.etudid)) {
            etudiants.set(etud.etudid, { annees: [] });
            stats.total2023++;
        }
        
        etudiants.get(etud.etudid).annees.push({
            annee: 2023,
            ordre: etud.annee.ordre || 1,
            code: etud.annee.code,
            etat: etud.etat
        });
    });

    const links = new Map();
    const addLink = (s, t) => {
        if (s === t) return;
        const key = `${s}→${t}`;
        links.set(key, (links.get(key) || 0) + 1);
    };

    // Construire les flux
    etudiants.forEach(etudiant => {
        etudiant.annees.sort((a, b) => a.annee - b.annee || a.ordre - b.ordre);
        
        let previous = 'Parcoursup';
        
        etudiant.annees.forEach((step, idx) => {
            const niveau = `BUT${step.ordre}_${step.annee}`;
            addLink(previous, niveau);
            
            // Déterminer la destination selon le code
            let destination = null;
            const code = step.code;
            
            if (code === 'DEM') {
                destination = 'DEM';
                stats.demissions++;
            } else if (code === 'DEF') {
                destination = 'DEF';
                stats.demissions++;
            } else if (code === 'NAR') {
                destination = 'NAR';
                stats.abandons++;
            } else if (code === 'RED') {
                destination = 'RED';
                stats.redoublements++;
            } else if (code === 'AJ') {
                destination = 'AJ';
                stats.redoublements++;
            } else if (code === 'ADJ') {
                destination = 'ADJ';
                stats.redoublements++;
            } else if (['ADM', 'PASD', 'ADSUP', 'CMP'].includes(code)) {
                if (idx < etudiant.annees.length - 1) {
                    const next = etudiant.annees[idx + 1];
                    destination = `BUT${next.ordre}_${next.annee}`;
                    stats.passages++;
                } else if (step.ordre >= 3) {
                    destination = 'Diplômé';
                } else {
                    // On garde le code original si pas de suite
                    destination = code;
                }
            }
            
            if (destination) {
                addLink(niveau, destination);
            }
            
            previous = niveau;
        });
    });

    const nodes = new Set();
    links.forEach((count, key) => {
        const [src, tgt] = key.split('→');
        nodes.add(src);
        nodes.add(tgt);
    });

    return { 
        nodes: Array.from(nodes), 
        links, 
        stats: {
            ...stats,
            totalEtudiants: etudiants.size
        }
    };
}

async function init() {
    try {
        const [data2022, data2023] = await Promise.all(
            CONFIG.files.map(f => fetch(f).then(r => r.json()))
        );
        
        const processed = processCohortData(data2022, data2023);
        
        document.getElementById('loader').classList.add('hidden');
        renderChart(processed);
        // renderStats(processed.stats); // À implémenter ultérieurement
        
        document.getElementById('toggle-stats').addEventListener('change', (e) => {
            document.getElementById('stats-grid').classList.toggle('opacity-0', !e.target.checked);
        });

    } catch (err) {
        console.error('Erreur:', err);
        document.getElementById('loader').innerHTML = "❌ Erreur de chargement des données.";
    }
}

function renderChart(data) {
    const nodeLabels = data.nodes;
    const nodeIndices = new Map(nodeLabels.map((n, i) => [n, i]));
    
    const s = [], t = [], v = [], c = [];
    data.links.forEach((val, key) => {
        const [src, tgt] = key.split('→');
        s.push(nodeIndices.get(src));
        t.push(nodeIndices.get(tgt));
        v.push(val);
        
        let color = CONFIG.colors.DEFAULT;
        if (['NAR', 'DEF', 'DEM'].includes(tgt)) {
            color = CONFIG.colors[tgt];
        } else if (['RED', 'AJ', 'ADJ'].includes(tgt)) {
            color = CONFIG.colors[tgt];
        } else if (['ADM', 'PASD', 'ADSUP', 'CMP'].includes(tgt)) {
            color = CONFIG.colors[tgt];
        } else if (tgt.includes('BUT')) {
            color = CONFIG.colors.Parcoursup;
        } else if (tgt === 'Diplômé') {
            color = CONFIG.colors.Diplômé;
        }
        
        c.push(hexToRgba(color, 0.4));
    });

    const nodeColors = nodeLabels.map(label => {
        if (label === 'Parcoursup') return CONFIG.colors.Parcoursup;
        if (label.includes('2022')) return CONFIG.colors.BUT1_2022;
        if (label.includes('2023')) return CONFIG.colors.BUT1_2023;
        if (label === 'NAR') return CONFIG.colors.NAR;
        if (label === 'DEF') return CONFIG.colors.DEF;
        if (label === 'DEM') return CONFIG.colors.DEM;
        if (label === 'RED') return CONFIG.colors.RED;
        if (label === 'AJ') return CONFIG.colors.AJ;
        if (label === 'ADJ') return CONFIG.colors.ADJ;
        if (label === 'ADM') return CONFIG.colors.ADM;
        if (label === 'PASD') return CONFIG.colors.PASD;
        if (label === 'ADSUP') return CONFIG.colors.ADSUP;
        if (label === 'CMP') return CONFIG.colors.CMP;
        if (label === 'Diplômé') return CONFIG.colors.Diplômé;
        return CONFIG.colors.DEFAULT;
    });

    Plotly.newPlot('sankey-plot', [{
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
            source: s, 
            target: t, 
            value: v, 
            color: c 
        }
    }], {
        font: { color: "#FBEDD3", size: 13, family: 'Arial' },
        paper_bgcolor: "rgba(0,0,0,0)",
        plot_bgcolor: "rgba(0,0,0,0)",
        margin: { l: 20, r: 150, t: 40, b: 40 },
        title: { 
            text: `Parcours de ${data.stats.totalEtudiants} étudiants`,
            font: { size: 18, color: "#E3BF81" }
        }
    }, { 
        responsive: true,
        displayModeBar: true 
    });
}

// Fonction pour les statistiques - à implémenter ultérieurement
// function renderStats(stats) {
//     const grid = document.getElementById('stats-grid');
//     // Code des statistiques ici
// }

init();
</script>
</body>
</html>