<?php

namespace Models;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Traits\StoreTrait;

class MailTemplate extends Model
{
    use StoreTrait, SoftDeletes;

    protected $table = 'zz_emails';

    public function getVariablesAttribute()
    {
        $dbo = $database = database();

        // Lettura delle variabili del modulo collegato
        $variables = include $this->module->filepath('variables.php');

        return (array) $variables;
    }

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function account()
    {
        return $this->hasOne(MailAccount::class, 'id_smtp');
    }
}
