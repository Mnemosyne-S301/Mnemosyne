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
let COHORTES    = []; // années de cohorte disponibles

// ─── Éléments UI ─────────────────────────────────────────────────────────────
const btn_ajt       = document.getElementById("Ajt");

const btn_save      = document.getElementById("saveRules");
const rulesStatus   = document.getElementById("rulesStatus");
const formContainer = document.body.querySelector("form");

// ─── Classes CSS partagées ───────────────────────────────────────────────────
const CLS_SELECT  = "bg-[#071624] text-white text-sm py-1.5 px-3 rounded-lg border border-white/15 focus:outline-none focus:ring-2 focus:ring-[#60A5FA]/40 focus:border-[#60A5FA]/50 cursor-pointer transition-colors";
const CLS_BTN_ADD = "flex items-center gap-1.5 text-xs bg-[#60A5FA]/10 hover:bg-[#60A5FA]/20 text-[#60A5FA] border border-[#60A5FA]/20 hover:border-[#60A5FA]/40 rounded-lg px-3 py-1.5 transition-colors font-medium";
const CLS_BTN_RM  = "flex items-center justify-center w-6 h-6 text-white/30 hover:text-red-400 hover:bg-red-900/30 rounded-md transition-colors ml-1 text-xs border border-white/5 hover:border-red-600/30";

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
    btn.innerHTML = "<i class='fas fa-xmark'></i>";
    btn.title = "Retirer ce bloc";
    btn.className = CLS_BTN_RM;
    btn.addEventListener("click", onClick);
    return btn;
}

function mkBloc(extraClass, ...children) {
    const div = document.createElement("div");
    div.className = "inline-flex items-center gap-2 bg-[#0A1929] border border-white/10 rounded-lg px-3 py-1.5 text-sm " + extraClass;
    children.forEach(c => div.append(c));
    return div;
}

function mkLabel(text) {
    const span = document.createElement("span");
    span.className = "text-white/40 text-xs whitespace-nowrap select-none font-medium tracking-wide";
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
    card.className = "rule-card relative flex flex-col bg-[#0A1E2F] rounded-2xl w-full text-white border border-white/[0.08] shadow-lg overflow-hidden transition-all duration-300";

    // Barre colorée à gauche (accent)
    const accent = document.createElement("div");
    accent.className = "rule-accent absolute left-0 top-0 bottom-0 w-[3px] rounded-l-2xl bg-[#EDB85C]";
    card.append(accent);

    // En-tête de la carte
    const cardHeader = document.createElement("div");
    cardHeader.className = "flex items-center gap-3 px-5 py-3 border-b border-white/[0.06] bg-white/[0.02]";

    const headerLabel = document.createElement("span");
    headerLabel.className = "text-xs font-semibold text-white/40 uppercase tracking-widest select-none";
    headerLabel.textContent = "Règle";

    const headerSpacer = document.createElement("div");
    headerSpacer.className = "flex-1";

    // Corps de la carte
    const cardBody = document.createElement("div");
    cardBody.className = "flex flex-col gap-4 px-5 pt-4 pb-5";

    // Ligne principale (phrase)
    const mainRow = document.createElement("div");
    mainRow.className = "flex items-center gap-2 flex-wrap";

    const blocsArea = document.createElement("div");
    blocsArea.className = "blocs-area flex flex-wrap gap-2";

    // Cohorte — toujours visible
    const selCohorte = mkSelect("regle-cohorte w-24",
        COHORTES.length
            ? COHORTES.map(y => ({ value: String(y), label: String(y) }))
            : [{ value: String(new Date().getFullYear() - 3), label: String(new Date().getFullYear() - 3) }],
        regle.cohorte ? String(regle.cohorte) : ""
    );
    // Option neutre par défaut ("année...")
    const optVide = document.createElement("option");
    optVide.value = "";
    optVide.textContent = "année…";
    selCohorte.prepend(optVide);
    if (!regle.cohorte) selCohorte.value = "";

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
        btnToggle.innerHTML = actif
            ? "<span class='w-2 h-2 rounded-full bg-green-400 inline-block mr-1.5'></span>Actif"
            : "<span class='w-2 h-2 rounded-full bg-white/30 inline-block mr-1.5'></span>Ignorée";
        btnToggle.className = actif
            ? "rule-toggle flex items-center text-xs bg-green-900/30 hover:bg-green-800/40 text-green-400 border border-green-700/40 rounded-lg px-3 py-1.5 transition-colors font-medium"
            : "rule-toggle flex items-center text-xs bg-white/5 hover:bg-white/10 text-white/40 border border-white/10 rounded-lg px-3 py-1.5 transition-colors font-medium";
        accent.className = actif
            ? "rule-accent absolute left-0 top-0 bottom-0 w-[3px] rounded-l-2xl bg-[#EDB85C]"
            : "rule-accent absolute left-0 top-0 bottom-0 w-[3px] rounded-l-2xl bg-white/15";
        card.style.opacity = actif ? "1" : "0.6";
    };
    updateToggleStyle(estActif);
    btnToggle.addEventListener("click", () => {
        const nowActif = btnToggle.dataset.actif !== "1";
        btnToggle.dataset.actif = nowActif ? "1" : "0";
        updateToggleStyle(nowActif);
    });

    const btnDelete = document.createElement("button");
    btnDelete.type = "button";
    btnDelete.innerHTML = "<i class='fas fa-trash-can'></i>";
    btnDelete.title = "Supprimer cette règle";
    btnDelete.className = "flex items-center justify-center w-7 h-7 text-white/30 hover:text-red-400 hover:bg-red-900/30 rounded-lg transition-colors border border-white/5 hover:border-red-600/30 text-xs";
    btnDelete.addEventListener("click", () => {
        card.remove();
        sauvegarderSilencieusement();
    });

    cardHeader.append(headerLabel, headerSpacer, btnToggle, btnDelete);

    mainRow.append(mkLabel("Les étudiants"), blocsArea, mkLabel("de la cohorte"), selCohorte, mkLabel("sont en"), selResultat);

    // Boutons d'ajout de blocs
    const addRow = document.createElement("div");
    addRow.className = "flex gap-2 flex-wrap pt-1 border-t border-white/[0.05]";

    function mkBtnAdd(icon, text, cls, onClick) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.innerHTML = "<i class='fas " + icon + " text-[10px]'></i><span>" + text + "</span>";
        btn.className = CLS_BTN_ADD + " " + cls;
        btn.addEventListener("click", onClick);
        return btn;
    }

    const btnAddFormation = mkBtnAdd("fa-building-columns", "Formation", "btn-add-formation", () => {
        blocsArea.append(creerBlocFormation("", card));
        btnAddFormation.hidden = true;
    });

    const btnAddCode = mkBtnAdd("fa-tag", "Code décision", "btn-add-code", () => {
        blocsArea.append(creerBlocCode("", card));
        btnAddCode.hidden = true;
    });

    const btnAddSeuil = mkBtnAdd("fa-sliders", "Seuil numérique", "btn-add-seuil", () => {
        blocsArea.append(creerBlocSeuil("plus", "", "moyenne", card));
        btnAddSeuil.hidden = true;
    });

    addRow.append(btnAddFormation, btnAddCode, btnAddSeuil);

    cardBody.append(mainRow, addRow);
    card.append(cardHeader, cardBody);
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

        const cohorte = card.querySelector(".regle-cohorte")?.value;
        if (cohorte) regle.cohorte = parseInt(cohorte, 10);

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

    const emptyState = document.getElementById('rulesEmptyState');

    if (!reglesConfig || !Array.isArray(reglesConfig.regles) || reglesConfig.regles.length === 0) {
        afficherStatutRegles("Aucune règle enregistrée pour le moment.");
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');
    reglesConfig.regles.forEach(creerLigneRegle);
    afficherStatutRegles(`${reglesConfig.regles.length} règle(s) chargée(s).`);
}

