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

include_once __DIR__.'/../../core.php';

use Carbon\Carbon;
use Models\Module;
use Models\Plugin;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Contratti\Contratto;
use Modules\Contratti\Stato as StatoContratto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\Tipo;
use Plugins\PianificazioneInterventi\Promemoria;

// Segmenti
$id_fatture = Module::where('name', 'Fatture di vendita')->first()->id;
if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_fatture);
    $_SESSION['module_'.$id_fatture]['id_segment'] = $segments[0]['id'] ?? null;
}
$id_segment = $_SESSION['module_'.$id_fatture]['id_segment'];
$idconto = setting('Conto predefinito fatture di vendita');
$idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
    'predefined' => 1,
    'dir' => 'entrata',
])['id'];
$stati_bloccati = $dbo->fetchOne('SELECT GROUP_CONCAT(`title` SEPARATOR ", ") AS stati_bloccati FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `is_bloccato`= 1')['stati_bloccati'];

switch (post('op')) {
    case 'create_invoice':
        $documenti = collect();
        $numero_totale = 0;

        // Informazioni della fattura
        $tipo_documento = Tipo::where('id', post('idtipodocumento'))->first();

        $stato_documenti_accodabili = Stato::where('name', 'Bozza')->first();
        $accodare = post('accodare');

        $data = date('Y-m-d');
        $id_segment = post('id_segment');
        $raggruppamento = post('raggruppamento');

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $documento_import = Contratto::find($id);
            $anagrafica = $documento_import->anagrafica;
            $id_anagrafica = $anagrafica->id;

            if (!$documento_import->stato->is_fatturabile) {
                break;
            }

            // Proseguo solo se i documenti scelti sono fatturabili
            $righe = $documento_import->getRighe();
            if (!empty($righe)) {
                ++$numero_totale;

                // Ricerca fattura per anagrafica tra le registrate
                $id_sede = $raggruppamento == 'sede' ? $documento_import->idsede_destinazione : 0;
                if ($raggruppamento == 'sede') {
                    $fattura = $documenti->first(fn ($item, $key) => $item->anagrafica->id == $id_anagrafica && $item->idsede_destinazione == $id_sede);
                } else {
                    $fattura = $documenti->first(fn ($item, $key) => $item->anagrafica->id == $id_anagrafica);
                }

                // Ricerca fattura per anagrafica se l'impostazione di accodamento è selezionata
                if (!empty($accodare) && empty($fattura)) {
                    if ($raggruppamento == 'sede') {
                        $fattura = Fattura::where('idanagrafica', $id_anagrafica)
                            ->where('idstatodocumento', $stato_documenti_accodabili->id)
                            ->where('idtipodocumento', $tipo_documento->id)
                            ->where('idsede_destinazione', $id_sede)
                            ->first();
                    } else {
                        $fattura = Fattura::where('idanagrafica', $id_anagrafica)
                            ->where('idstatodocumento', $stato_documenti_accodabili->id)
                            ->where('idtipodocumento', $tipo_documento->id)
                            ->first();
                    }

                    if (!empty($fattura)) {
                        $documenti->push($fattura);
                    }
                }

                // Creazione fattura per anagrafica
                if (empty($fattura)) {
                    $fattura = Fattura::build($anagrafica, $tipo_documento, $data, $id_segment);
                    $fattura->idsede_destinazione = $id_sede;
                    $fattura->save();
                    $documenti->push($fattura);
                }

                // Inserimento righe
                foreach ($righe as $riga) {
                    $qta = $riga->qta_rimanente;

                    if ($qta > 0) {
                        $copia = $riga->copiaIn($fattura, $qta);

                        // Fix per idconto righe fattura
                        $articolo = ArticoloOriginale::find($copia->idarticolo);
                        $copia->id_conto = ($articolo->idconto_vendita ?: $idconto);

                        // Aggiornamento seriali dalla riga dell'ordine
                        if ($copia->isArticolo()) {
                            $copia->serials = $riga->serials;
                        }

                        $copia->save();
                    }
                }
            }
        }

        if ($numero_totale > 0) {
            flash()->info(tr('_NUM_ contratti fatturati!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun contratto fatturato!'));
        }
        break;

    case 'renew_contract':
        $numero_totale = 0;

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $contratto = Contratto::find($id);
            $rinnova = !empty($contratto->data_accettazione) && !empty($contratto->data_conclusione) && $contratto->data_accettazione != '0000-00-00' && $contratto->data_conclusione != '0000-00-00' && $contratto->stato->is_bloccato & $contratto->rinnovabile;

            if ($rinnova) {
                $diff = $contratto->data_conclusione->diffAsCarbonInterval($contratto->data_accettazione);

                $new_contratto = $contratto->replicate();

                $new_contratto->numero = Contratto::getNextNumero($contratto->data_conclusione->copy()->addDays(1), $contratto->id_segment);

                $new_contratto->idcontratto_prev = $contratto->id;
                $new_contratto->data_accettazione = $contratto->data_conclusione->copy()->addDays(1);
                $new_contratto->data_conclusione = $new_contratto->data_accettazione->copy()->add($diff);
                $new_contratto->data_bozza = Carbon::now();

                $stato = StatoContratto::where('name', 'Bozza')->first();
                $new_contratto->stato()->associate($stato);

                $new_contratto->save();
                $new_idcontratto = $new_contratto->id;

                // Correzioni dei prezzi per gli interventi
                $dbo->query('DELETE FROM co_contratti_tipiintervento WHERE idcontratto='.prepare($new_idcontratto));
                $dbo->query('INSERT INTO co_contratti_tipiintervento(idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico) SELECT '.prepare($new_idcontratto).', idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico FROM co_contratti_tipiintervento AS z WHERE idcontratto='.prepare($contratto->id));
                $new_contratto->save();

                // Replico le righe del contratto
                $righe = $contratto->getRighe();
                foreach ($righe as $riga) {
                    $new_riga = $riga->replicate();
                    $new_riga->qta_evasa = 0;
                    $new_riga->idcontratto = $new_contratto->id;

                    $new_riga->save();
                }

                // Replicazione degli impianti
                $impianti = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_contratti WHERE idcontratto='.prepare($contratto->id));
                $dbo->sync('my_impianti_contratti', ['idcontratto' => $new_idcontratto], ['idimpianto' => array_column($impianti, 'idimpianto')]);

                // Replicazione dei promemoria
                $promemoria = $dbo->fetchArray('SELECT * FROM co_promemoria WHERE idcontratto='.prepare($contratto->id));
                $giorni = $contratto->data_conclusione->diffInDays($contratto->data_accettazione);
                foreach ($promemoria as $p) {
                    $dbo->insert('co_promemoria', [
                        'idcontratto' => $new_idcontratto,
                        'data_richiesta' => date('Y-m-d', strtotime($p['data_richiesta'].' +'.$giorni.' day')),
                        'idtipointervento' => $p['idtipointervento'],
                        'richiesta' => $p['richiesta'],
                        'idimpianti' => $p['idimpianti'],
                    ]);
                    $id_promemoria = $dbo->lastInsertedID();

                    $promemoria = Promemoria::find($p['id']);
                    $righe = $promemoria->getRighe();
                    foreach ($righe as $riga) {
                        $new_riga = $riga->replicate();
                        $new_riga->id_promemoria = $id_promemoria;
                        $new_riga->save();
                    }

                    // Copia degli allegati
                    $allegati = $promemoria->uploads();
                    foreach ($allegati as $allegato) {
                        $allegato->copia([
                            'id_module' => $id_module,
                            'id_plugin' => Plugin::where('name', 'Pianificazione interventi')->first()->id,
                            'id_record' => $id_promemoria,
                        ]);
                    }
                }

                // Cambio stato precedente contratto in concluso (non più pianificabile)
                $dbo->query('UPDATE `co_contratti` SET `rinnovabile`= 0, `idstato`= (SELECT `co_staticontratti`.`id` FROM `co_staticontratti` WHERE `name` = \'Concluso\')  WHERE `co_contratti`.`id` = '.prepare($contratto->id));

                ++$numero_totale;
            }
        }

        if ($numero_totale > 0) {
            flash()->info(tr('_NUM_ contratti rinnovati!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun contratto rinnovato!'));
        }
        break;

    case 'change_status':
        $id_stato = post('id_stato');

        $n_contratti = 0;
        $stato = StatoContratto::find($id_stato);

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $contratto = Contratto::find($id);

            $contratto->stato()->associate($stato);
            $contratto->save();

            ++$n_contratti;
        }

        if ($n_contratti > 0) {
            flash()->info(tr('Stato aggiornato a _NUM_ contratti!', [
                '_NUM_' => $n_contratti,
            ]));
        } else {
            flash()->warning(tr('Nessuno stato aggiornato!'));
        }

        break;

    case 'change_payment':
        $n_contratti = 0;

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $contratto = Contratto::find($id);

            $contratto->idpagamento = post('idpagamento');
            $contratto->save();

            ++$n_contratti;
        }

        if ($n_contratti > 0) {
            flash()->info(tr('Metodo di pagamento aggiornato a _NUM_ contratti!', [
                '_NUM_' => $n_contratti,
            ]));
        } else {
            flash()->warning(tr('Nessun metodo di pagamento aggiornato!'));
        }

        break;
}

