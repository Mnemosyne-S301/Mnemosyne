const btn_ajt = document.getElementById("Ajt");
const btn_supp = document.getElementById("Supp");
const divp = document.body.querySelector("form");


const btn_save = document.getElementById("saveRules");

const CONDITION_CODE_VALUE = "formation";
const VALEUR_FIELD_CLASS = "placeholder-gray-700 bg-[#999999] w-full mx-6 py-4 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600";
const CODE_ANNEE_OPTIONS = [
    { code: "ABAN", label: "ABAN — Abandon constaté (sans lettre de démission)" },
    { code: "ABL", label: "ABL — Année Blanche" },
    { code: "ADM", label: "ADM — Admis" },
    { code: "ADJ", label: "ADJ — Admis par décision de jury" },
    { code: "ATJ", label: "ATJ — Non validé pour une autre raison, voir règlement local" },
    { code: "DEF", label: "DEF — (défaillance) Non évalué par manque assiduité" },
    { code: "DEM", label: "DEM — Démission" },
    { code: "EXC", label: "EXC — Exclusion, décision réservée à des décisions disciplinaires" },
    { code: "NAR", label: "NAR — Non admis, réorientation" },
    { code: "PAS1NCI", label: "PAS1NCI — Non admis, passage jury (RCUE < 8)" },
    { code: "PASD", label: "PASD — Non admis, passage de droit" },
    { code: "RAT", label: "RAT — En attente d’un rattrapage" },
    { code: "RED", label: "RED — Ajourné, mais autorisé à redoubler" }
];
const VALEUR_TYPE_OPTIONS = [
    { value: "moyenne", label: "Moyenne" },
    { value: "UEs validées", label: "UEs validées" },
];

function creerInputValeur(valeur = "") {
    const input = document.createElement("input");
    input.type = "number";
    input.min = "1";
    input.max = "20";
    input.step = "1";
    input.placeholder = "Valeur";
    input.className = VALEUR_FIELD_CLASS;
    input.classList.add("regle-valeur");
    if (valeur) input.value = valeur;
    return input;
}

function creerSelectCode(valeur = "") {
    const select = document.createElement("select");
    select.className = VALEUR_FIELD_CLASS;
    select.classList.add("regle-valeur");
    CODE_ANNEE_OPTIONS.forEach(({ code, label }) => {
        const option = document.createElement("option");
        option.value = code;
        option.textContent = label;
        select.append(option);
    });
    if (valeur) select.value = valeur;
    return select;
}

function creerSelectValeurType(valeur = "") {
    const select = document.createElement("select");
    select.className = VALEUR_FIELD_CLASS;
    select.classList.add("regle-valeur-type");
    VALEUR_TYPE_OPTIONS.forEach(({ value, label }) => {
        const option = document.createElement("option");
        option.value = value;
        option.textContent = label;
        select.append(option);
    });
    if (valeur) select.value = valeur;
    return select;
}

function mettreAJourChampValeur(container, conditionValue) {
    const champActuel = container.querySelector(".regle-valeur");
    const valeurActuelle = champActuel?.value ?? "";
    const doitEtreSelect = conditionValue === CONDITION_CODE_VALUE;
    const estSelect = champActuel?.tagName === "SELECT";
    if (champActuel && doitEtreSelect === estSelect) return;
    const nouveauChamp = doitEtreSelect ? creerSelectCode(valeurActuelle) : creerInputValeur(valeurActuelle);
    if (champActuel) {
        champActuel.replaceWith(nouveauChamp);
    } else {
        container.append(nouveauChamp);
    }

    const champType = container.querySelector(".regle-valeur-type");
    if (champType) {
        champType.hidden = doitEtreSelect;
        champType.disabled = doitEtreSelect;
    }
}

btn_ajt.addEventListener("click", () => {
    const div = document.createElement("div");
    const filtre = document.createElement("label");
    const select = document.createElement("select");
    const option1 = document.createElement("option");
    const option2 = document.createElement("option");
    const option3 = document.createElement("option");
    const option4 = document.createElement("option");
    const option5 = document.createElement("option");

    const inputCondition = creerInputValeur();
    const selectValeurType = creerSelectValeurType();
    const filtre2 = document.createElement("label");
    const select2 = document.createElement("select");


    div.className = "flex justify_center text-white items-center my-6 bg-[#122738] p-4 rounded-lg d w-full max-w-5xl";
    select.className = "placeholder-gray-700 bg-[#999999] w-full mx-6 py-4 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600";
    select2.className = "placeholder-gray-700 bg-[#999999] w-full mx-6 py-4 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600";
    inputCondition.className = VALEUR_FIELD_CLASS;

    select.classList.add("regle-condition");
    select2.classList.add("regle-resultat");
    inputCondition.classList.add("regle-valeur");
    

    
    filtre.textContent = "Les étudiants ";
    option1.textContent = "ayant pour code";
    option2.textContent = "ayant plus de";
    option3.textContent = "ayant moins de";

    option1.value = "formation";
    option2.value = "plus";
    option3.value = "moins";

    select.append(option1);
    select.append(option2);
    select.append(option3);

    div.append(filtre);
    div.append(select);
    div.append(inputCondition);
    div.append(selectValeurType);

    filtre2.textContent = "sont en ";
    option4.textContent = "réussite";
    option5.textContent = "échec";

    option4.value = "reussite";
    option5.value = "echec";

    select2.append(option4);
    select2.append(option5);

    div.append(filtre2);
    div.append(select2);

    divp.appendChild(div);

    select.addEventListener("change", () => {
        mettreAJourChampValeur(div, select.value);
    });
    mettreAJourChampValeur(div, select.value);
});

btn_supp.addEventListener("click", () => {
    const divs = document.querySelectorAll(".d");
    if(divs.length > 0){
        divs[divs.length - 1].remove();
    }
});

//fonction qui récupère toutes les règles dans le formulaire
function recupererReglesDepuisFormulaire() {
    const divs = document.querySelectorAll(".d");
    const regles = [];

    divs.forEach(div => {
        const condition = div.querySelector(".regle-condition")?.value;
        const resultat = div.querySelector(".regle-resultat")?.value;
        const valeur = div.querySelector(".regle-valeur")?.value?.trim();
        const valeurType = condition === CONDITION_CODE_VALUE
            ? null
            : div.querySelector(".regle-valeur-type")?.value?.trim();
        const valeurResultat = div.querySelector(".regle-resultat-valeur")?.value?.trim();

        if (condition && resultat) {
            regles.push({ condition, valeur, valeurType, resultat, valeurResultat });
        }
    });

    return {
        actif: true,
        regles
    };
}

//  clic sur "Enregistrer règles" => stockage dans localStorage
btn_save?.addEventListener("click", () => {
    const reglesConfig = recupererReglesDepuisFormulaire();
    localStorage.setItem("SANKEY_REGLES", JSON.stringify(reglesConfig));
    alert("Règles enregistrées ✅");
});
