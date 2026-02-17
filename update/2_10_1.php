<?php

include __DIR__.'/../config.inc.php';
use Carbon\Carbon;
use Models\Cache;

// 1. Sblocca tutti gli hooks che potrebbero essere bloccati
$unlocked_hooks = $database->table('zz_hooks')
    ->update([
        'processing_at' => null,
        'processing_token' => null,
    ]);

// 2. Invalida tutte le cache degli hooks impostando la scadenza nel passato
$cache_affected = $database->table('zz_cache')
    ->update(['expire_at' => Carbon::now()->subMinutes(1)]);

// 3. Svuota il contenuto delle cache specifiche degli hooks
$hook_caches = [
    'Informazioni su Services',
    'Spazio utilizzato',
    'Ultima versione di OpenSTAManager disponibile',
    'Ricevute Elettroniche',
    'Ultima esecuzione del cron',
];

$content_cleared = 0;
foreach ($hook_caches as $cache_name) {
    $cache = Cache::where('name', $cache_name)->first();
    if ($cache) {
        $cache->content = null;
        $cache->expire_at = Carbon::now()->subMinutes(1);
        $cache->save();
        ++$content_cleared;
    }
}

// 4. Rimuovi fisicamente le cache scadute (solo quelle temporanee)
$deleted_rows = $database->table('zz_cache')
    ->where('expire_at', '<', Carbon::now())
    ->whereNull('valid_time')
    ->delete();