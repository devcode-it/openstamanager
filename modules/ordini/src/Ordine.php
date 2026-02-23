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

namespace Modules\Ordini;

use Common\Components\Component;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\DDT\DDT;
use Modules\Interventi\Intervento;
use Modules\Pagamenti\Pagamento;
use Traits\RecordTrait;
use Traits\ReferenceTrait;

class Ordine extends Document
{
    use ReferenceTrait;
    use RecordTrait;

    /**
     * @var bool Disabilita movimentazione automatica
     */
    public static $movimenta_magazzino = false;

    protected $table = 'or_ordini';

    protected $with = [
        'tipo',
    ];

    /**
     * Crea un nuovo ordine.
     *
     * @param string $data
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, Tipo $tipo_documento, $data, $id_segment = null)
    {
        $model = new static();

        $stato_documento = Stato::where('name', 'Bozza')->value('id');

        $direzione = $tipo_documento->dir;
        $id_segment = $id_segment ?: getSegmentPredefined($model->getModule()->id);
        $conto = $direzione == 'entrata' ? 'vendite' : 'acquisti';

        // Tipo di pagamento e banca predefinite dall'anagrafica
        $id_pagamento = $anagrafica['idpagamento_'.$conto] ?: setting('Tipo di pagamento predefinito');

        $model->anagrafica()->associate($anagrafica);
        $model->tipo()->associate($tipo_documento);
        $model->stato()->associate($stato_documento);
        $model->id_segment = $id_segment;
        $model->idagente = $anagrafica->idagente;
        $model->data = $data;
        $model->idpagamento = $id_pagamento;

        $model->numero = static::getNextNumero($data, $direzione, $id_segment);
        $model->numero_esterno = static::getNextNumeroSecondario($data, $direzione, $id_segment);

        $model->save();

        return $model;
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return $this->direzione == 'entrata' ? 'Ordini cliente' : 'Ordini fornitore';
    }

    public function getDirezioneAttribute()
    {
        return $this->tipo->dir;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function tipo()
    {
        return $this->belongsTo(Tipo::class, 'idtipoordine');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstatoordine');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idordine');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idordine');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idordine');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idordine');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_ordine');
    }

    /**
     * Effettua un controllo sui campi del documento.
     * Viene richiamato dalle modifiche alle righe del documento.
     */
    public function triggerEvasione(Component $trigger)
    {
        parent::triggerEvasione($trigger);

        if (!setting('Cambia automaticamente stato ordini fatturati')) {
            return;
        }

        $righe = $this->getRighe();
        $qta_evasa = $righe->sum('qta_evasa');
        $qta = $righe->sum('qta');
        $parziale = $qta != $qta_evasa;

        $stato_attuale = $this->stato;
        $use_translation = database()->isConnected() && database()->tableExists('or_statiordine_lang');
        $nome_stato = $use_translation
            ? $stato_attuale->getTranslation('title', \Models\Locale::getPredefined()->id)
            : $stato_attuale->descrizione;

        // Ottimizzazione: singola query per calcolare quantità fatturate
        $righe_ids = $righe->pluck('id')->toArray();
        $class_type = Components\Articolo::class;

        $fatture_collegate = database()->table('co_righe_documenti')
            ->whereIn('original_id', $righe_ids)
            ->where('original_type', $class_type)
            ->join('co_documenti', 'co_righe_documenti.iddocumento', '=', 'co_documenti.id')
            ->select('co_righe_documenti.original_id', 'co_righe_documenti.iddocumento')
            ->get()
            ->keyBy('original_id');

        $qta_fatturate = 0;
        $fatture_collegate_totali = $fatture_collegate->count();

        foreach ($righe as $riga) {
            if ($fatture_collegate->has($riga->id)) {
                $qta_fatturate += $riga->qta;
            }
        }

        $parziale_fatturato = $qta != $qta_fatturate;
        $descrizione = $this->determinaNuovoStato($trigger, $qta_evasa, $parziale, $parziale_fatturato, $nome_stato, $fatture_collegate_totali);

        $stato_field = $use_translation ? 'name' : 'descrizione';
        $stato = Stato::where($stato_field, $descrizione)->first()->id;

        $this->stato()->associate($stato);
        $this->save();
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di ordine.
     *
     * @param string $data
     * @param string $direzione
     * @param int    $id_segment
     *
     * @return string
     */
    public static function getNextNumero($data, $direzione, $id_segment)
    {
        return getNextNumeroProgressivo('or_ordini', 'numero', $data, $id_segment, [
            'direction' => $direzione,
            'type_document_field' => 'idtipoordine',
            'type_document_table' => 'or_tipiordine',
        ]);
    }

    /**
     * Calcola il nuovo numero secondario di ordine.
     *
     * @param string $data
     * @param string $direzione
     *
     * @return string
     */
    public static function getNextNumeroSecondario($data, $direzione, $id_segment)
    {
        return getNextNumeroSecondarioProgressivo('or_ordini', 'numero_esterno', $data, $id_segment, [
            'direction' => $direzione,
            'type_document_field' => 'idtipoordine',
            'type_document_table' => 'or_tipiordine',
        ]);
    }

    // Opzioni di riferimento

    public function getReferenceName()
    {
        return $this->tipo->getTranslation('title');
    }

    public function getReferenceNumber()
    {
        $visualizza_numero_cliente = setting('Visualizza numero ordine cliente');

        return $visualizza_numero_cliente
            ? ($this->numero_cliente ?: $this->numero_esterno ?: $this->numero)
            : ($this->numero_esterno ?: $this->numero);
    }

    public function getReferenceSecondaryNumber()
    {
        return null;
    }

    public function getReferenceDate()
    {
        $visualizza_numero_cliente = setting('Visualizza numero ordine cliente');

        return $visualizza_numero_cliente ? ($this->data_cliente ?: $this->data) : $this->data;
    }

    public function getReferenceRagioneSociale()
    {
        return $this->anagrafica->ragione_sociale;
    }

    /**
     * Determina il nuovo stato dell'ordine in base alle condizioni di evasione/fatturazione.
     *
     * @param float  $qta_evasa
     * @param bool   $parziale
     * @param bool   $parziale_fatturato
     * @param string $nome_stato
     * @param int    $fatture_collegate_totali
     *
     * @return string
     */
    protected function determinaNuovoStato(Component $trigger, $qta_evasa, $parziale, $parziale_fatturato, $nome_stato, $fatture_collegate_totali)
    {
        if ($qta_evasa == 0) {
            return 'Accettato';
        }

        $documento = $trigger->getDocument();

        if ($documento instanceof \Modules\Fatture\Fattura) {
            return $parziale_fatturato ? 'Parzialmente fatturato' : 'Fatturato';
        }

        if ($documento instanceof DDT) {
            $fatture_ddt = database()->table('co_righe_documenti')
                ->where('idddt', $documento->id)
                ->join('co_documenti', 'co_righe_documenti.iddocumento', '=', 'co_documenti.id')
                ->count();

            return $fatture_ddt > 0
                ? ($parziale_fatturato ? 'Parzialmente fatturato' : 'Fatturato')
                : ($parziale ? 'Parzialmente evaso' : 'Evaso');
        }

        if ($fatture_collegate_totali > 0) {
            return $parziale_fatturato ? 'Parzialmente fatturato' : 'Fatturato';
        }

        if (in_array($nome_stato, ['Parzialmente fatturato', 'Fatturato'])) {
            return $parziale ? 'Parzialmente evaso' : 'Evaso';
        }

        if ($qta_evasa > 0) {
            return $parziale ? 'Parzialmente evaso' : 'Evaso';
        }

        return $nome_stato;
    }
}
