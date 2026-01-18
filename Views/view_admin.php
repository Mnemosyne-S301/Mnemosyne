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
            <a href="index.php?controller=accueil" class="text-white text-xl font-bold flex items-center">
                <i class="fas fa-home mr-2"></i> Accueil
            </a>
            <button class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-2 focus:ring-gray-600 rounded-lg px-4 py-2">Synchroniser</button>
        </div>
    </nav>
    <div class="my-12 flex flex-col items-center justify-start text-white">
        <h1 class="text-4xl font-bold mb-8">Bienvenue dans l'Espace Admin</h1>
        <p class="text-lg">Vous pouvez ajouter des règles et les synchroniser avec ScoDoc</p>
        <div class="mt-8 space-x-4">
        </div>
    </div>
    <h2 class="flex justify-center text-3xl text-left font-bold my-10 text-white">Liste des filtres</h2>
    <div class="flex justify-start items-center my-12 mx-8">
        <button id="Ajt" class="text-white bg-[#EDB85C] hover:bg-[#E3BF81] focus:ring-2 focus:ring-gray-600 rounded-lg px-4 py-2 mx-4">Ajouter Filtre</button>
        <button id="Supp" class="text-white bg-red-700 hover:bg-red-800 focus:ring-2 focus:ring-gray-600 rounded-lg px-4 py-2 mx-4">Supprimer Filtre</button>
    </div>
    <form class="mx-8 my-12 w-1/2">
    </form>
</body>
<script src="/Content/script/AjouterFiltre.js"></script>
</html>