<?php
/**
 * Service_filtres
 *
 * Petite couche de persistance pour enregistrer/charger les règles Sankey.
 * Stocke un JSON dans `config/sankey_rules.json`.
 */
class Service_filtres {
    private string $filePath;

    public function __construct() {
        $this->filePath = __DIR__ . '/../config/sankey_rules.json';
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!file_exists($this->filePath)) {
            // fichier par défaut
            @file_put_contents($this->filePath, json_encode(['actif' => false, 'regles' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Retourne la configuration des règles
     * @return array
     */
    public function getRules(): array {
        $content = @file_get_contents($this->filePath);
        if ($content === false) return ['actif' => false, 'regles' => []];
        $data = json_decode($content, true);
        if (!is_array($data)) return ['actif' => false, 'regles' => []];
        return $data;
    }

    /**
     * Sauvegarde la configuration des règles (écrase le fichier)
     * @param array $rules
     * @return bool
     */
    public function saveRules(array $rules): bool {
        $json = json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) return false;
        $res = @file_put_contents($this->filePath, $json);
        return $res !== false;
    }
}
?>