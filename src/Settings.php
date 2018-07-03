<?php

/**
 * Classe per la gestione dell impostazioni del progetto.
 *
 * @since 2.3
 */
class Settings
{
    /** @var array Elenco delle impostazioni ottenute */
    protected static $values = [];

    /**
     * Restituisce il valore corrente dell'impostazione ricercata.
     * Se l'impostazione viene cercata più volte, il primo valore individuato viene salvato; per costringere a aggiornare i contenuto, usare l'opzione $again.
     *
     * @param string $nome
     * @param string $sezione
     * @param string $descrizione
     * @param bool   $again
     *
     * @return string
     */
    public static function get($nome, $sezione = null, $descrizione = false, $again = false)
    {
        if (Update::isUpdateAvailable()) {
            return null;
        }

        if (empty(self::$values[$sezione.'.'.$nome]) || !empty($again)) {
            $database = Database::getConnection();

            if (!$database->isInstalled()) {
                return null;
            }

            $query = 'SELECT valore, tipo FROM zz_settings WHERE nome='.prepare($nome);
            if (!empty($sezione)) {
                $query .= ' AND sezione='.prepare($sezione);
            }
            $results = $database->fetchArray($query);

            $value = null;
            if (!empty($results)) {
                $result = $results[0];
                $value = $result['valore'];

                if (!empty($descrizione) && str_contains($result['tipo'], 'query=')) {
                    $data = $database->fetchArray(str_replace('query=', '', $result['tipo']));
                    if (!empty($data)) {
                        $value = $data[0]['descrizione'];
                    }
                }
            }

            self::$values[$sezione.'.'.$nome] = $value;
        }

        return self::$values[$sezione.'.'.$nome];
    }

    public static function set($name, $value)
    {
        $database = Database::getConnection();

        $database->update('zz_settings', [
            'valore' => $value,
        ], [
            'nome' => $name,
        ]);

        self::get($nome, null, null, true);
    }
}
