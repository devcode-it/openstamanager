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

use Models\OperationLog;
use Modules\Anagrafiche\Anagrafica;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Modules\Fatture\Fattura;
use Modules\Scadenzario\Scadenza;

$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));

switch (post('op')) {
    case 'change_distinta':
        $distinta = post('distinta');

        $n_scadenze = 0;
        foreach ($id_records as $id) {
            $database->update('co_scadenziario', [
                'distinta' => $distinta,
            ], ['id' => $id]);

            ++$n_scadenze;
        }

        if ($n_scadenze > 0) {
            flash()->info(tr('Info distinta aggiornata a _NUM_ scadenze!', [
                '_NUM_' => $n_scadenze,
            ]));
        } else {
            flash()->warning(tr('Nessuna scadenza modificata!'));
        }

        break;

    case 'change-bank':
        $list = [];
        foreach ($id_records as $id) {
            $scadenza = Scadenza::find($id);
            if ($scadenza->iddocumento){
                $documento = Fattura::find($scadenza->iddocumento);
                $documento->id_banca_azienda = post('id_banca');
                $documento->save();
                array_push($list, $documento->numero_esterno);
            }
        }

        if ($list){
            flash()->info(tr('Banca aggiornata per le Fatture _LIST_ !', [
                '_LIST_' => implode(',', $list),
            ]));
        }

        break;

    case 'send-sollecito':
        $template = Template::pool('Sollecito di pagamento');
        
        $list = [];
        foreach ($id_records as $id) {
            $scadenza = Scadenza::find($id);
            $documento = $scadenza->documento;

            // Controllo se è una fattura di vendita
            if ($documento->direzione == 'entrata'){
                $id_documento = $documento->id;
                $id_anagrafica = $documento->idanagrafica;

                $fattura_allegata = $dbo->selectOne('zz_files', 'id', ['id_module' => $id_module, 'id_record' => $id, 'original' => 'Fattura di vendita.pdf'])['id'];

                // Allego stampa della fattura se non presente
                if (empty($fattura_allegata)) {
                    $print_predefined = $dbo->selectOne('zz_prints', '*', ['predefined' => 1, 'id_module' => Modules::get('Fatture di vendita')['id']]);

                    $print = Prints::render($print_predefined['id'], $id_documento, null, true);
                    $name = 'Fattura di vendita';
                    $upload = Uploads::upload($print['pdf'], [
                        'name' => $name,
                        'original_name' => $name.'.pdf',
                        'category' => 'Generale',
                        'id_module' => $id_module,
                        'id_record' => $id,
                    ]);

                    $fattura_allegata = $dbo->selectOne('zz_files', 'id', ['id_module' => $id_module, 'id_record' => $id, 'original' => 'Fattura di vendita.pdf'])['id'];
                }

                // Selezione destinatari e invio mail
                if (!empty($template)) {
                    $creata_mail = false;
                    $emails = [];

                    // Aggiungo email anagrafica
                    if (!empty($documento->anagrafica->email)) {
                        $emails[] = $documento->anagrafica->email;
                        $mail = Mail::build(auth()->getUser(), $template, $id);
                        $mail->addReceiver($documento->anagrafica->email);
                        $creata_mail = true;
                    }

                    // Aggiungo email referenti in base alla mansione impostata nel template
                    $mansioni = $dbo->select('em_mansioni_template', 'idmansione', ['id_template' => $template->id]);
                    foreach ($mansioni as $mansione) {
                        $referenti = $dbo->table('an_referenti')->where('idmansione', $mansione['idmansione'])->where('idanagrafica', $id_anagrafica)->where('email', '!=', '')->get();
                        if (!$referenti->isEmpty() && $creata_mail == false) {
                            $mail = Mail::build(auth()->getUser(), $template, $id);
                            $creata_mail = true;
                        }
                        
                        foreach ($referenti as $referente) {
                            if (!in_array($referente->email, $emails)) {
                                $emails[] = $referente->email;
                                $mail->addReceiver($referente->email);
                            }   
                        }
                    }
                    if ($creata_mail == true) {
                        if (!empty($fattura_allegata)) {
                            $mail->addUpload($fattura_allegata);
                        }
                        
                        $mail->save();
                        OperationLog::setInfo('id_email', $mail->id);
                        OperationLog::setInfo('id_module', $id_module);
                        OperationLog::setInfo('id_record', $id);
                        OperationLog::build('send-email');

                        array_push($list, $documento->numero_esterno);
                    }
                }
            }
        }

        if ($list){
            flash()->info(tr('Mail inviata per le Fatture _LIST_ !', [
                '_LIST_' => implode(',', $list),
            ]));
        }

        break;
}

$operations['registrazione-contabile'] = [
    'text' => '<span><i class="fa fa-calculator"></i> '.tr('Registrazione contabile').'</span>',
    'data' => [
        'title' => tr('Registrazione contabile'),
        'type' => 'modal',
        'origine' => 'scadenzario',
        'url' => base_path().'/add.php?id_module='.Modules::get('Prima nota')['id'],
    ],
];

$operations['change_distinta'] = [
    'text' => '<span><i class="fa fa-edit"></i> '.tr('Info distinta'),
    'data' => [
        'title' => tr('Modificare le informazioni della distinta?'),
        'msg' => tr('Per ciascuna scadenza selezionata verrà modificata l\'informazione della distinta associata').'.<br>
        <br>{[ "type": "text", "label": "'.tr('Info distinta').'", "name": "distinta", "required": 1 ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['change-bank'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna banca').'</span>',
    'data' => [
        'title' => tr('Aggiornare la banca?'),
        'msg' => tr('Per ciascuna scadenza selezionata, verrà aggiornata la banca della fattura di riferimento e quindi di conseguenza di tutte le scadenze collegate').'
        <br><br>{[ "type": "select", "label": "'.tr('Banca').'", "name": "id_banca", "required": 1, "values": "query=SELECT id, CONCAT (nome, \' - \' , iban) AS descrizione FROM co_banche WHERE id_anagrafica='.prepare($anagrafica_azienda->idanagrafica).'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['send-sollecito'] = [
    'text' => '<span><i class="fa fa-envelope"></i> '.tr('Invia mail sollecito').'</span>',
    'data' => [
        'title' => tr('Inviare mail sollecito?'),
        'msg' => tr('Per ciascuna scadenza selezionata collegata ad una fattura di vendita, verrà inviata una mail con allegata la fattura di vendita corrispondente.<br>(Template utilizzato: Sollecito di pagamento)'),
        'button' => tr('Invia'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

return $operations;
