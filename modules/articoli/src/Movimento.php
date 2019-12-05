<?php

namespace Modules\Articoli;

use Common\Model;
use Traits\HierarchyTrait;

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

        if (!empty($document)){
            $class = get_class($document);
            $id = $document->id;

            $model->reference_type = $class;
            $model->reference_id = $id;
        }else {
            $model->manuale = true;
        }

        $model->save();

        return $model;
    }

    public function articolo()
    {
        return $this->hasOne(Articolo::class, 'idarticolo');
    }

    public function hasDocument(){
        return isset($this->reference_type);
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

            $this->document =  $class::find($id);
        }

        return $this->document;
    }
}
