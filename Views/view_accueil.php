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
        <body>
    <div>
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
    </div>
    </body>
    <style>
@keyframes gradient {
    0% {
        background-position: 0% 0%;
    }
    50% {
        background-position: 100% 100%;
    }
    100% {
        background-position: 0% 0%;
    }
}

.wave {
    background: rgb(255 255 255 / 5%);
    border-radius: 1000% 1000% 0 0;
    position: fixed;
    width: 200%;
    height: 12em;
    animation: wave 10s -3s linear infinite;
    transform: translate3d(0, 0, 0);
    opacity: 0.8;
    bottom: 0;
    left: 0;
    z-index: -1;
}

.wave:nth-of-type(2) {
    bottom: -1.25em;
    animation: wave 18s linear reverse infinite;
    opacity: 0.8;
}

.wave:nth-of-type(3) {
    bottom: -2.5em;
    animation: wave 20s -1s reverse infinite;
    opacity: 0.9;
}

@keyframes wave {
    2% {
        transform: translateX(1);
    }

    25% {
        transform: translateX(-25%);
    }

    50% {
        transform: translateX(-50%);
    }

    75% {
        transform: translateX(-25%);
    }

    100% {
        transform: translateX(1);
    }
}
    </style>

    <!-- ============================
         MAIN : Formulaire centré e
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

                <!-- Sélecteur de formation -->
                <select name=formation 
                        class="appearance-none block w-full bg-neutral-secondary-medium text-[#999999]
                               border-default-medium text-heading text-sm rounded-lg
                               focus:ring-brand focus:border-brand px-3 py-2.5 shadow-xs
                               text-fg-disabled bg-[#88888880]">
                    <?php
                        $defaultFormation = 'GEA';
                        if(!empty($formationArray)) {
                            foreach($formationArray as $formation){
                                $accronyme = htmlspecialchars($formation['accronyme'] ?? $formation['titre']);
                                $titre = htmlspecialchars($formation['titre'] ?? $formation['accronyme']);
                                $selected = ($accronyme === $defaultFormation) ? 'selected' : '';
                                echo '<option value="'.$accronyme.'" '.$selected.'>'.$titre.' (' . $accronyme . ')</option>';
                            }
                        }
                        else{
                            echo '<option>Aucune Formation </option>';
                        }
                    ?>
                </select>
            </div>

            <!-- ----------------------------
                 Champ : Année scolaire
            ----------------------------- -->
            <div id="form-années" class="flex flex-col gap-2">

                <!-- Label -->
                <label class="font-semibold text-[#FBEDD3]">
                    Année de départ:
                </label>

                <!-- Sélecteur d'années (PHP générant les options) -->
                <select name="anneeDepart"
                        class="appearance-none block text-[#999999] w-full bg-neutral-secondary-medium
                               border-default-medium text-heading text-sm rounded-lg
                               focus:ring-brand focus:border-brand px-3 py-2.5 shadow-xs
                               text-fg-disabled bg-[#88888880]">

                    <?php
                        // Génère les années 2021 jusqu'à 2024 (années avec données disponibles)
                        $defaultYear = 2023;
                        for ($y = 2021; $y <= 2024; $y++) {
                            $selected = ($y === $defaultYear) ? 'selected' : '';
                            echo '<option value="' . $y . '" ' . $selected . '>' . $y . '-' . ($y + 1) . '</option>';
                        }
                    ?>
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
        <a href="index.php?controller=auth&action=login">
            <input id="logo_admin"
                type="image"
                src="/Content/image/connexion_admin.png"
                class="fixed bottom-0 right-0 w-32 h-32
                        rounded-tl-3xl object-cover cursor-pointer
                        backdrop-blur-md bg-[#FFFFFF0A] shadow-2xl
                        transition-all duration-300 hover:scale-105 hover:opacity-90" />
        </a>

    </main>

</body>
</html>
