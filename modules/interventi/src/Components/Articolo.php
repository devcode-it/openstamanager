<?php

namespace Modules\Interventi\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\Interventi\Intervento;

class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'mg_articoli_interventi';
    protected $serialRowID = 'intervento';
    protected $disableOrder = true;

    /**
     * Crea una nuova riga collegata ad un intervento.
     *
     * @param Intervento $intervento
     * @param Original   $articolo
     * @param int        $id_automezzo
     *
     * @return self
     */
    public static function build(Intervento $intervento, Original $articolo, $id_automezzo = null)
    {
        $model = parent::build($intervento, $articolo);

        $model->prezzo_acquisto = $articolo->prezzo_acquisto;
        $model->prezzo_vendita = $articolo->prezzo_vendita;
        $model->desc_iva = '';

        $model->save();

        return $model;
    }

    public function movimenta($qta)
    {
        $articolo = $this->articolo;
        $id_automezzo = $this->intervento->idautomezzo;

        $dbo = database();
        $automezzo_carico = $dbo->fetchNum('SELECT qta FROM mg_articoli_automezzi WHERE qta > 0 AND idarticolo = '.prepare($articolo->id).' AND idautomezzo = '.prepare($id_automezzo)) != 0;

        // Movimento l'articolo
        if (!empty($id_automezzo) && $automezzo_carico) {
            $rs = $dbo->fetchArray("SELECT CONCAT_WS(' - ', nome, targa) AS nome FROM dt_automezzi WHERE id=".prepare($id_automezzo));
            $nome = $rs[0]['nome'];

            $descrizione = ($qta < 0) ? tr("Carico sull'automezzo _NAME_", [
                '_NAME_' => $nome,
            ]) : tr("Scarico dall'automezzo _NAME_", [
                '_NAME_' => $nome,
            ]);

            $dbo->query('UPDATE mg_articoli_automezzi SET qta = qta - '.$qta.' WHERE idarticolo = '.prepare($articolo->id).' AND idautomezzo = '.prepare($id_automezzo));
            $data = date('Y-m-d');

            $articolo->registra(-$qta, $descrizione, $data, false, [
                'idautomezzo' => $id_automezzo,
                'idintervento' => $this->idintervento,
            ]);
        } else {
            $intervento = $this->intervento;

            $numero = $intervento->codice;
            $data = database()->fetchOne('SELECT MAX(orario_fine) AS data FROM in_interventi_tecnici WHERE idintervento = :id_intervento', [
                ':id_intervento' => $intervento->id,
            ])['data'];

            $data = $data ?: $intervento->data_richiesta;

            $descrizione = ($qta < 0) ? tr('Ripristino articolo da intervento _NUM_', [
                '_NUM_' => $numero,
            ]) : tr('Scarico magazzino per intervento _NUM_', [
                '_NUM_' => $numero,
            ]);

            $articolo->movimenta(-$qta, $descrizione, $data, false, [
                'idintervento' => $intervento->id,
            ]);
        }
    }

    public function getDirection()
    {
        return 'entrata';
    }

    /**
     * Effettua i conti per l'IVA indetraibile.
     */
    public function fixIvaIndetraibile()
    {
    }

    public function getSubtotaleAttribute()
    {
        return $this->prezzo_vendita * $this->qta;
    }

    /**
     * Effettua i conti per il subtotale della riga.
     */
    protected function fixSubtotale()
    {
        $this->prezzo_vendita = $this->prezzo_unitario_vendita;

        $this->fixIva();
    }
}