$operations['change_status'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia stato'),
    'data' => [
        'title' => tr('Vuoi davvero aggiornare lo stato di questi contratti?'),
        'msg' => '<br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `co_staticontratti`.`id`, `title` AS descrizione, `colore` as _bgcolor_ FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `title`" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['change_payment'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia metodo di pagamento'),
    'data' => [
        'title' => tr('Vuoi davvero aggiornare il metodo di pagamento di questi contratti?'),
        'msg' => '<br>{[ "type": "select", "label": "'.tr('Metodo di pagamento').'", "name": "idpagamento", "required": 1, "ajax-source": "pagamenti" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['create_invoice'] = [
    'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Fattura _TYPE_', ['_TYPE_' => strtolower((string) $module->getTranslation('title'))]),
    'data' => [
        'title' => tr('Fatturare i _TYPE_ selezionati?', ['_TYPE_' => strtolower((string) $module->getTranslation('title'))]),
        'msg' => '{[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle fatture di vendita non ancora emesse?').'</small>", "placeholder": "'.tr('Aggiungere alle fatture esistenti non ancora emesse?').'", "name": "accodare" ]}<br>
        {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_fatture, 'is_sezionale' => 1]).', "value": "'.$id_segment.'", "select-options-escape": true ]}<br>
        {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT `co_tipidocumento`.`id`, CONCAT(`codice_tipo_documento_fe`, \' - \', `title`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `enabled` = 1 AND `dir` =\'entrata\' ORDER BY `codice_tipo_documento_fe`", "value": "'.$idtipodocumento.'" ]}<br>
        {[ "type": "select", "label": "'.tr('Raggruppa per').'", "name": "raggruppamento", "required": 1, "values": "list=\"cliente\":\"Cliente\",\"sede\":\"Sede\"", "value": "'.setting('Raggruppamento fatturazione massiva contratti').'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['renew_contract'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Rinnova contratti').'</span>',
    'data' => [
        'title' => tr('Rinnovare i contratti selezionati?').'</span>',
        'msg' => ''.tr('Un contratto è rinnovabile se presenta una data di accettazione e conclusione, se il rinnovo è abilitato dal plugin Rinnovi e se si trova in uno di questi stati: _STATE_LIST_', ['_STATE_LIST_' => $stati_bloccati]),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

return $operations;
