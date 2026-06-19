// ─── Constantes ──────────────────────────────────────────────────────────────
const CODE_ANNEE_OPTIONS = [
    { code: "ABAN",    label: "ABAN — Abandon constaté (sans lettre de démission)" },
    { code: "ABL",     label: "ABL — Année Blanche" },
    { code: "ADM",     label: "ADM — Admis" },
    { code: "ADJ",     label: "ADJ — Admis par décision de jury" },
    { code: "ADSUP",   label: "ADSUP — Admis par décision supplémentaire" },
    { code: "AJ",      label: "AJ — Ajourné" },
    { code: "ATJ",     label: "ATJ — Non validé (règlement local)" },
    { code: "CMP",     label: "CMP — Compensation" },
    { code: "DEF",     label: "DEF — Défaillant (manque d'assiduité)" },
    { code: "DEM",     label: "DEM — Démission" },
    { code: "EXC",     label: "EXC — Exclusion disciplinaire" },
    { code: "NAR",     label: "NAR — Non admis, réorientation" },
    { code: "PAS1NCI", label: "PAS1NCI — Non admis, passage jury (RCUE < 8)" },
    { code: "PASD",    label: "PASD — Non admis, passage de droit" },
    { code: "RAT",     label: "RAT — En attente d'un rattrapage" },
    { code: "RED",     label: "RED — Ajourné, mais autorisé à redoubler" },
];

// Rempli dynamiquement via l'API
let FORMATIONS = [];

// ─── Éléments UI ─────────────────────────────────────────────────────────────
const btn_ajt       = document.getElementById("Ajt");
const btn_supp      = document.getElementById("Supp");
const btn_save      = document.getElementById("saveRules");
const rulesStatus   = document.getElementById("rulesStatus");
const formContainer = document.body.querySelector("form");

// ─── Classes CSS partagées ───────────────────────────────────────────────────
const CLS_SELECT  = "bg-[#1E3A52] text-white text-sm py-2 px-3 rounded-md border border-white/20 focus:outline-none focus:ring-2 focus:ring-[#60A5FA]/60 cursor-pointer";
const CLS_BTN_ADD = "text-xs bg-[#0E2233] hover:bg-[#1A3A5C] text-[#60A5FA] border border-[#60A5FA]/40 rounded px-3 py-1 transition-colors";
const CLS_BTN_RM  = "text-xs text-red-400 hover:text-red-300 border border-red-400/40 hover:border-red-300/40 rounded px-2 py-1 transition-colors ml-1";

// ─── Utilitaires ─────────────────────────────────────────────────────────────

function mkSelect(extraClass, options /* [{value, label}] */, selected = "") {
    const sel = document.createElement("select");
    sel.className = CLS_SELECT + " " + extraClass;
    options.forEach(({ value, label }) => {
        const opt = document.createElement("option");
        opt.value = value;
        opt.textContent = label;
        sel.append(opt);
    });
    if (selected) sel.value = selected;
    return sel;
}

function mkBtnRemove(onClick) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.textContent = "×";
    btn.className = CLS_BTN_RM;
    btn.addEventListener("click", onClick);
    return btn;
}

function mkBloc(extraClass, ...children) {
    const div = document.createElement("div");
    div.className = "flex items-center gap-2 bg-[#0E2233] border border-white/10 rounded-lg px-3 py-2 text-sm " + extraClass;
    children.forEach(c => div.append(c));
    return div;
}

function mkLabel(text) {
    const span = document.createElement("span");
    span.className = "text-[#FBEDD3]/60 whitespace-nowrap select-none";
    span.textContent = text;
    return span;
}

function afficherStatutRegles(message) {
    if (rulesStatus) rulesStatus.textContent = message;
}

// ─── Blocs optionnels ────────────────────────────────────────────────────────

function creerBlocFormation(valeur, card) {
    const opts = [{ value: "", label: "— choisir —" }]
        .concat(FORMATIONS.map(f => ({ value: f.code, label: f.label })));
    const sel = mkSelect("bloc-formation-select", opts, valeur);

    const bloc = mkBloc("bloc-formation",
        mkLabel("de la formation"),
        sel,
        mkBtnRemove(() => {
            bloc.remove();
            card.querySelector(".btn-add-formation").hidden = false;
        })
    );
    return bloc;
}

function creerBlocCode(valeur, card) {
    const opts = CODE_ANNEE_OPTIONS.map(o => ({ value: o.code, label: o.label }));
    const sel = mkSelect("bloc-code-select", opts, valeur);

    const bloc = mkBloc("bloc-code",
        mkLabel("ayant le code"),
        sel,
        mkBtnRemove(() => {
            bloc.remove();
            card.querySelector(".btn-add-code").hidden = false;
        })
    );
    return bloc;
}

