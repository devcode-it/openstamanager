<?php

namespace Models;

use Common\Model;

class OperationLog extends Model
{
    protected $table = 'zz_operations';

    protected static $info = [];

    public static function setInfo($name, $value)
    {
        self::$info[$name] = $value;
    }

    public static function getInfo($name)
    {
        return self::$info[$name];
    }

    public static function build($operation)
    {
        if (!\Auth::check()) {
            return null;
        }

        $model = parent::build();

        foreach (self::$info as $key => $value) {
            $model->{$key} = $value;
        }

        $model->op = $operation;
        $model->id_utente = \Auth::user()->id;

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

    public function email()
    {
        return $this->belongsTo(Mail::class, 'id_email');
    }
}
