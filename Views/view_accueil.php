<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Mnemosyne — Accueil</title>

    <!-- Importation de TailwindCSS depuis un CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="h-screen overflow-hidden bg-[#0A1E2F] flex flex-col">

    <!-- ============================
         HEADER : Logo + Slogan
    ============================= -->
    <header class="flex flex-col items-center justify-center gap-4 py-8">

        <!-- Logo principal -->
        <img id="logo"
             src="/Content/image/logo.png"
             alt="Logo Mnemosyne"
             class="w-72 h-72 object-contain" />

        <!-- Slogan -->
        <h2 class="text-[#FBEDD3] text-2xl font-semibold">
            Gardez la mémoire, éclairez les parcours
        </h2>
    </header>

    <!-- ============================
         MAIN :  Formulaire centré
    ============================= -->
    <main class="flex items-center justify-center flex-1">

        <!-- Formulaire principal -->
        <form action="index.php"
              method="get"
              class="flex flex-col gap-4 w-96">
            
            <!-- Champ caché pour le contrôleur -->
            <input type="hidden" name="controller" value="sankey">

            <!-- ----------------------------
                 Champ : Choix de formation
            ----------------------------- -->
            <div id="form-formation" class="flex flex-col gap-2">

                <!-- Label -->
                <label class="font-semibold text-[#FBEDD3]">
                    Formation:
                </label>

                <!-- Sélecteur de formation (dynamique) -->
                <select name="formation" 
                        id="selectFormation"
                        class="appearance-none block w-full bg-neutral-secondary-medium text-[#999999]
                               border-default-medium text-heading text-sm rounded-lg
                               focus:ring-brand focus:border-brand px-3 py-2.5 shadow-xs
                               text-fg-disabled bg-[#88888880]"
                        onchange="updateAnnees()">
                    <option value="">-- Sélectionnez une formation --</option>
                    <?php if (isset($formations) && !empty($formations)): ?>
                        <?php foreach ($formations as $formation): ?>
                            <option value="<?= htmlspecialchars($formation['nom']) ?>" 
                                    data-annees='<?= json_encode($formation['annees']) ?>'>
                                <?= htmlspecialchars($formation['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- ----------------------------
                 Champ : Année scolaire
            ----------------------------- -->
            <div id="form-années" class="flex flex-col gap-2">

                <!-- Label -->
                <label class="font-semibold text-[#FBEDD3]">
                    Année:
                </label>

                <!-- Sélecteur d'années (mis à jour dynamiquement) -->
                <select name="anneeDepart"
                        id="selectAnnee"
                        class="appearance-none block text-[#999999] w-full bg-neutral-secondary-medium
                               border-default-medium text-heading text-sm rounded-lg
                               focus:ring-brand focus:border-brand px-3 py-2.5 shadow-xs
                               text-fg-disabled bg-[#88888880]">
                    <option value="">-- Sélectionnez d'abord une formation --</option>
                </select>
            </div>

            <!-- ----------------------------
                 Bouton d'envoi du formulaire
            ----------------------------- -->
            <input type="submit"
                   value="Confirmer"
                   class="mt-4 p-2 bg-[#FBEDD3] text-[#091D2F] rounded-lg
                          hover:bg-[#E3BF81] cursor-pointer font-semibold" />
        </form>

        <!-- ============================
             BOUTON ADMIN (collé en bas)
        ============================= -->
        <a href="/Views/view_login.php">
            <input id="logo_admin"
                type="image"
                src="/Content/image/connexion_admin.png"
                class="fixed bottom-0 right-0 w-32 h-32
                        rounded-tl-3xl object-cover cursor-pointer
                        backdrop-blur-md bg-[#FFFFFF0A] shadow-2xl
                        transition-all duration-300 hover:scale-105 hover:opacity-90" />
        </a>

    </main>

    <!-- ============================
         SCRIPT :  Mise à jour dynamique des années
    ============================= -->
    <script>
        function updateAnnees() {
            const selectFormation = document.getElementById('selectFormation');
            const selectAnnee = document.getElementById('selectAnnee');
            
            // Récupérer l'option sélectionnée
            const selectedOption = selectFormation.options[selectFormation.selectedIndex];
            
            // Récupérer les années depuis l'attribut data-annees
            const anneesJSON = selectedOption.getAttribute('data-annees');
            
            // Vider le select des années
            selectAnnee. innerHTML = '';
            
            if (anneesJSON) {
                const annees = JSON.parse(anneesJSON);
                
                if (annees.length > 0) {
                    // Ajouter les options d'années
                    annees.forEach(annee => {
                        const option = document. createElement('option');
                        option.value = annee;
                        option.textContent = annee + '-' + (annee + 1);
                        selectAnnee.appendChild(option);
                    });
                } else {
                    // Aucune année disponible
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = '-- Aucune année disponible --';
                    selectAnnee.appendChild(option);
                }
            } else {
                // Aucune formation sélectionnée
                const option = document.createElement('option');
                option.value = '';
                option.textContent = '-- Sélectionnez d\'abord une formation --';
                selectAnnee.appendChild(option);
            }
        }
    </script>

</body>
</html>