function creerBlocSeuil(seuilSens, seuilValeur, seuilType, card) {
    const selSens = mkSelect("bloc-seuil-sens w-28", [
        { value: "plus",  label: "plus de" },
        { value: "moins", label: "moins de" },
    ], seuilSens || "plus");

    const input = document.createElement("input");
    input.type        = "number";
    input.min         = "0";
    input.max         = "20";
    input.step        = "0.5";
    input.placeholder = "valeur";
    input.className   = CLS_SELECT + " bloc-seuil-valeur w-24";
    if (seuilValeur) input.value = seuilValeur;

    const selType = mkSelect("bloc-seuil-type", [
        { value: "moyenne",      label: "de moyenne" },
        { value: "ues_validées", label: "d'UEs validées" },
    ], seuilType || "moyenne");

    const bloc = mkBloc("bloc-seuil",
        selSens, input, selType,
        mkBtnRemove(() => {
            bloc.remove();
            card.querySelector(".btn-add-seuil").hidden = false;
        })
    );
    return bloc;
}

// ─── Carte de règle ───────────────────────────────────────────────────────────

function creerLigneRegle(regle = {}) {
    const card = document.createElement("div");
    card.className = "rule-card d flex flex-col gap-3 bg-[#122738] p-4 rounded-xl w-full max-w-5xl my-4 text-white border border-white/10 shadow";

    // Ligne principale
    const mainRow = document.createElement("div");
    mainRow.className = "flex items-center gap-3 flex-wrap";

    const blocsArea = document.createElement("div");
    blocsArea.className = "blocs-area flex flex-wrap gap-2 flex-1 min-w-0";

    const selResultat = mkSelect("regle-resultat", [
        { value: "reussite", label: "réussite" },
        { value: "echec",    label: "échec" },
        { value: "ignorer",  label: "ignorer" },
    ], regle.resultat || "reussite");

    // Toggle actif / ignoré
    const estActif = regle.actif !== false;
    const btnToggle = document.createElement("button");
    btnToggle.type = "button";
    btnToggle.dataset.actif = estActif ? "1" : "0";
    const updateToggleStyle = (actif) => {
        btnToggle.textContent = actif ? "● Actif" : "○ Ignorée";
        btnToggle.className = actif
            ? "rule-toggle text-xs bg-green-900/50 hover:bg-green-800/50 text-green-300 border border-green-600/30 rounded-lg px-3 py-1 transition-colors"
            : "rule-toggle text-xs bg-gray-700/50 hover:bg-gray-600/50 text-gray-400 border border-gray-600/30 rounded-lg px-3 py-1 transition-colors";
        card.style.opacity = actif ? "1" : "0.55";
        card.style.filter  = actif ? "" : "grayscale(0.4)";
    };
    updateToggleStyle(estActif);
    btnToggle.addEventListener("click", () => {
        const nowActif = btnToggle.dataset.actif !== "1";
        btnToggle.dataset.actif = nowActif ? "1" : "0";
        updateToggleStyle(nowActif);
    });

    const btnDelete = document.createElement("button");
    btnDelete.type = "button";
    btnDelete.textContent = "Supprimer la règle";
    btnDelete.className = "ml-auto text-xs bg-red-900/40 hover:bg-red-800/50 text-red-300 border border-red-600/30 rounded-lg px-3 py-1 transition-colors";
    btnDelete.addEventListener("click", () => {
        card.remove();
        sauvegarderSilencieusement();
    });

    mainRow.append(mkLabel("Les étudiants"), blocsArea, mkLabel("sont en"), selResultat, btnToggle, btnDelete);

    // Boutons d'ajout de blocs
    const addRow = document.createElement("div");
    addRow.className = "flex gap-2 flex-wrap";

    function mkBtnAdd(text, cls, onClick) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.textContent = text;
        btn.className = CLS_BTN_ADD + " " + cls;
        btn.addEventListener("click", onClick);
        return btn;
    }

    const btnAddFormation = mkBtnAdd("+ Formation", "btn-add-formation", () => {
        blocsArea.append(creerBlocFormation("", card));
        btnAddFormation.hidden = true;
    });

    const btnAddCode = mkBtnAdd("+ Code décision jury", "btn-add-code", () => {
        blocsArea.append(creerBlocCode("", card));
        btnAddCode.hidden = true;
    });

    const btnAddSeuil = mkBtnAdd("+ Seuil numérique", "btn-add-seuil", () => {
        blocsArea.append(creerBlocSeuil("plus", "", "moyenne", card));
        btnAddSeuil.hidden = true;
    });

    addRow.append(btnAddFormation, btnAddCode, btnAddSeuil);

    card.append(mainRow, addRow);
    formContainer.append(card);

    // Restaurer les blocs depuis une règle sauvegardée
    if (regle.formation) {
        blocsArea.append(creerBlocFormation(regle.formation, card));
        btnAddFormation.hidden = true;
    }
    if (regle.code) {
        blocsArea.append(creerBlocCode(regle.code, card));
        btnAddCode.hidden = true;
    }
    if (regle.seuilSens) {
        blocsArea.append(creerBlocSeuil(regle.seuilSens, regle.seuilValeur || "", regle.seuilType || "moyenne", card));
        btnAddSeuil.hidden = true;
    }

    return card;
}

