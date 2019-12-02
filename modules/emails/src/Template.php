<?php

namespace Modules\Emails;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\Module;
use Models\PrintTemplate;
use Traits\StoreTrait;

class Template extends Model
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
        return $this->belongsTo(Account::class, 'id_account')->withTrashed();
    }

    public function prints()
    {
        return $this->belongsToMany(PrintTemplate::class, 'em_print_template', 'id_template', 'id_print');
    }
}
