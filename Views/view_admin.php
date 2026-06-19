<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/Content/image/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Espace Admin — Mnemosyne</title>
    <style>
        body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; }
        .fade-in { animation: fadeIn .25s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
        .rules-empty { border: 2px dashed rgba(255,255,255,.12); }
    </style>
</head>

<body class="bg-[#071624] min-h-screen">

    <!-- Navbar -->
    <nav class="sticky top-0 z-50 bg-[#0A1E2F]/95 backdrop-blur border-b border-white/[0.07] shadow-xl">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-6 h-14">
            <div class="flex items-center gap-2 text-sm">
                <a href="/accueil/default" class="flex items-center gap-1.5 text-white/70 hover:text-white transition-colors">
                    <i class="fas fa-home text-[#60A5FA]"></i>
                    <span>Accueil</span>
                </a>
                <i class="fas fa-chevron-right text-white/20 text-xs"></i>
                <span class="text-white font-semibold">Administration</span>
            </div>
            <button id="syncBtn" type="button"
                class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white text-sm font-medium rounded-lg px-4 py-2 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400/40 disabled:opacity-50">
                <i class="fas fa-rotate"></i>
                <span>Synchroniser</span>
            </button>
        </div>
    </nav>

    <!-- Toast notification -->
    <div id="syncMessage" class="hidden fixed top-16 right-4 z-50 max-w-sm w-full px-4 py-3 rounded-lg shadow-xl text-white text-sm fade-in border border-white/10"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

        <!-- Page header -->
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-8 h-8 rounded-lg bg-[#EDB85C]/15 flex items-center justify-center">
                    <i class="fas fa-shield-halved text-[#EDB85C] text-sm"></i>
                </div>
                <h1 class="text-2xl font-bold text-white">Espace Administration</h1>
            </div>
            <p class="text-white/40 text-sm ml-11">Gérez les scénarios de suivi et les comptes administrateurs</p>
        </div>

        <!-- Main layout: 2 columns on large screens -->
        <div class="flex flex-col lg:flex-row gap-6 items-start">

            <!-- ── Left: Scenarios section ── -->
            <div class="flex-1 min-w-0">

                <!-- Section toolbar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-7 bg-[#EDB85C] rounded-full"></div>
                        <h2 class="text-lg font-bold text-white">Scénarios de suivi</h2>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button id="Ajt"
                            class="flex items-center gap-1.5 bg-[#EDB85C] hover:bg-[#f0c76d] active:bg-[#d4a445] text-[#071624] text-sm font-bold rounded-lg px-3.5 py-2 transition-colors shadow-sm">
                            <i class="fas fa-plus text-xs"></i>
                            Nouvelle règle
                        </button>
                        <button id="saveRules"
                            class="flex items-center gap-1.5 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 text-sm font-semibold rounded-lg px-3.5 py-2 transition-colors border border-emerald-600/30">
                            <i class="fas fa-floppy-disk text-xs"></i>
                            Enregistrer
                        </button>
                        <a href="index.php?controller=sankey"
                            class="flex items-center gap-1.5 bg-[#60A5FA]/10 hover:bg-[#60A5FA]/20 text-[#60A5FA] text-sm font-semibold rounded-lg px-3.5 py-2 transition-colors border border-[#60A5FA]/25">
                            <i class="fas fa-chart-sankey text-xs"></i>
                            Voir Sankey
                        </a>
                    </div>
                </div>

                <!-- Status bar -->
                <div class="flex items-center gap-2 min-h-[24px] mb-4">
                    <p id="rulesStatus" class="text-xs text-white/40 italic"></p>
                </div>

                <!-- Empty state (hidden by JS when rules load) -->
                <div id="rulesEmptyState" class="rules-empty rounded-2xl p-10 text-center text-white/30 hidden">
                    <i class="fas fa-filter text-3xl mb-3 block opacity-40"></i>
                    <p class="text-sm">Aucune règle définie.<br>Cliquez sur <strong class="text-white/50">Nouvelle règle</strong> pour commencer.</p>
                </div>

                <!-- Rules form -->
                <form class="w-full space-y-3"></form>
            </div>

            <!-- ── Right: Admins panel ── -->
            <aside class="w-full lg:w-72 xl:w-80 shrink-0 bg-[#0A1E2F] text-white rounded-2xl shadow-xl border border-white/[0.08] overflow-hidden sticky top-20">

                <!-- Panel header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.07]">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-users text-[#60A5FA] text-sm"></i>
                        <h3 class="font-bold text-sm uppercase tracking-wider text-white/80">Administrateurs</h3>
                    </div>
                    <a href="index.php?controller=auth&action=logout"
                       class="flex items-center gap-1.5 text-xs text-red-400/80 hover:text-red-300 bg-red-900/20 hover:bg-red-900/30 px-2.5 py-1.5 rounded-lg transition-colors border border-red-800/20">
                        <i class="fas fa-right-from-bracket"></i>
                        Déconnexion
                    </a>
                </div>

                <!-- Admin list -->
                <div class="divide-y divide-white/[0.05]">
                    <?php if (empty($admins)): ?>
                        <div class="px-5 py-4 text-white/40 text-sm">Aucun administrateur.</div>
                    <?php else: ?>
                        <?php foreach ($admins as $admin): ?>
                            <?php
                                $username = htmlspecialchars($admin->getUsername());
                                $initials = strtoupper(substr($username, 0, 2));
                                $isSelf   = (int) $admin->getId() === (int) ($_SESSION["id"] ?? 0);
                            ?>
                            <div class="flex items-center gap-3 px-5 py-3">
                                <div class="w-8 h-8 rounded-full bg-[#1A3348] flex items-center justify-center text-xs font-bold text-[#60A5FA] border border-[#1E3D5C] shrink-0">
                                    <?= $initials ?>
                                </div>
                                <span class="flex-1 text-sm font-medium text-white/90 truncate"><?= $username ?></span>
                                <?php if (!$isSelf): ?>
                                    <form method="POST" action="index.php?controller=admin&action=delAdmin"
                                          onsubmit="return confirm('Supprimer cet admin ?');" class="inline shrink-0">
                                        <input type="hidden" name="id" value="<?= (int) $admin->getId() ?>">
                                        <button type="submit"
                                                title="Supprimer"
                                                class="w-7 h-7 flex items-center justify-center text-red-400/70 hover:text-red-300 bg-red-900/10 hover:bg-red-900/30 rounded-lg transition-colors border border-red-800/20 hover:border-red-600/30">
                                            <i class="fas fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-[#60A5FA]/70 bg-blue-900/20 px-2 py-0.5 rounded-md border border-blue-800/20">Vous</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Add admin button + form -->
                <div class="px-5 py-4 border-t border-white/[0.07]">
                    <button type="button"
                            onclick="document.getElementById('addAdminBox').classList.toggle('hidden')"
                            class="w-full flex items-center justify-center gap-2 text-[#EDB85C] text-sm font-semibold bg-[#EDB85C]/10 hover:bg-[#EDB85C]/15 rounded-xl px-4 py-2.5 transition-colors border border-[#EDB85C]/20 hover:border-[#EDB85C]/30">
                        <i class="fas fa-user-plus text-xs"></i>
                        Ajouter un administrateur
                    </button>

                    <div id="addAdminBox" class="hidden mt-3 bg-white/[0.04] p-4 rounded-xl border border-white/[0.08] fade-in">
                        <form method="POST" action="index.php?controller=admin&action=addAdmin" class="space-y-3">
                            <div>
                                <label class="block text-xs text-white/50 mb-1.5 font-medium">Nom d'utilisateur</label>
                                <input name="username" type="text" required placeholder="username"
                                       class="w-full rounded-lg px-3 py-2 bg-[#071624] text-white text-sm border border-white/10 focus:outline-none focus:border-[#60A5FA]/50 placeholder-white/20 transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs text-white/50 mb-1.5 font-medium">Mot de passe</label>
                                <input name="password" type="password" required placeholder="••••••••"
                                       class="w-full rounded-lg px-3 py-2 bg-[#071624] text-white text-sm border border-white/10 focus:outline-none focus:border-[#60A5FA]/50 placeholder-white/20 transition-colors">
                            </div>
                            <button type="submit"
                                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm rounded-lg px-4 py-2 transition-colors">
                                Créer le compte
                            </button>
                        </form>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script src="/Content/script/AjouterFiltre.js"></script>
    <script>
    document.getElementById('syncBtn').addEventListener('click', async () => {
        const btn = document.getElementById('syncBtn');
        const box = document.getElementById('syncMessage');

        btn.disabled = true;
        btn.querySelector('span').textContent = 'En cours…';

box.className = 'fixed top-16 right-4 z-50 max-w-sm w-full px-4 py-3 rounded-lg shadow-xl text-white text-sm fade-in border border-blue-900 bg-blue-950';
        box.innerHTML = '<i class="fas fa-rotate fa-spin mr-2"></i>Synchronisation en cours…';

        try {
            const response = await fetch('index.php?controller=admin&action=synchroniser', { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                box.className = 'fixed top-16 right-4 z-50 max-w-sm w-full px-4 py-3 rounded-lg shadow-xl text-white text-sm fade-in border border-green-900 bg-green-950';
                box.innerHTML = '<i class="fas fa-check mr-2"></i>' + (data.message || 'Synchronisation réussie.');
            } else {
                box.className = 'fixed top-16 right-4 z-50 max-w-sm w-full px-4 py-3 rounded-lg shadow-xl text-white text-sm fade-in border border-red-900 bg-red-950';
                box.innerHTML = '<i class="fas fa-xmark mr-2"></i>' +
                    (data.message || 'Erreur pendant la synchronisation.') +
                    (data.json_error ? '<br><span class="opacity-70 text-xs">' + data.json_error + '</span>' : '') +
                    (data.raw_output ? '<br><span class="opacity-70 text-xs">' + data.raw_output.substring(0, 200) + '</span>' : '');
            }
        } catch (error) {
            box.className = 'fixed top-16 right-4 z-50 max-w-sm w-full px-4 py-3 rounded-lg shadow-xl text-white text-sm fade-in border border-red-900 bg-red-950';
            box.innerHTML = '<i class="fas fa-xmark mr-2"></i>Erreur réseau : ' + error.message;
        }

        btn.disabled = false;
        btn.querySelector('span').textContent = 'Synchroniser';
        setTimeout(() => { box.className = 'hidden'; }, 6000);
    });
    </script>
</body>
</html>