// ─── Sauvegarde silencieuse (sans alert) ────────────────────────────────────

function sauvegarderSilencieusement() {
    const reglesConfig = recupererReglesDepuisFormulaire();
    localStorage.setItem("SANKEY_REGLES", JSON.stringify(reglesConfig));
    fetch('index.php?controller=api&action=rules', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(reglesConfig),
    })
        .then(r => r.json())
        .then(j => {
            if (j.ok) afficherStatutRegles(`Sauvegardé automatiquement (${reglesConfig.regles.length} règle(s)).`);
        })
        .catch(() => afficherStatutRegles('Enregistré localement uniquement.'));
}

// ─── Sérialisation ───────────────────────────────────────────────────────────

function recupererReglesDepuisFormulaire() {
    const regles = [];

    document.querySelectorAll(".rule-card").forEach(card => {
        const resultat = card.querySelector(".regle-resultat")?.value;
        if (!resultat) return;

        const regle = { resultat };

        // État actif/ignoré de la règle
        const toggleBtn = card.querySelector(".rule-toggle");
        regle.actif = !toggleBtn || toggleBtn.dataset.actif !== "0";

        const formation = card.querySelector(".bloc-formation-select")?.value;
        if (formation) regle.formation = formation;

        const code = card.querySelector(".bloc-code-select")?.value;
        if (code) regle.code = code;

        const seuilSens   = card.querySelector(".bloc-seuil-sens")?.value;
        const seuilValeur = card.querySelector(".bloc-seuil-valeur")?.value?.trim();
        const seuilType   = card.querySelector(".bloc-seuil-type")?.value;
        if (seuilSens && seuilValeur) {
            regle.seuilSens   = seuilSens;
            regle.seuilValeur = seuilValeur;
            regle.seuilType   = seuilType || "moyenne";
        }

        regles.push(regle);
    });

    return { actif: true, regles };
}

// ─── Restauration ────────────────────────────────────────────────────────────

async function restaurerReglesSauvegardees() {
    let reglesConfig = null;

    try {
        const resp = await fetch('index.php?controller=api&action=rules');
        if (resp.ok) {
            const json = await resp.json();
            if (json && Array.isArray(json.regles) && json.regles.length > 0) reglesConfig = json;
        }
    } catch (e) {
        console.warn('Impossible de charger les règles depuis le serveur', e);
    }

    if (!reglesConfig) {
        try {
            reglesConfig = JSON.parse(localStorage.getItem("SANKEY_REGLES") || "null");
        } catch {
            afficherStatutRegles("Impossible de charger les règles sauvegardées.");
            return;
        }
    }

    if (!reglesConfig || !Array.isArray(reglesConfig.regles) || reglesConfig.regles.length === 0) {
        afficherStatutRegles("Aucune règle enregistrée pour le moment.");
        return;
    }

    reglesConfig.regles.forEach(creerLigneRegle);
    afficherStatutRegles(`${reglesConfig.regles.length} règle(s) chargée(s).`);
}

// ─── Événements ──────────────────────────────────────────────────────────────

btn_ajt?.addEventListener("click", () => creerLigneRegle());

btn_supp?.addEventListener("click", () => {
    const cards = document.querySelectorAll(".rule-card");
    if (cards.length > 0) {
        cards[cards.length - 1].remove();
        sauvegarderSilencieusement();
    }
});

btn_save?.addEventListener("click", () => {
    const reglesConfig = recupererReglesDepuisFormulaire();
    localStorage.setItem("SANKEY_REGLES", JSON.stringify(reglesConfig));

    fetch('index.php?controller=api&action=rules', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(reglesConfig),
    })
        .then(r => r.json())
        .then(j => {
            if (j.ok) {
                afficherStatutRegles(`${reglesConfig.regles.length} règle(s) enregistrée(s).`);
                alert('Règles enregistrées');
            } else {
                afficherStatutRegles('Enregistré localement, échec serveur.');
            }
        })
        .catch(() => afficherStatutRegles('Enregistré localement, impossible de joindre le serveur.'));
});

// ─── Chargement formations + init ────────────────────────────────────────────

async function chargerFormations() {
    try {
        const resp = await fetch('index.php?controller=sankey&action=getFormations');
        if (resp.ok) FORMATIONS = await resp.json();
    } catch (e) {
        console.warn('Impossible de charger la liste des formations', e);
        FORMATIONS = [
            { code: 'INFO', label: 'BUT Informatique' },
            { code: 'GEA',  label: 'BUT GEA' },
            { code: 'RT',   label: 'BUT Réseaux et Télécommunications' },
            { code: 'GEII', label: 'BUT GEII' },
            { code: 'CJ',   label: 'BUT Carrières Juridiques' },
            { code: 'SD',   label: 'BUT Science des Données' },
        ];
    }
}

async function init() {
    await chargerFormations();
    await restaurerReglesSauvegardees();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
} else {
    init();
}

