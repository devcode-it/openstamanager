<?php

/**
 * Funzioni comuni per la gestione dei tour guidati
 * Salva e recupera lo stato dei tour completati nel database
 */
if (! defined('DOCROOT') && ! defined('ROOTDIR')) {
    return;
}

/**
 * Normalizza l'elenco dei tour completati.
 */
function normalizeCompletedTours($tours): array
{
    if (! is_array($tours)) {
        return [];
    }

    $normalized_tours = [];

    foreach ($tours as $tour) {
        if (is_numeric($tour)) {
            $normalized_tours[] = (int) $tour;
        }
    }

    return array_values(array_unique($normalized_tours));
}

/**
 * Decodifica le opzioni utente mantenendo intatte le altre impostazioni.
 */
function decodeUserTourOptions(?string $options_json): array
{
    $options_data = json_decode($options_json ?: '{}', true);

    if (json_last_error() !== JSON_ERROR_NONE || ! is_array($options_data)) {
        $options_data = [];
    }

    $options_data['tours'] = normalizeCompletedTours($options_data['tours'] ?? []);

    return $options_data;
}

/**
 * Recupera le opzioni dell'utente per la gestione dei tour.
 */
function getUserTourOptions(int $id_user): ?array
{
    global $dbo;

    $options = $dbo->fetchOne('SELECT `options` FROM `zz_users` WHERE `id` = '.prepare($id_user));

    if (empty($options)) {
        return null;
    }

    return decodeUserTourOptions($options['options'] ?? null);
}

/**
 * Salva un tour come completato nel database
 *
 * @return bool Successo dell'operazione
 */
function saveTourCompleted(int $id_module): bool
{
    global $dbo;

    // Ottieni ID utente corrente
    $id_user = $_SESSION['id_utente'] ?? null;
    if (! $id_user) {
        return false;
    }

    $options_data = getUserTourOptions((int) $id_user);

    if ($options_data === null) {
        return false;
    }

    // Mantieni l'ID del modulo come numero intero per coerenza
    $id_module_int = (int) $id_module;
    if (! in_array($id_module_int, $options_data['tours'], true)) {
        $options_data['tours'][] = $id_module_int;
    }

    // Salva le opzioni aggiornate con JSON_UNESCAPED_SLASHES
    $options_json = json_encode($options_data, JSON_UNESCAPED_SLASHES);

    if ($options_json === false) {
        return false;
    }

    $result = $dbo->query('UPDATE `zz_users` SET `options` = '.prepare($options_json).' WHERE `id` = '.prepare($id_user));

    return (bool) $result;
}

/**
 * Verifica se un tour è stato completato
 *
 * @return bool True se il tour è completato, false altrimenti
 */
function isTourCompleted(int $id_module): bool
{
    // Ottieni ID utente corrente
    $id_user = $_SESSION['id_utente'] ?? null;
    if (! $id_user) {
        return false;
    }

    $options_data = getUserTourOptions((int) $id_user);

    if ($options_data === null) {
        return false;
    }

    // Converti l'ID del modulo a numero intero per il confronto
    $id_module_int = (int) $id_module;

    return in_array($id_module_int, $options_data['tours'] ?? [], true);
}
