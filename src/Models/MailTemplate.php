<?php

namespace Models;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Traits\StoreTrait;

class MailTemplate extends Model
{
    use StoreTrait;
    use SoftDeletes;

    protected $table = 'em_templates';

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
        return $this->belongsTo(MailAccount::class, 'id_smtp');
    }
}
