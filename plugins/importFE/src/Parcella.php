<?php

namespace Plugins\ImportFE;

use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo as TipoFattura;
use Util\XML;

/**
 * Classe per la gestione delle parcelle in XML.
 *
 * @since 2.4.11
 */
class Parcella extends FatturaOrdinaria
{
    /**
     * Prepara la fattura elettronica come fattura del gestionale.
     *
     * @param int    $id_tipo
     * @param string $data
     * @param int    $id_sezionale
     * @param int    $ref_fattura
     *
     * @return Fattura
     */
    public function prepareFattura($id_tipo, $data, $id_sezionale, $ref_fattura)
    {
        if (empty($ref_fattura)) {
            return parent::prepareFattura($id_tipo, $data, $id_sezionale, $ref_fattura);
        }

        $anagrafica = $this->saveAnagrafica();

        $tipo = TipoFattura::where('id', $id_tipo)->first();

        $fattura = Fattura::find($ref_fattura);

        // Rimozione righe precedenti (query per evitare procedure automatiche di compensazione)
        database()->query('DELETE FROM co_righe_documenti WHERE iddocumento = '.prepare($fattura->id));

        $fattura->anagrafica()->associate($anagrafica);
        $fattura->tipo()->associate($tipo);
        $fattura->data = $data;
        $fattura->id_segment = $id_sezionale;

        return $fattura;
    }
}
