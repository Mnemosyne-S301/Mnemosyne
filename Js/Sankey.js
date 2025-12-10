// --- LISTE DES FICHIERS JSON À CHARGER ---
const FICHIERS_JSON = [
'decisions_jury_2022_fs_1095_BUT_Informatique_en_FI_classique.json',
'decisions_jury_2023_fs_1174_BUT_Informatique_en_FI_classique.json'
];
let toutesLesDonneesConsolidees = [];
const selecteurCohorte = document.getElementById('selecteur-cohorte');
const conteneurGraphique = document.getElementById('sankey-annee-unique');
const ID_DIV_GRAPHIQUE = 'sankey-annee-unique';

// Définition des couleurs (Identique à la version précédente)
const carteCouleur = {
'ADM': '#008000', 'ADSUP': '#008080', 'PASD': '#90EE90',
'RED': '#A52A2A', 'ADJ': '#FFA500', 'NAR': '#FF0000',
'DEF': '#800080', 'DEM': '#808080', 'Cohorte': '#1E90FF'
};

const hexVersRgba = (hex, alpha) => {
const r = parseInt(hex.slice(1, 3), 16);
const g = parseInt(hex.slice(3, 5), 16);
const b = parseInt(hex.slice(5, 7), 16);
return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

// --- TRAITEMENT ET RENDU ---

function traiterDonneesPourSankey(donnees, anneeCible) {
const donneesCohorte = donnees.filter(r =>
    r.annee &&
    r.annee.annee_scolaire == anneeCible &&
    r.annee.code
);

if (donneesCohorte.length === 0) return null;

const comptesDecisions = new Map();
const ordreFormationAnnee = donneesCohorte[0].annee.ordre || 'N/A';

donneesCohorte.forEach(etudiant => {
    const code = etudiant.annee.code;
    comptesDecisions.set(code, (comptesDecisions.get(code) || 0) + 1);
});

// Préparation des listes pour Plotly
const LIBELLE_SOURCE = `Cohorte BUT${ordreFormationAnnee} (${anneeCible}-${parseInt(anneeCible) + 1})`;
const ensembleNoeuds = new Set([LIBELLE_SOURCE]);
const cibles = Array.from(comptesDecisions.keys());
cibles.forEach(noeud => ensembleNoeuds.add(noeud));

const tousLesNoeuds = Array.from(ensembleNoeuds);
const carteNoeuds = new Map(tousLesNoeuds.map((label, i) => [label, i]));

const liens = { source: [], target: [], value: [] };
const indexSource = carteNoeuds.get(LIBELLE_SOURCE);

// Créer les liens: Cohorte -> Chaque Décision
cibles.forEach(code => {
    liens.source.push(indexSource);
    liens.target.push(carteNoeuds.get(code));
    liens.value.push(comptesDecisions.get(code));
});

return { noeuds: tousLesNoeuds, liens: liens, totalEtudiants: donneesCohorte.length };
}

function afficherMessage(conteneur, message, estErreur = false) {
conteneur.innerHTML = `<p class="loading-message" style="color: ${estErreur ? 'red' : '#555'};">${message}</p>`;
}

function dessinerGraphiqueSankey(anneeCible) {
// AVERTISSEMENT : NE PAS UTILISER innerHTML = ... ICI POUR ÉVITER DE DÉTRUIRE LE GRAPHIQUE
// Si le graphique est déjà dessiné, Plotly.react va le mettre à jour.

const donneesTraitees = traiterDonneesPourSankey(toutesLesDonneesConsolidees, anneeCible);

if (!donneesTraitees) {
    afficherMessage(conteneurGraphique, `Aucune donnée de décision trouvée pour l'année ${anneeCible}-${parseInt(anneeCible) + 1}. (Effectif: 0)`, true);
    return;
}

const { noeuds, liens, totalEtudiants } = donneesTraitees;

const couleursNoeuds = noeuds.map(label => {
    if (label.includes("Cohorte")) return carteCouleur['Cohorte'];
    const statut = label.split(' ')[0];
    return carteCouleur[statut] || '#ADD8E6';
});

const couleursLiens = liens.target.map(indexCible => {
    const libelleCible = noeuds[indexCible];
    const statut = libelleCible.split(' ')[0];
    return hexVersRgba(carteCouleur[statut] || '#ADD8E6', 0.6);
});

const donneesGraphique = [{
    type: "sankey",
    orientation: "h",
    node: {
        pad: 15, thickness: 20, line: {color: "black", width: 0.5},
        label: noeuds,
        color: couleursNoeuds
    },
    link: {
        source: liens.source,
        target: liens.target,
        value: liens.value,
        color: couleursLiens
    }
}];

const miseEnPage = {
    title: `Résultats du Jury : Année ${anneeCible}-${parseInt(anneeCible) + 1} (Total Étudiants: ${totalEtudiants})`,
    font: {size: 12},
    // Assurez-vous que le mode est défini pour le re-rendu
    autosize: true
};

// Vider d'abord si ce n'est pas déjà un graphique Plotly pour éviter d'empiler les messages d'erreur.
if (!document.getElementById(ID_DIV_GRAPHIQUE).classList.contains('js-plotly-plot')) {
    conteneurGraphique.innerHTML = ''; // Nettoyer seulement si ce n'est pas une zone Plotly
}

Plotly.react(ID_DIV_GRAPHIQUE, donneesGraphique, miseEnPage);
    // Inclure une image Sankey pour la visualisation
console.log("");
}

// --- GESTION DU CHARGEMENT ET INITIALISATION ---

function chargerTousLesFichiers(fichiers) {
const promesses = fichiers.map(fichier =>
    fetch(fichier).then(reponse => {
        if (!reponse.ok) {
            throw new Error(`Échec du chargement de ${fichier} : Code ${reponse.status}.`);
        }
        return reponse.json();
    })
);
return Promise.all(promesses);
}

chargerTousLesFichiers(FICHIERS_JSON)
.then(resultats => {
    toutesLesDonneesConsolidees = resultats.flat();

    const anneesUniques = Array.from(new Set(toutesLesDonneesConsolidees
        .filter(r => r.annee && r.annee.annee_scolaire)
        .map(r => r.annee.annee_scolaire.toString())
    )).sort().reverse();

    if (anneesUniques.length === 0) {
        afficherMessage(conteneurGraphique, "Aucune année académique valide trouvée.", true);
        return;
    }

    // Remplir le menu déroulant
    anneesUniques.forEach(annee => {
        const option = document.createElement('option');
        option.value = annee;
        option.textContent = `${annee}-${parseInt(annee) + 1}`;
        selecteurCohorte.appendChild(option);
    });

    // Définir l'écouteur d'événement
    selecteurCohorte.addEventListener('change', (evenement) => {
        dessinerGraphiqueSankey(evenement.target.value);
    });

    // Dessiner le graphique initial
    dessinerGraphiqueSankey(anneesUniques[0]);
})
.catch(erreur => {
    console.error("Erreur critique lors du chargement :", erreur);
    afficherMessage(conteneurGraphique, `Erreur critique lors du chargement des fichiers: ${erreur.message}. (Si local, pensez au serveur Python http.server)`, true);
});