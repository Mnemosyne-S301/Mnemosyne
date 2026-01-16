# Service Filtres - Documentation

## Vue d'ensemble

Le service `Service_filtres` permet de filtrer les étudiants selon différents critères basés sur leurs performances académiques (nombre d'UE validées) et leur statut (réussite/échec).

## Architecture

### Fichiers impliqués

1. **Services/Service_filtres.php** - Service principal contenant la logique métier
2. **Controllers/Controller_api.php** - Endpoints API pour utiliser le service
3. **Content/script/AjouterFiltre.js** - Interface utilisateur pour créer des filtres
4. **Views/view_admin.php** - Page d'administration avec l'interface de filtrage

## Utilisation du Service

### 1. Instanciation

```php
require_once 'Services/Service_filtres.php';
$service = new Service_filtres();
```

### 2. Appliquer un filtre

```php
$resultats = $service->appliquerFiltre(
    'BUT Informatique',  // formation
    2023,                // année scolaire
    'ayant plus de',     // critère
    4,                   // seuil (optionnel)
    'réussite'          // statut
);
```

**Critères disponibles :**
- `"en formation"` - Tous les étudiants de la formation
- `"ayant plus de"` - Étudiants ayant validé plus de N UE (nécessite le paramètre `$seuil`)
- `"ayant moins de"` - Étudiants ayant validé moins de N UE (nécessite le paramètre `$seuil`)

**Statuts disponibles :**
- `"réussite"` - Étudiants en situation de réussite (≥4 UE validées)
- `"échec"` - Étudiants en situation d'échec (<4 UE validées)

### 3. Compter les étudiants filtrés

```php
$total = $service->compterEtudiantsFiltres(
    'BUT Informatique',
    2023,
    'ayant plus de',
    4,
    'réussite'
);
echo "Nombre d'étudiants : $total";
```

### 4. Valider les paramètres

```php
$params = [
    'formation' => 'BUT Informatique',
    'annee' => 2023,
    'critere' => 'ayant plus de',
    'seuil' => 4,
    'statut' => 'réussite'
];

$validation = $service->validerParametresFiltre($params);

if ($validation['valid']) {
    echo "Paramètres valides";
} else {
    print_r($validation['errors']);
}
```

### 5. Formater les résultats

```php
$resultats = $service->appliquerFiltre(...);
$formatted = $service->formaterResultats($resultats);

/*
Retourne :
[
    'total_etudiants' => 150,
    'details' => [
        ['nb_ue' => 6, 'effectif' => 50, 'parcours' => 'Parcours A'],
        ['nb_ue' => 5, 'effectif' => 100, 'parcours' => 'Parcours A']
    ],
    'resume' => [
        'nombre_groupes' => 2,
        'total' => 150
    ]
]
*/
```

## API Endpoints

### POST/GET `/api/appliquer_filtre`

Applique un filtre et retourne les résultats formatés.

**Paramètres :**
- `formation` (string, requis) - Nom de la formation
- `annee` (int, requis) - Année scolaire
- `critere` (string, requis) - Type de critère
- `seuil` (int, optionnel) - Seuil d'UE (requis pour "ayant plus de" et "ayant moins de")
- `statut` (string, optionnel, défaut: "réussite") - Statut recherché

**Réponse en cas de succès :**
```json
{
    "success": true,
    "data": {
        "total_etudiants": 150,
        "details": [...],
        "resume": {...}
    },
    "raw": [...]
}
```

**Réponse en cas d'erreur :**
```json
{
    "success": false,
    "errors": ["Liste des erreurs"]
}
```

### POST/GET `/api/compter_filtres`

Compte le nombre d'étudiants correspondant au filtre.

**Paramètres :** Identiques à `/api/appliquer_filtre`

**Réponse en cas de succès :**
```json
{
    "success": true,
    "count": 150
}
```

## Interface JavaScript

### Fonctions utilitaires exposées

```javascript
// Récupérer tous les filtres configurés dans l'interface
const filtres = window.FiltresUtils.recuperer();

// Appliquer les filtres (à connecter au backend)
window.FiltresUtils.appliquer('BUT Informatique', 2023);

// Réinitialiser tous les filtres
window.FiltresUtils.reinitialiser();
```

### Exemple d'intégration avec Fetch API

```javascript
function appliquerFiltres(formation, annee) {
    const filtres = window.FiltresUtils.recuperer();
    
    filtres.forEach(filtre => {
        fetch('/api/appliquer_filtre', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                formation: formation,
                annee: annee,
                critere: filtre.critere,
                statut: filtre.statut
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Résultats:', data.data);
            } else {
                console.error('Erreurs:', data.errors);
            }
        })
        .catch(error => console.error('Erreur:', error));
    });
}
```

## Notes techniques

### Seuil de réussite

Le seuil de réussite est actuellement fixé à 4 UE validées dans la méthode `appliquerFiltre()`. Ce seuil peut être ajusté selon les règles métier de l'établissement.

### Source des données

Le service utilise `StatsDAO::getNbRepartitionUEADMISParFormation()` qui interroge la table `repartition_notes_par_parcours` créée par le script `stats_database_create.sql`.

### Codes de validation

Les codes considérés comme "validation" dans la base de données sont :
- `ADM` - Admis
- `ADSUP` - Admis supérieur
- `PASD` - Passage avec dette

## Exemples d'utilisation pratiques

### Exemple 1 : Trouver les étudiants en difficulté

```php
$service = new Service_filtres();
$etudiants_difficulte = $service->appliquerFiltre(
    'BUT Informatique',
    2023,
    'ayant moins de',
    3,
    'échec'
);
```

### Exemple 2 : Statistiques de réussite

```php
$service = new Service_filtres();
$total_formation = $service->compterEtudiantsFiltres(
    'BUT Informatique',
    2023,
    'en formation',
    null,
    'réussite'
);

$total_reussite = $service->compterEtudiantsFiltres(
    'BUT Informatique',
    2023,
    'ayant plus de',
    4,
    'réussite'
);

$taux_reussite = ($total_reussite / $total_formation) * 100;
echo "Taux de réussite : " . round($taux_reussite, 2) . "%";
```

## Support et contribution

Pour toute question ou suggestion d'amélioration, veuillez consulter le gestionnaire du projet.
