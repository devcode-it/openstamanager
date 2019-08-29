<?php

namespace Models;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Common\Model;
use Hooks\Manager;
use Illuminate\Database\Eloquent\Builder;
use Traits\StoreTrait;

class Hook extends Model
{
    use StoreTrait;

    protected $table = 'zz_hooks';

    protected $appends = [
        'permission',
    ];

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getPermissionAttribute()
    {
        return $this->module ? $this->module->permission : 'rw';
    }

    /**
     * Restituisce le informazioni sull'esecuzione dell'hook.
     *
     * @return array
     */
    public function execute($token)
    {
        $hook = $this->getClass();

        if ($hook->isSingleton() && $token != $this->processing_token) {
            return;
        }

        $result = $hook->manage();

        if ($hook->isSingleton()) {
            $this->unlock($token);
        }

        $result['execute'] = $hook->needsExecution();

        return $result;
    }

    /**
     * Restituisce le informazioni per l'inizializzazione grafica dell'hook.
     *
     * @return array
     */
    public function response()
    {
        $hook = $this->getClass();

        $response = $hook->response();

        return $response;
    }

    /**
     * Imposta il lock sull'hook se non già impostato.
     * Timeout di 10 minuti.
     *
     * @return string|null
     */
    public function lock()
    {
        $hook = $this->getClass();
        if (!$hook->isSingleton()) {
            return true;
        }

        $result = empty($this->processing_at);

        // Forzatura in caso di freeze per più di 10 minuti
        $date = new Carbon($this->processing_at);
        $interval = CarbonInterval::make('10 minutes');
        $date = $date->add($interval);

        $now = new Carbon();
        $result |= $date->greaterThan($now);

        $token = null;
        if ($result) {
            $token = random_string();

            $this->processing_token = $token;
            $this->processing_at = date('Y-m-d H:i:s');
            $this->save();
        }

        return $token;
    }

    /**
     * Rimuove il lock sull'hook.
     *
     * @return string|null
     */
    public function unlock($token)
    {
        if ($token == $this->processing_token) {
            $this->processing_token = null;
            $this->processing_at = null;
            $this->save();
        }
    }

    public function getClass()
    {
        $class = $this->class;
        $hook = new $class();

        if (!$hook instanceof Manager) {
            throw new \UnexpectedValueException();
        }

        return $hook;
    }

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('enabled', function (Builder $builder) {
            $builder->where('enabled', true);
        });
    }
}
