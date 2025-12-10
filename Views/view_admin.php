<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Connection ...</title>
</head>
<body class="bg-[#0A1E2F]">
    <nav>
        <div class="flex items-center justify-between p-4 bg-[#0E2233]">
            <a href="/" class="text-white text-xl font-bold flex items-center">
                <i class="fas fa-home mr-2"></i> Accueil
            </a>
        </div>
    </nav>
    <div class="flex flex-col items-center justify-center min-h-screen">
        <img src="/Statics/img/logo.png" alt="Logo Mnemosyne" class="mb-16 w-64 h-64">
        <div class="bg-[#102436] p-8 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6 text-center text-white">Connexion Admin</h2>
            <form action="/login" method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-white mb-1">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required class="bg-[#999999] w-full px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600">
                </div>
                <div>
                    <label for="password" class="block text-white mb-1">Mot de passe</label>
                    <input type="password" id="password" name="password" required class="bg-[#999999] w-full px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-600">
                </div>
                <button type="submit" class="w-full bg-[#333333] text-white py-3 my-4 rounded-md hover:bg-blue-700 transition duration-300">Se connecter</button>
            </form>
        </div>
    </div>
    
</body>
</html>