// ─── Événements ──────────────────────────────────────────────────────────────

btn_ajt?.addEventListener("click", () => {
    creerLigneRegle();
    const empty = document.getElementById('rulesEmptyState');
    if (empty) empty.classList.add('hidden');
});

btn_save?.addEventListener("click", () => {
    const reglesConfig = recupererReglesDepuisFormulaire();
    localStorage.setItem("SANKEY_REGLES", JSON.stringify(reglesConfig));

    const origHTML = btn_save.innerHTML;
    btn_save.disabled = true;
    btn_save.innerHTML = "<i class='fas fa-spinner fa-spin text-xs'></i><span>Enregistrement…</span>";

    fetch('index.php?controller=api&action=rules', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(reglesConfig),
    })
        .then(r => r.json())
        .then(j => {
            if (j.ok) {
                afficherStatutRegles(`${reglesConfig.regles.length} r\u00e8gle(s) enregistr\u00e9e(s).`);
                btn_save.innerHTML = "<i class='fas fa-check text-xs'></i><span>Enregistr\u00e9</span>";
                setTimeout(() => { btn_save.disabled = false; btn_save.innerHTML = origHTML; }, 2000);
            } else {
                afficherStatutRegles('Enregistrement échoué c\u00f4t\u00e9 serveur.');
                btn_save.disabled = false; btn_save.innerHTML = origHTML;
            }
        })
        .catch(() => {
            afficherStatutRegles('Enregistr\u00e9 localement uniquement.');
            btn_save.disabled = false; btn_save.innerHTML = origHTML;
        });
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

async function chargerCohortes() {
    try {
        const resp = await fetch('index.php?controller=sankey&action=getAnnees');
        if (resp.ok) COHORTES = await resp.json();
    } catch (e) {
        console.warn('Impossible de charger les cohortes', e);
        COHORTES = [2021, 2022, 2023, 2024];
    }
}

async function init() {
    await Promise.all([chargerFormations(), chargerCohortes()]);
    await restaurerReglesSauvegardees();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
} else {
    init();
}

