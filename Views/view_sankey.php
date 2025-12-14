<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Mnemosyne — Diagramme Sankey</title>

    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Plotly -->
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>

<body class="h-screen bg-[#0A1E2F] flex flex-col overflow-hidden">

    <!-- ============================
         HEADER
    ============================= -->
    <header class="relative flex flex-col items-center justify-center gap-4 py-8">

        <!-- Bouton retour -->
        <a href="index.php?action=home"
           class="absolute left-8 top-8">
            <img src="img/Retour.png"
                 alt="Retour"
                 class="w-10 h-10 hover:scale-105 transition-transform">
        </a>

        <!-- Logo -->
        <img src="/Statics/img/logo.png"
             alt="Logo Mnemosyne"
             class="w-40 h-40 object-contain" />

        <!-- Slogan -->
        <h2 class="text-[#FBEDD3] text-2xl font-semibold">
            Gardez la mémoire, éclairez les parcours
        </h2>
    </header>

    <!-- ============================
         MAIN
    ============================= -->
    <main class="flex-1 overflow-auto flex flex-col items-center gap-10 pb-10">

        <!-- ============================
             SANKEY
        ============================= -->
        <section class="w-full flex justify-center">
            <div id="sankey-annee-unique"
                 class="w-11/12 h-[600px]
                        bg-[#FFFFFF0A] rounded-2xl
                        backdrop-blur-md shadow-2xl
                        flex items-center justify-center
                        text-[#FBEDD3]">
                <p class="opacity-70">
                    Chargement et construction du flux de cohorte...
                </p>
            </div>
        </section>

        <!-- ============================
             STATS
        ============================= -->
        <section class="w-11/12 flex flex-col gap-4 text-[#FBEDD3]">

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox"
                       class="accent-[#E3BF81] w-4 h-4">
                Afficher les statistiques
            </label>

            <div class="min-h-[150px]
                        border border-[#FBEDD340]
                        rounded-xl bg-[#FFFFFF0A]
                        backdrop-blur-md shadow-lg p-4">
                <!-- Stats futures -->
            </div>

        </section>

    </main>

    <!-- ============================
         SCRIPT SANKEY COMPLET
    ============================= -->
    <script>
    const FICHIERS_JSON = [
        '/Database/example/json/decisions_jury_2022_fs_1095_BUT_Informatique_en_FI_classique.json',
        '/Database/example/json/decisions_jury_2022_fs_1174_BUT_Informatique_en_FI_classique.json'
    ];
    
    let toutesLesDonneesConsolidees = [];
    const ID_DIV_GRAPHIQUE = 'sankey-annee-unique';
    const conteneurGraphique = document.getElementById(ID_DIV_GRAPHIQUE);

    const carteCouleur = {
        'ENTREE_PS': '#F06292',
        'ENTREE_RED': '#FFEE58',
        'ENTREE_HPS': '#4DD0E1',
        'BUT1': '#BDBDBD',
        'BUT2': '#64B5F6',
        'BUT3': '#AB47BC',
        'RED': '#FF7043',
        'ABANDON': '#4CAF50',
        'DIPLOME': '#757575',
        'TRANSFERT': '#9C27B0',
        'REORIENT': '#E91E63'
    };

    const mapStatutVersCategorie = {
        'ADM': 'PROMOTION', 'ADSUP': 'PROMOTION', 'PASD': 'PROMOTION', 'CMP': 'PROMOTION',
        'RED': 'REDOUBLEMENT', 'ADJ': 'REDOUBLEMENT', 'ADJR': 'REDOUBLEMENT',
        'NAR': 'ABANDON', 'DEF': 'ABANDON', 'DEM': 'ABANDON'
    };

    const hexVersRgba = (hex, alpha) => {
        if (!hex || typeof hex !== 'string' || hex[0] !== '#') hex = '#808080';
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };

    const getOrdreButFromSemestreId = (id) => {
        if (id === 1 || id === 2) return 1;
        if (id === 3 || id === 4) return 2;
        if (id === 5 || id === 6) return 3;
        return 0;
    };

    function construireSankeyMultiEtape(donnees) {
        const etudiantsParIne = new Map();

        donnees.forEach(record => {
            if (!record.etudid || !record.annee || !record.annee.code) return;

            if (!record.semestre_id && record.annee.ordre) {
                record.semestre_id = record.annee.ordre * 2;
            }
            if (!record.semestre_id) return;

            if (!etudiantsParIne.has(record.etudid)) {
                etudiantsParIne.set(record.etudid, []);
            }
            etudiantsParIne.get(record.etudid).push(record);
        });

        etudiantsParIne.forEach(records => {
            records.sort((a, b) => {
                const anneeA = parseInt(a.annee.annee_scolaire);
                const anneeB = parseInt(b.annee.annee_scolaire);
                if (anneeA !== anneeB) return anneeA - anneeB;
                return a.semestre_id - b.semestre_id;
            });
        });

        const noeudsUniques = new Map();
        const flux = new Map();

        const NODES_DEBUT = {
            'ParcourSup': 'ParcourSup',
            'Redoublant': 'Redoublant',
            'Hors ParcourSup': 'Hors ParcourSup'
        };

        const NODES_TERMINAUX = {
            'REDOUBLT_BUT1': 'Redoublement BUT1',
            'REDOUBLT_BUT2': 'Redoublement BUT2',
            'REDOUBLT_BUT3': 'Redoublement BUT3',
            'ABANDON_BUT1': 'Abandon BUT1',
            'ABANDON_BUT2': 'Abandon BUT2',
            'ABANDON_BUT3': 'Abandon BUT3',
            'DIPLOME': 'Diplôme',
            'Passerelle': 'Passerelle',
            'Réorientation': 'Réorientation'
        };

        Object.keys(NODES_DEBUT).forEach(k => noeudsUniques.set(k, NODES_DEBUT[k]));
        Object.keys(NODES_TERMINAUX).forEach(k => noeudsUniques.set(k, NODES_TERMINAUX[k]));

        let totalEtudiants = 0;

        etudiantsParIne.forEach(records => {
            totalEtudiants++;

            if (records.length > 0) {
                const premier = records[0];
                const ordre = getOrdreButFromSemestreId(premier.semestre_id);
                if (ordre > 0) {
                    const source = premier.origine || 'ParcourSup';
                    const cible = `BUT${ordre}`;
                    noeudsUniques.set(cible, cible);
                    flux.set(`${source}::${cible}`, (flux.get(`${source}::${cible}`) || 0) + 1);
                }
            }

            records.forEach(etat => {
                const ordre = getOrdreButFromSemestreId(etat.semestre_id);
                if (!ordre) return;

                const source = `BUT${ordre}`;
                let cible = null;

                if (etat.autorisations && etat.autorisations.length > 0) {
                    const auto = etat.autorisations[0];
                    if (auto.formation_code && auto.formation_code !== 'BUT_Informatique') {
                        cible = auto.formation_code.startsWith('BUT') ? 'Passerelle' : 'Réorientation';
                    }
                }

                if (!cible) {
                    const cat = mapStatutVersCategorie[etat.annee.code];
                    if (cat === 'REDOUBLEMENT') cible = `REDOUBLT_BUT${ordre}`;
                    else if (cat === 'ABANDON') cible = `ABANDON_BUT${ordre}`;
                    else if (cat === 'PROMOTION') cible = ordre < 3 ? `BUT${ordre + 1}` : 'DIPLOME';
                    else return;
                }

                if (source === cible) return;
                flux.set(`${source}::${cible}`, (flux.get(`${source}::${cible}`) || 0) + 1);
            });
        });

        const codes = Array.from(noeudsUniques.keys());
        const labels = Array.from(noeudsUniques.values());
        const mapIndex = new Map(codes.map((c, i) => [c, i]));

        const liens = { source: [], target: [], value: [], color: [] };

        flux.forEach((v, k) => {
            const [s, t] = k.split('::');
            if (!mapIndex.has(s) || !mapIndex.has(t)) return;
            liens.source.push(mapIndex.get(s));
            liens.target.push(mapIndex.get(t));
            liens.value.push(v);
            const col = carteCouleur[t] || carteCouleur['DIPLOME'];
            liens.color.push(hexVersRgba(col, 0.6));
        });

        const couleursNoeuds = codes.map(c => carteCouleur[c] || '#cccccc');
        return { labels, liens, couleursNoeuds, totalEtudiants };
    }

    function dessinerGraphique() {
        const d = construireSankeyMultiEtape(toutesLesDonneesConsolidees);
        if (!d || d.liens.value.length === 0) return;

        Plotly.react(ID_DIV_GRAPHIQUE, [{
            type: "sankey",
            orientation: "h",
            node: {
                pad: 15,
                thickness: 20,
                label: d.labels,
                color: d.couleursNoeuds
            },
            link: d.liens
        }], {
            title: `Flux de cohorte BUT Informatique (Total : ${d.totalEtudiants})`,
            font: { color: "#FBEDD3" },
            paper_bgcolor: "rgba(0,0,0,0)",
            plot_bgcolor: "rgba(0,0,0,0)",
            margin: { l: 150, r: 150, t: 50, b: 20 }
        });
    }

    Promise.all(
        FICHIERS_JSON.map(f => fetch(f).then(r => r.json()))
    ).then(res => {
        toutesLesDonneesConsolidees = res.flat();
        dessinerGraphique();
    });
    </script>

</body>
</html>
