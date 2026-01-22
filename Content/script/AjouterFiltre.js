const btn_ajt = document.getElementById("Ajt");
const btn_supp = document.getElementById("Supp");
const divp = document.body.querySelector("form");


const btn_save = document.getElementById("saveRules");

btn_ajt.addEventListener("click", () => {
    const div = document.createElement("div");
    const filtre = document.createElement("label");
    const select = document.createElement("select");
    const option1 = document.createElement("option");
    const option2 = document.createElement("option");
    const option3 = document.createElement("option");
    const option4 = document.createElement("option");
    const option5 = document.createElement("option");

    const inputCondition = document.createElement("input");
    const filtre2 = document.createElement("label");
    const select2 = document.createElement("select");


    div.className = "flex justify_center text-white items-center my-6 bg-[#122738] p-4 rounded-lg d";
    select.className = "placeholder-gray-700 bg-[#999999] w-full mx-6 py-4 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600";
    select2.className = "placeholder-gray-700 bg-[#999999] w-full mx-6 py-4 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600";
    inputCondition.className = "placeholder-white bg-[#999999] w-full mx-6 py-4 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600";

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

    inputCondition.type = "text";
    inputCondition.placeholder = "Préciser...";


    select.append(option1);
    select.append(option2);
    select.append(option3);

    div.append(filtre);
    div.append(select);
    div.append(inputCondition);

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
        const valeurResultat = div.querySelector(".regle-resultat-valeur")?.value?.trim();

        if (condition && resultat) {
            regles.push({ condition, valeur, resultat, valeurResultat });
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
