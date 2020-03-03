<?php

namespace Modules\Interventi\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\Interventi\Intervento;

class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'in_righe_interventi';
    protected $serialRowID = 'intervento';

    /**
     * Crea una nuova riga collegata ad un intervento.
     *
     * @return self
     */
    public static function build(Intervento $intervento, Original $articolo)
    {
        $model = parent::build($intervento, $articolo);

        $model->prezzo_acquisto = $articolo->prezzo_acquisto;
        $model->prezzo_vendita = $articolo->prezzo_vendita;
        $model->desc_iva = '';

        $model->save();

        return $model;
    }
}
