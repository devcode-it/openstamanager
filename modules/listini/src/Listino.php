<?php

namespace Modules\Listini;

use Common\Model;

class Listino extends Model
{
    protected $table = 'mg_listini';

    public static function build($nome, $percentuale)
    {
        $model = parent::build();

        $model->nome = $nome;
        $model->percentuale = $percentuale;
        $model->save();

        return $model;
    }

    public function setPercentualeCombinatoAttribute($value)
    {
        $this->prc_combinato = $value;
    }

    public function getPercentualeCombinatoAttribute()
    {
        return $this->prc_combinato;
    }

    public function setPercentualeAttribute($value)
    {
        $value = floatval($value);
        if (abs($value) > 100) {
            $value = ($value > 0) ? 100 : -100;
        }

        $this->prc_guadagno = $value;
    }

    public function getPercentualeAttribute()
    {
        return $this->prc_guadagno;
    }

    public function save(array $options = [])
    {
        $combinato = $this->prc_combinato;
        if (!empty($combinato)) {
            $this->percentuale = parseScontoCombinato($combinato);
        }

        return parent::save($options);
    }
}
