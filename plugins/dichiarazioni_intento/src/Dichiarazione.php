<?php

namespace Plugins\DichiarazioniIntento;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;

/**
 * Classe per la gestione delle dichiarazione d'intento.
 *
 * @since 2.4.11
 */
class Dichiarazione extends Model
{
    use SoftDeletes;

    protected $table = 'co_dichiarazioni_intento';

    /**
     * Crea una nuova dichiarazione d'intento.
     *
     * @param $data
     * @param $numero_protocollo
     * @param $numero_progressivo
     * @param $data_inizio
     * @param $data_fine
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, $data, $numero_protocollo, $numero_progressivo, $data_inizio, $data_fine)
    {
        $model = parent::build();

        $model->anagrafica()->associate($anagrafica);

        $model->data = $data;
        $model->numero_protocollo = $numero_protocollo;
        $model->numero_progressivo = $numero_progressivo;
        $model->data_inizio = $data_inizio;
        $model->data_fine = $data_fine;

        $model->save();

        return $model;
    }

    /**
     * Metodo per ricalcolare il totale utlizzato della dichiarazione.
     */
    public function fixTotale()
    {
        $this->setRelations([]);

        $righe = collect();
        $fatture = $this->fatture;
        foreach ($fatture as $fattura) {
            $righe = $righe->merge($fattura->getRighe());
        }

        // Filtro delle righe per IVA
        $id_iva = setting("Iva per lettere d'intento");
        $righe_dichiarazione = $righe->filter(function ($item, $key) use ($id_iva) {
            return $item->aliquota != null && $item->aliquota->id == $id_iva;
        });

        $totale = $righe_dichiarazione->sum('totale_imponibile') ?: 0;
        $this->totale = $totale;
    }

    // Relazioni Eloquent

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_anagrafica');
    }

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'id_dichiarazione_intento');
    }
}
