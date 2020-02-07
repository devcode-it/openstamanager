<?php

namespace Hooks;

use Carbon\Carbon;
use Carbon\CarbonInterval;

abstract class CachedManager extends Manager
{
    protected static $cache = null;
    protected static $is_cached = null;

    abstract public function data();

    public function needsExecution()
    {
        return !self::isCached();
    }

    public function execute()
    {
        if (self::isCached()) {
            $results = self::getCache()['results'];
        } else {
            $results = $this->data();

            self::update($results);
        }

        return $results;
    }

    public static function getCache()
    {
        if (!isset(self::$cache)) {
            $hook = self::getHook();

            $cache = database()->selectOne('zz_hook_cache', '*', ['hook_id' => $hook->id], ['id' => 'DESC']);

            // Interpretazione della cache
            if (isset($cache['results'])) {
                $cache['results'] = json_decode($cache['results'], true);
            }

            self::$cache = $cache;
        }

        return self::$cache;
    }

    public static function update($results)
    {
        $hook = self::getHook();

        if( !empty($hook) ){
            // Rimozione cache precedente
            $database = database();
            $database->delete('zz_hook_cache', [
                'hook_id' => $hook->id,
            ]);

            // Aggiunta del risultato come cache
            $cache = json_encode($results);
            $database->insert('zz_hook_cache', [
                'hook_id' => $hook->id,
                'results' => $cache,
            ]);

            self::$cache = $results;
            self::$is_cached = null;
        }
    }

    public static function isCached()
    {
        if (!isset(self::$is_cached)) {
            $hook = self::getHook();
            $cache = self::getCache();

            $is_cached = false;
            if (!empty($cache)) {
                $date = new Carbon($cache['created_at']);
                $interval = CarbonInterval::make($hook->frequency);

                $date = $date->add($interval);

                $now = new Carbon();
                $is_cached = $date->greaterThan($now);
            }

            self::$is_cached = $is_cached;
        }

        return self::$is_cached;
    }
}
