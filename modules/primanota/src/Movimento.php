<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\PrimaNota;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Fatture\Fattura;
use Modules\Scadenzario\Scadenza;

class Movimento extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_movimenti';

    protected $appends = [
        'id_conto',
        'avere',
        'dare',
    ];

    public static function build(?Mastrino $mastrino = null, $id_conto = null, ?Fattura $documento = null, ?Scadenza $scadenza = null)
    {
        $model = new static();

        // Informazioni dipendenti dal mastrino
        $model->idmastrino = $mastrino->idmastrino;
        $model->data = $mastrino->data;
        $model->descrizione = $mastrino->descrizione;
        $model->note = $mastrino->note;
        $model->primanota = $mastrino->primanota;
        $model->is_insoluto = $mastrino->is_insoluto;
        $model->id_anagrafica = $mastrino->id_anagrafica;

        // Conto associato
        $model->idconto = $id_conto;

        // Associazione al documento indicato
        $documento_scadenza = $scadenza ? $scadenza->documento : null;
        $documento = $documento ?: $documento_scadenza;
        if (!empty($documento)) {
            $model->id_anagrafica = $documento->idanagrafica;
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

    /**
     * Registra automaticamente un pagamento di prima nota per una scadenza.
     *
     * @param int $id_scadenza ID della scadenza
     * @param float $importo Importo da registrare
     * @param int $id_conto_anagrafica ID del conto cliente/fornitore
     * @param int $id_conto_contropartita ID del conto di contropartita
     * @param string $dir Direzione (entrata/uscita)
     * @return bool
     */
    public static function registraPagamentoAutomatico($id_scadenza, $importo, $id_conto_anagrafica, $id_conto_contropartita, $dir)
    {
        // Recupero la scadenza
        $scadenza = Scadenza::find($id_scadenza);
        if (empty($scadenza)) {
            return false;
        }

        // Recupero il documento associato
        $documento = $scadenza->documento;
        if (empty($documento)) {
            return false;
        }

        // Determino la data del pagamento (oggi o data scadenza se precedente)
        $data_scadenza = !empty($scadenza->data_concordata) ? $scadenza->data_concordata : $scadenza->scadenza;
        $data_pagamento = new \DateTime();
        if ($data_pagamento > $data_scadenza) {
            $data_pagamento = $data_scadenza;
        }

        // Creo la descrizione del movimento
        $descrizione = tr('Pagamento automatico _DESC_', [
            '_DESC_' => $documento->getReference(),
        ]);

        // Creo il mastrino
        $mastrino = Mastrino::build($descrizione, $data_pagamento, false, true, $documento->idanagrafica);
        $mastrino->save();

        // Determino dare/avere in base alla direzione
        if ($dir == 'entrata') {
            // Fattura di vendita: Dare sul conto cliente, Avere sul conto contropartita
            $movimento_cliente = self::build($mastrino, $id_conto_anagrafica, $documento, $scadenza);
            $movimento_cliente->totale = $importo;
            $movimento_cliente->save();

            $movimento_contropartita = self::build($mastrino, $id_conto_contropartita, $documento, null);
            $movimento_contropartita->totale = -$importo;
            $movimento_contropartita->save();
        } else {
            // Fattura di acquisto: Avere sul conto fornitore, Dare sul conto contropartita
            $movimento_fornitore = self::build($mastrino, $id_conto_anagrafica, $documento, $scadenza);
            $movimento_fornitore->totale = -$importo;
            $movimento_fornitore->save();

            $movimento_contropartita = self::build($mastrino, $id_conto_contropartita, $documento, null);
            $movimento_contropartita->totale = $importo;
            $movimento_contropartita->save();
        }
        $mastrino->aggiornaScadenzario();

        return true;
    }
}
