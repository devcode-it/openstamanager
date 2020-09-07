<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace Modules\Articoli;

use Common\Model;

/**
 * Classe dedicata alla gestione dei movimenti di magazzino degli articoli.
 *
 * Alcuni appunti sull'utilizzo dei campi *idsede_azienda* e *idsede_controparte*
 * Il campo *idsede_azienda* è relativo alla sede dell'Azienda che è interessata dal movimento, mentre *idsede_controparte* indica la sede del Cliente/Fornitore controparte.
 * La natura effettiva del movimento (e di *idsede_controparte*) è quindi identificabile dal valore del campo *qta*: se positivo il magazzino è aumentatao (movimento da *idsede_controparte* a *idsede_azienda*), se negativo il magazzino è diminuito (movimento da *idsede_azienda* a *idsede_controparte*).
 *
 * Si noti che il valore "0" per i campi *idsede_* indica solitamente una Sede legale dell'Anagrafica di riferimento. Solo se il movimento non è associato ad alcun documento il campo *idsede_controparte* non segue questo significato, poichè il movimento in questo caso è considerato manuale.
 */
class Movimento extends Model
{
    protected $document;
    protected $table = 'mg_movimenti';

    public static function build(Articolo $articolo, $qta, $descrizone, $data, $document = null)
    {
        $model = parent::build();

        $model->articolo()->associate($articolo);

        $model->qta = $qta;
        $model->descrizone = $descrizone;
        $model->data = $data;

        if (!empty($document)) {
            $class = get_class($document);
            $id = $document->id;

            $model->reference_type = $class;
            $model->reference_id = $id;
        } else {
            $model->manuale = true;
        }

        $model->save();

        return $model;
    }

    public function getDescrizioneAttribute()
    {
        $descrizione = $this->movimento;
        if ($this->hasDocument()) {
            $documento = $this->getDocument();

            $descrizione = $documento ? self::descrizioneMovimento($this->qta, $documento->direzione) : $descrizione;
        }

        return $descrizione;
    }

    public function getDataAttribute()
    {
        $data = $this->attributes['data'];
        if ($this->hasDocument()) {
            $documento = $this->getDocument();

            $data = $documento ? $documento->getReferenceDate() : $data;
        }

        return $data;
    }

    public function getQtaAttribute()
    {
        if (isset($this->qta_documento)) {
            return $this->qta_documento;
        }

        return $this->qta;
    }

    public function articolo()
    {
        return $this->hasOne(Articolo::class, 'idarticolo');
    }

    /**
     * Restituisce un insieme di movimenti appartenenti allo stesso documento.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function movimentiRelativi()
    {
        return $this->hasMany(Movimento::class, 'idarticolo', 'idarticolo')
            ->where('reference_type', $this->reference_type)
            ->where('reference_id', $this->reference_id);
    }

    /**
     * Verifica se è disponibile un documento associato al movimento.
     *
     * @return bool
     */
    public function hasDocument()
    {
        return isset($this->reference_type);
    }

    /**
     * Verifica se il movimento è manuale oppure automatico.
     *
     * @return bool
     */
    public function isManuale()
    {
        return !empty($this->manuale);
    }

    /**
     * Restituisce il documento collegato al movimento.
     *
     * @return Model
     */
    public function getDocument()
    {
        if ($this->hasDocument() && !isset($this->document)) {
            $class = $this->reference_type;
            $id = $this->reference_id;

            $this->document = $class::find($id);
        }

        return $this->document;
    }

    /**
     * Restituisce una descrizione standard applicabile a un movimento sulla base della relativa quantità e alla direzione.
     *
     * @param $qta
     * @param string $direzione
     *
     * @return string
     */
    public static function descrizioneMovimento($qta, $direzione = 'entrata')
    {
        if (empty($direzione)) {
            $direzione = 'entrata';
        }

        $carico = ($direzione == 'entrata') ? tr('Ripristino articolo') : tr('Carico magazzino');
        $scarico = ($direzione == 'entrata') ? tr('Scarico magazzino') : tr('Rimozione articolo');

        $descrizione = $qta > 0 ? $carico : $scarico;

        // Descrizione per vecchi documenti rimossi ma con movimenti azzerati
        if ($qta == 0) {
            $descrizione = tr('Nessun movimento');
        }

        return $descrizione;
    }
}
