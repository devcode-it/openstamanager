<?php

use Modules\Contratti\Components\Riga;
use Modules\Contratti\Contratto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo;
use Plugins\PianificazioneFatturazione\Pianificazione;

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

// Pianificazione fatturazione
switch ($operazione) {
    case 'add':
        $contratto = Contratto::find($id_record);

        $selezioni = collect(post('selezione_periodo'));
        $periodi = post('periodo');

        $numero_fatture = 0;
        foreach ($selezioni as $key => $selezione) {
            $data_scadenza = $periodi[$key];
            ++$numero_fatture;

            // Creazione pianificazione
            Pianificazione::build($contratto, $data_scadenza);
        }

        if ($numero_fatture > 0) {
            // Rimozione righe precedenti del contratto
            $righe_contratto = $contratto->getRighe();
            $iva_righe = collect($righe_contratto->toArray())->groupBy('idiva');
            foreach ($righe_contratto as $riga) {
                $riga->delete();
            }

            // Creazione nuove righe
            $descrizioni = post('descrizione');
            $qta = post('qta');
            foreach ($iva_righe as $id_iva => $righe) {
                $iva = $righe->first()->aliquota;
                $righe = $righe->toArray();

                $totale = sum(array_column($righe, setting('Utilizza prezzi di vendita comprensivi di IVA') ? 'totale' : 'totale_imponibile'));

                $qta_riga = $qta[$id_iva];
                $descrizione_riga = $descrizioni[$id_iva];

                $prezzo_unitario = $totale / $qta_riga / $numero_fatture;

                for ($rata = 1; $rata <= $numero_fatture; ++$rata) {
                    $riga = Riga::build($contratto);

                    $riga->descrizione = $descrizione_riga;
                    $riga->setPrezzoUnitario($prezzo_unitario, $id_iva);
                    $riga->qta = $qta_riga;

                    $riga->save();
                }
            }
        }

        break;

    case 'reset':
        $dbo->query('DELETE FROM `co_fatturazione_contratti` WHERE `idcontratto`='.prepare($id_record));
        flash()->info(tr('Pianificazione rimossa'));

        break;

    case 'add_fattura':
        $id_rata = post('rata');
        $pianificazione = Pianificazione::find($id_rata);
        $contratto = $pianificazione->contratto;

        $data = post('data');
        $id_segment = post('id_segment');
        $tipo = Tipo::find(post('idtipodocumento'));

        // Creazione fattura
        $fattura = Fattura::build($contratto->anagrafica, $tipo, $data, $id_segment);
        $fattura->note = post('note');
        $fattura->save();

        // Copia righe
        $righe = $pianificazione->getRighe();
        foreach ($righe as $riga) {
            $copia = $riga->copiaIn($fattura, $riga->qta);

            // Aggiornamento movimentazioni
            if ($copia->isArticolo()) {
                //$copia->movimenta($copia->qta);
            }
        }

        // Salvataggio fattura nella pianificazione
        $pianificazione->fattura()->associate($fattura);
        $pianificazione->save();

        break;
}
