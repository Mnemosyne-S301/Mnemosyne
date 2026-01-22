<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Espace Admin</title>
</head>

<body class="bg-[#0A1E2F]">
    <nav>
        <div class="flex items-center justify-between p-4 bg-[#0E2233] shadow-lg">
            <a href="/accueil/default" class="text-white text-xl font-bold flex items-center">
                <i class="fas fa-home mr-2"></i> Accueil
            </a>
            <button class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-2 focus:ring-gray-600 rounded-lg px-4 py-2">
                Synchroniser
            </button>
        </div>
    </nav>

    <!-- Layout: contenu principal + panneau admins -->
    <div class="flex gap-8 mx-8 my-10">
        <!-- Contenu principal -->
        <div class="flex-1">
            <div class="my-12 flex flex-col items-center justify-start text-white">
                <h1 class="text-4xl font-bold mb-8">Bienvenue dans l'Espace Admin</h1>
                <p class="text-lg">Vous pouvez ajouter des règles et les synchroniser avec ScoDoc</p>
                <div class="mt-8 space-x-4"></div>
            </div>

            <h2 class="flex justify-center text-3xl text-left font-bold my-10 text-white">Liste des filtres</h2>

            <div class="flex justify-start items-center my-12">
                <button id="Ajt" class="text-white bg-[#EDB85C] hover:bg-[#E3BF81] focus:ring-2 focus:ring-gray-600 rounded-lg px-4 py-2 mx-4">
                    Ajouter Filtre
                </button>
                <button id="Supp" class="text-white bg-red-700 hover:bg-red-800 focus:ring-2 focus:ring-gray-600 rounded-lg px-4 py-2 mx-4">
                    Supprimer Filtre
                </button>
                <button id="saveRules" class="text-white bg-green-700 hover:bg-green-800 focus:ring-2 focus:ring-gray-600 rounded-lg px-4 py-2 mx-4">
                    Enregistrer règles
                </button>
            </div>

            <form class="my-12 w-full max-w-5xl"></form>
        </div>

        <!-- Panneau admins à droite -->
        <aside class="w-full max-w-sm bg-[#0E2233] text-white rounded-xl shadow-lg p-5 h-fit sticky top-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">Admins</h3>
                <a href="index.php?controller=auth&action=logout"
                   class="text-xs bg-gray-700 hover:bg-gray-800 px-3 py-2 rounded-lg">
                    Déconnexion
                </a>
            </div>

            <div class="overflow-hidden rounded-lg border border-white/10">
                <table class="w-full text-sm">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="text-left p-3 font-semibold">Nom</th>
                            <th class="text-right p-3 font-semibold">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr class="border-t border-white/10">
                                <td class="p-3" colspan="2">Aucun admin.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr class="border-t border-white/10">
                                    <td class="p-3">
                                        <?= htmlspecialchars($admin->getUsername()) ?>
                                    </td>
                                    <td class="p-3 text-right">
                                        <form method="POST"
                                              action="index.php?controller=admin&action=delAdmin"
                                              onsubmit="return confirm('Supprimer cet admin ?');"
                                              class="inline">
                                            <input type="hidden" name="id" value="<?= (int)$admin->getId() ?>">
                                            <button type="submit"
                                                    class="bg-red-700 hover:bg-red-800 px-3 py-1.5 rounded-lg text-xs">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <button type="button"
                    class="mt-4 w-full bg-[#EDB85C] hover:bg-[#E3BF81] text-[#0A1E2F] font-semibold rounded-lg px-4 py-2"
                    onclick="document.getElementById('addAdminBox').classList.toggle('hidden')">
                + Ajouter admin
            </button>

            <div id="addAdminBox" class="hidden mt-4 bg-white/5 p-4 rounded-lg border border-white/10">
                <form method="POST" action="index.php?controller=admin&action=addAdmin" class="space-y-3">
                    <div>
                        <label class="block text-xs mb-1 opacity-90">Username</label>
                        <input name="username" type="text" required
                               class="w-full rounded-lg px-3 py-2 text-black focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs mb-1 opacity-90">Mot de passe</label>
                        <input name="password" type="password" required
                               class="w-full rounded-lg px-3 py-2 text-black focus:outline-none">
                    </div>

                    <button type="submit"
                            class="w-full bg-blue-700 hover:bg-blue-800 rounded-lg px-4 py-2">
                        Créer
                    </button>
                </form>
            </div>
        </aside>
    </div>

    <script src="/Content/script/AjouterFiltre.js"></script>
</body>
</html>
