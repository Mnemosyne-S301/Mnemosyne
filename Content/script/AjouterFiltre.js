const btn_ajt = document.getElementById("Ajt");
const btn_supp = document.getElementById("Supp");
const divp = document.body.querySelector("form");

/**
 * Crée un nouvel élément de filtre dans le formulaire
 */
btn_ajt.addEventListener("click", () => {
    const div = document.createElement("div");
    const filtre = document.createElement("label");
    const select = document.createElement("select");
    const option1 = document.createElement("option");
    const option2 = document.createElement("option");
    const option3 = document.createElement("option");
    const option4 = document.createElement("option");
    const option5 = document.createElement("option");
    const filtre2 = document.createElement("label");
    const select2 = document.createElement("select");

    // Classes CSS pour le style
    div.className = "flex justify-center items-center my-6 bg-[#12C3C] p-4 rounded-lg d";
    select.className = "placeholder-gray-700 bg-[#999999] w-full mx-6 py-4 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600 filtre-critere";
    select2.className = "placeholder-gray-700 bg-[#999999] w-full mx-6 py-4 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600 filtre-statut";
    
    // Configuration du premier select (critère)
    filtre.textContent = "Les étudiants ";
    filtre.className = "text-white font-medium";
    
    option1.textContent = "en formation ...";
    option1.value = "en formation";
    option2.textContent = "ayant plus de ...";
    option2.value = "ayant plus de";
    option3.textContent = "ayant moins de ...";
    option3.value = "ayant moins de";

    select.append(option1);
    select.append(option2);
    select.append(option3);

    div.append(filtre);
    div.append(select);

    // Configuration du second select (statut)
    filtre2.textContent = "sont en ";
    filtre2.className = "text-white font-medium";
    
    option4.textContent = "réussite";
    option4.value = "réussite";
    option5.textContent = "échec";
    option5.value = "échec";

    select2.append(option4);
    select2.append(option5);

    div.append(filtre2);
    div.append(select2);
    
    divp.appendChild(div);
});

/**
 * Supprime le dernier filtre ajouté
 */
btn_supp.addEventListener("click", () => {
    const divs = document.querySelectorAll(".d");
    if(divs.length > 0){
        divs[divs.length - 1].remove();
    }
});

/**
 * Récupère tous les filtres actuellement configurés
 * @returns {Array} Tableau d'objets représentant les filtres
 */
function recupererFiltres() {
    const filtres = [];
    const divs = document.querySelectorAll(".d");
    
    divs.forEach((div, index) => {
        const critere = div.querySelector(".filtre-critere");
        const statut = div.querySelector(".filtre-statut");
        
        if (critere && statut) {
            filtres.push({
                id: index + 1,
                critere: critere.value,
                statut: statut.value
            });
        }
    });
    
    return filtres;
}

/**
 * Applique les filtres configurés (fonction à connecter au backend)
 * @param {string} formation - Le nom de la formation
 * @param {number} annee - L'année scolaire
 */
function appliquerFiltres(formation, annee) {
    const filtres = recupererFiltres();
    
    if (filtres.length === 0) {
        console.warn("Aucun filtre configuré");
        return;
    }
    
    console.log("Filtres à appliquer:", filtres);
    
    // TODO: Envoyer les filtres au backend via AJAX/Fetch
    // Example:
    // fetch('/api/filtres/appliquer', {
    //     method: 'POST',
    //     headers: { 'Content-Type': 'application/json' },
    //     body: JSON.stringify({ formation, annee, filtres })
    // })
    // .then(response => response.json())
    // .then(data => console.log("Résultats:", data))
    // .catch(error => console.error("Erreur:", error));
}

/**
 * Réinitialise tous les filtres
 */
function reinitialiserFiltres() {
    const divs = document.querySelectorAll(".d");
    divs.forEach(div => div.remove());
}

// Exposer les fonctions utilitaires globalement si nécessaire
window.FiltresUtils = {
    recuperer: recupererFiltres,
    appliquer: appliquerFiltres,
    reinitialiser: reinitialiserFiltres
};

