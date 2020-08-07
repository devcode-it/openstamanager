<?php

namespace Modules\Fatture\Gestori;

use Modules\Fatture\Components;
use Modules\Fatture\Fattura;

/**
 * Classe dedicata alla gestione del Bollo per la Fattura, compreso il calcolo del relativo valore e la generazione dinamica della riga associata.
 *
 * @since 2.4.17
 */
class Bollo
{
    private $fattura;

    public function __construct(Fattura $fattura)
    {
        $this->fattura = $fattura;
    }

    /**
     * Metodo per calcolare automaticamente il bollo da applicare al documento.
     *
     * @return float
     */
    public function getBollo()
    {
        if (isset($this->fattura->bollo)) {
            return $this->fattura->bollo;
        }

        $righe_bollo = $this->fattura->getRighe()->filter(function ($item, $key) {
            return $item->aliquota != null && in_array($item->aliquota->codice_natura_fe, ['N1', 'N2', 'N3', 'N4']);
        });
        $importo_righe_bollo = $righe_bollo->sum('netto');

        // Leggo la marca da bollo se c'è e se il netto a pagare supera la soglia
        $bollo = ($this->fattura->direzione == 'uscita') ? $this->fattura->bollo : setting('Importo marca da bollo');

        $marca_da_bollo = 0;
        if (abs($bollo) > 0 && abs($importo_righe_bollo) > setting("Soglia minima per l'applicazione della marca da bollo")) {
            $marca_da_bollo = $bollo;
        }

        // Se l'importo è negativo può essere una nota di credito, quindi cambio segno alla marca da bollo
        $marca_da_bollo = abs($marca_da_bollo);

        return $marca_da_bollo;
    }

    /**
     * Metodo per aggiornare ed eventualmente aggiungere la marca da bollo al documento.
     */
    public function manageRigaMarcaDaBollo()
    {
        $riga = $this->fattura->rigaBollo;

        $addebita_bollo = $this->fattura->addebita_bollo;
        $marca_da_bollo = $this->getBollo();

        // Rimozione riga bollo se nullo
        if (empty($addebita_bollo) || empty($marca_da_bollo)) {
            if (!empty($riga)) {
                $riga->delete();
            }

            return null;
        }

        // Creazione riga bollo se non presente
        if (empty($riga)) {
            $riga = Components\Riga::build($this->fattura);
            $riga->save();
        }

        $riga->prezzo_unitario = $marca_da_bollo;
        $riga->qta = 1;
        $riga->descrizione = setting('Descrizione addebito bollo');
        $riga->id_iva = setting('Iva da applicare su marca da bollo');
        $riga->idconto = setting('Conto predefinito per la marca da bollo');

        $riga->save();

        return $riga->id;
    }
}
