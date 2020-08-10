<?php

namespace Modules\PrimaNota;

use Common\Model;
use Modules\Fatture\Fattura;
use Modules\Scadenzario\Scadenza;

class Movimento extends Model
{
    protected $table = 'co_movimenti';

    protected $appends = [
        'id_conto',
        'avere',
        'dare',
    ];

    public static function build(Mastrino $mastrino, $id_conto, Fattura $documento = null, Scadenza $scadenza = null)
    {
        $model = parent::build();

        // Informazioni dipendenti dal mastrino
        $model->idmastrino = $mastrino->idmastrino;
        $model->data = $mastrino->data;
        $model->descrizione = $mastrino->descrizione;
        $model->primanota = $mastrino->primanota;
        $model->is_insoluto = $mastrino->is_insoluto;

        // Conto associato
        $model->idconto = $id_conto;

        // Associazione al documento indicato
        $documento_scadenza = $scadenza ? $scadenza->documento : null;
        $documento = $documento ?: $documento_scadenza;
        if (!empty($documento)) {
            $model->idanagrafica = $documento->idanagrafica;
            $model->iddocumento = $documento->id;
        }

        // Associazione alla scadenza indicata
        $model->id_scadenza = $scadenza ? $scadenza->id : null;

        $model->save();

        return $model;
    }

    public function setTotale($avere, $dare)
    {
        if (!empty($avere)) {
            $totale = -$avere;
        } else {
            $totale = $dare;
        }

        $this->totale = $totale;
    }

    public function save(array $options = [])
    {
        // Aggiornamento automatico di totale_reddito
        $conto = database()->fetchOne('SELECT * FROM co_pianodeiconti3 WHERE id = '.prepare($this->id_conto));
        $percentuale = floatval($conto['percentuale_deducibile']);
        $this->totale_reddito = $this->totale * $percentuale / 100;

        return parent::save($options);
    }

    // Attributi

    public function getIdContoAttribute()
    {
        return $this->attributes['idconto'];
    }

    public function getAvereAttribute()
    {
        return $this->totale < 0 ? abs($this->totale) : 0;
    }

    public function getDareAttribute()
    {
        return $this->totale > 0 ? abs($this->totale) : 0;
    }

    // Relazioni Eloquent

    public function scadenza()
    {
        return $this->belongsTo(Scadenza::class, 'id_scadenza');
    }

    public function documento()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }
}
