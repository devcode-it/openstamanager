<?php

namespace Models;

use Common\Model;
use Traits\NoteTrait;

class Note extends Model
{
    protected $table = 'zz_notes';

    /**
     * Crea una nuova nota.
     *
     * @param NoteTrait $structure
     * @param int       $id_record
     * @param string    $contenuto
     * @param string    $data_scadenza
     *
     * @return self
     */
    public static function build(User $user, $structure, $id_record, $contenuto, $data_notifica = null)
    {
        $model = parent::build();

        $model->user()->associate($user);

        if ($structure instanceof Module) {
            $model->module()->associate($structure);
        } elseif ($structure instanceof Plugin) {
            $model->plugin()->associate($structure);
        }

        $model->id_record = $id_record;

        $model->content = $contenuto;
        $model->notification_date = $data_notifica;

        $model->save();

        return $model;
    }

    /* Relazioni Eloquent */

    public function user()
    {
        return $this->belongsTo(User::class, 'id_utente');
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'id_plugin');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }
}
