<?php

/*
 * Implementazione conservativa di rate limiting per OpenSTAManager.
 * Usa Illuminate\Cache\RateLimiter quando disponibile; in caso contrario
 * applica un fallback file-based compatibile con i parametri configurati.
 *
 * Requisiti (modalità nativa):
 *  - illuminate/cache:^10.0
 *  - illuminate/filesystem:^10.0
 */

namespace Security;

class LaravelRateLimiter
{
    /**
     * Applica il rate limiting per una determinata "area" (es. 'api').
     *
     * @param string $area   Area logica (es. 'api')
     * @param array  $config Configurazione completa di OSM (incluso $rate_limiting)
     * @param array  $opts   Opzioni aggiuntive (es. ['key_parts' => ['resource' => ..., 'token' => ...]])
     *
     * @return array [bool $allowed, int $retryAfterSeconds]
     */
    public static function enforce(string $area, array $config, array $opts = []): array
    {
        $cfg = $config['rate_limiting'] ?? [];

        $ip = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        // Whitelist IP: sempre consentito
        $whitelist = (array) ($cfg['whitelist_ips'] ?? []);
        if (in_array($ip, $whitelist, true)) {
            return [true, 0];
        }

        // Blacklist IP: sempre bloccato
        $blacklist = (array) ($cfg['blacklist_ips'] ?? []);
        if (in_array($ip, $blacklist, true)) {
            return [false, 0];
        }

        // Costruzione chiave e limiti
        [$key, $max, $decay, $storePath] = self::buildKeyAndLimits($area, $cfg, $ip, $opts);

        // Prova ad usare il RateLimiter nativo di Illuminate
        if (
            class_exists(\Illuminate\Cache\RateLimiter::class)
            && class_exists(\Illuminate\Cache\Repository::class)
            && class_exists(\Illuminate\Cache\FileStore::class)
            && class_exists(\Illuminate\Filesystem\Filesystem::class)
        ) {
            try {
                if (!is_dir($storePath)) {
                    @mkdir($storePath, 0777, true);
                }
                $files = new \Illuminate\Filesystem\Filesystem();
                $store = new \Illuminate\Cache\FileStore($files, $storePath);
                $repo = new \Illuminate\Cache\Repository($store);
                $rl = new \Illuminate\Cache\RateLimiter($repo);

                if ($rl->tooManyAttempts($key, $max)) {
                    return [false, $rl->availableIn($key)];
                }

                $rl->hit($key, $decay);

                return [true, 0];
            } catch (\Throwable) {
                // In caso di problemi con Illuminate, prosegue col fallback
            }
        }

        // Fallback file-based (compatibile con max/decay)
        return self::fallbackEnforce($storePath, $key, $max, $decay);
    }

    /**
     * Costruisce chiave, limiti e percorso store (schema authenticated/unauthenticated).
     */
    private static function buildKeyAndLimits(string $area, array $cfg, string $ip, array $opts): array
    {
        $__unused = $opts;
        unset($__unused);

        // Determina utente autenticato (se presente)
        $userId = null;
        if (class_exists('Auth')) {
            try {
                $u = auth_osm()->getUser();
                if ($u && isset($u->id)) {
                    $userId = (int) $u->id;
                }
            } catch (\Throwable) {
                // ignora
            }
        }

        $limitsArea = (array) ($cfg['limits'][$area] ?? []);

        // Limiti distinti per authenticated/unauthenticated
        if ($userId) {
            $max = (int) ($limitsArea['authenticated']['max'] ?? 300);
            $decay = (int) ($limitsArea['authenticated']['decay'] ?? 60);
            $key = 'osm:rate:'.$area.':user:'.$userId;
        } else {
            $max = (int) ($limitsArea['unauthenticated']['max'] ?? 60);
            $decay = (int) ($limitsArea['unauthenticated']['decay'] ?? 300);
            $key = 'osm:rate:'.$area.':ip:'.$ip;
        }

        $storePath = (string) ($cfg['store_path'] ?? (function_exists('base_dir') ? base_dir().'/files/cache/ratelimiter' : __DIR__.'/../../files/cache/ratelimiter'));

        return [$key, $max, $decay, $storePath];
    }

    /**
     * Fallback semplice su file (contatore per finestra temporale), con lock.
     */
    private static function fallbackEnforce(string $storePath, string $key, int $max, int $decay): array
    {
        if (!is_dir($storePath)) {
            @mkdir($storePath, 0777, true);
        }
        $file = rtrim($storePath, '/\\').DIRECTORY_SEPARATOR.strtr($key, [':' => '_']).'.json';
        $now = time();
        $data = ['count' => 0, 'start' => $now];

        $h = @fopen($file, 'c+');
        if ($h === false) {
            // Se non posso accedere allo store, non blocco (fail-open conservativo)
            return [true, 0];
        }

        try {
            @flock($h, LOCK_EX);
            $contents = stream_get_contents($h);
            if ($contents) {
                $decoded = json_decode($contents, true);
                if (is_array($decoded) && isset($decoded['count'], $decoded['start'])) {
                    $data = $decoded;
                }
            }

            // Reset finestra se scaduta
            if (($now - (int) $data['start']) >= $decay) {
                $data = ['count' => 0, 'start' => $now];
            }

            if ((int) $data['count'] >= $max) {
                $retry = max(0, $decay - ($now - (int) $data['start']));

                return [false, $retry];
            }

            // Incremento e salvo
            $data['count'] = (int) $data['count'] + 1;
            ftruncate($h, 0);
            rewind($h);
            fwrite($h, json_encode($data));

            return [true, 0];
        } finally {
            @flock($h, LOCK_UN);
            @fclose($h);
        }
    }
}
