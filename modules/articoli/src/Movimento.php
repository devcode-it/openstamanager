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

namespace Modules\Articoli;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

/*
 * Classe dedicata alla gestione dei movimenti di magazzino degli articoli.
 */
class Movimento extends Model
{
    use SimpleModelTrait;

    protected $document;
    protected $table = 'mg_movimenti';

    public static function build(Articolo $articolo, $qta, $descrizone, $data, $document = null)
    {
        $model = new static();

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
