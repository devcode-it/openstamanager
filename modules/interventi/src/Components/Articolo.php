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

    public function movimentaMagazzino($qta)
    {
        $intervento = $this->intervento;

        $numero = $intervento->codice;
        $data = database()->fetchOne('SELECT MAX(orario_fine) AS data FROM in_interventi_tecnici WHERE idintervento = :id_intervento', [
            ':id_intervento' => $intervento->id,
        ])['data'];

        $data = $data ?: $intervento->data_richiesta;

        $descrizione = ($qta < 0) ? tr('Ripristino articolo da Attività numero _NUM_', [
            '_NUM_' => $numero,
        ]) : tr('Scarico magazzino per intervento _NUM_', [
            '_NUM_' => $numero,
        ]);

        $partenza = $intervento->idsede_partenza;
        $arrivo = $intervento->idsede_destinazione;

        $this->articolo->movimenta(-$qta, $descrizione, $data, false, [
            'idintervento' => $intervento->id,
            'idsede_azienda' => $partenza,
            'idsede_controparte' => $arrivo,
        ]);
    }

    public function getDirection()
    {
        return 'entrata';
    }
}
