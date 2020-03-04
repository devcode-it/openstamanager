<?php

namespace Modules\Articoli;

use Common\Model;

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

    public function movimentiRelativi()
    {
        return $this->hasMany(Movimento::class, 'idarticolo', 'idarticolo')
            ->where('reference_type', $this->reference_type)
            ->where('reference_id', $this->reference_id);
    }

    public function hasDocument()
    {
        return isset($this->reference_type);
    }

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
