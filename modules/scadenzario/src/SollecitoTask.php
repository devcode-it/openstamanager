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

namespace Modules\Scadenzario;

use Models\Module;
use Models\OperationLog;
use Models\PrintTemplate;
use Models\User;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Tasks\Manager;

/**
 * Task dedicato alla gestione del backup giornaliero automatico, se abilitato da Impostazioni.
 */
class SollecitoTask extends Manager
{
    public function needsExecution()
    {
        if (setting('Invio solleciti in automatico') > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function execute()
    {
        if (setting('Invio solleciti in automatico') > 0) {
            $giorni_promemoria = setting('Intervallo di giorni in anticipo per invio promemoria scadenza');
            $giorni_scadenza = setting('Ritardo in giorni della scadenza della fattura per invio sollecito pagamento');
            $giorni_prossimo_sollecito = setting("Ritardo in giorni dall'ultima email per invio sollecito pagamento");
            $template_promemoria = setting('Template email promemoria scadenza');
            $template_1 = setting('Template email primo sollecito');
            $template_2 = setting('Template email secondo sollecito');
            $template_3 = setting('Template email terzo sollecito');
            $template_notifica = setting('Template email mancato pagamento dopo i solleciti');
            $id_user = database()->selectOne('zz_users', 'id', ['idgruppo' => 1, 'enabled' => 1])['id'];
            $user = User::find($id_user);

            // Invio promemoria
            $rs = database()->fetchArray("
                SELECT 
                    `co_scadenziario`.*, 
                    IF(`co_scadenziario`.`data_concordata`, `co_scadenziario`.`data_concordata`, `co_scadenziario`.`scadenza`) AS `data_scadenza`
                FROM 
                    `co_scadenziario` 
                    INNER JOIN `co_documenti` ON `co_scadenziario`.`iddocumento`=`co_documenti`.`id` 
                    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` 
                    INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id` 
                WHERE 
                    `co_tipidocumento`.`dir`='entrata' 
                    AND `is_fiscale`=1  
                    AND `zz_segments`.`autofatture`=0 
                    AND ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`) 
                    AND IF(`co_scadenziario`.`data_concordata`, `co_scadenziario`.`data_concordata`, `co_scadenziario`.`scadenza`) = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL ".prepare($giorni_promemoria)." DAY),'%Y-%m-%d')");

            foreach ($rs as $r) {
                $has_inviata = database()->fetchOne('SELECT * FROM em_emails WHERE sent_at IS NOT NULL AND id_template='.prepare($template_promemoria).' AND id_record='.prepare($r['id']));
                $id_template = $template_promemoria;

                if (!$has_inviata && $id_template) {
                    $template = Template::find($id_template);
                    $id = $r['id'];

                    $scadenza = Scadenza::find($id);
                    $documento = $scadenza->documento;

                    $id_documento = $documento->id;
                    $id_anagrafica = $documento->idanagrafica;
                    $id_module = Module::where('name', 'Scadenzario')->first()->id;

                    $fattura_allegata = database()->selectOne('zz_files', 'id', ['id_module' => $id_module, 'id_record' => $id, 'original' => 'Fattura di vendita.pdf'])['id'];

                    // Allego stampa della fattura se non presente
                    if (empty($fattura_allegata)) {
                        $print_predefined = PrintTemplate::where('predefined', 1)->where('id_module', Module::where('name', 'Fatture di vendita')->first()->id)->first();
                        $print = \Prints::render($print_predefined['id'], $id_documento, null, true);
                        $name = 'Fattura di vendita';
                        $upload = \Uploads::upload($print['pdf'], [
                            'name' => $name,
                            'original_name' => $name.'.pdf',
                            'category' => 'Generale',
                            'id_module' => $id_module,
                            'id_record' => $id,
                        ]);

                        $fattura_allegata = database()->selectOne('zz_files', 'id', ['id_module' => $id_module, 'id_record' => $id, 'original' => 'Fattura di vendita.pdf'])['id'];
                    }

                    // Selezione destinatari e invio mail
                    if (!empty($template)) {
                        $creata_mail = false;
                        $emails = [];

                        // Aggiungo email anagrafica
                        if (!empty($documento->anagrafica->email)) {
                            $emails[] = $documento->anagrafica->email;
                            $mail = Mail::build($user, $template, $id);
                            $mail->addReceiver($documento->anagrafica->email);
                            $creata_mail = true;
                        }

                        // Aggiungo email referenti in base alla mansione impostata nel template
                        $mansioni = database()->select('em_mansioni_template', 'idmansione', [], ['id_template' => $template->id]);
                        foreach ($mansioni as $mansione) {
                            $referenti = database()->table('an_referenti')->where('idmansione', $mansione['idmansione'])->where('idanagrafica', $id_anagrafica)->where('email', '!=', '')->get();
                            if (!$referenti->isEmpty() && $creata_mail == false) {
                                $mail = Mail::build($user, $template, $id);
                                $creata_mail = true;
                            }

                            foreach ($referenti as $referente) {
                                if (!in_array($referente->email, $emails)) {
                                    $emails[] = $referente->email;
                                    $mail->addReceiver($referente->email);
                                }
                            }
                        }

                        if (!empty($emails)) {
                            if (!empty($fattura_allegata)) {
                                $mail->addUpload($fattura_allegata);
                            }

                            $mail->save();
                            OperationLog::setInfo('id_email', $mail->id);
                            OperationLog::setInfo('id_module', $id_module);
                            OperationLog::setInfo('id_record', $id);
                            OperationLog::build('send-email');
                        }
                    }
                }
            }

            $rs = database()->fetchArray("SELECT `co_scadenziario`.*, IF(`co_scadenziario`.`data_concordata`, `co_scadenziario`.`data_concordata`, `co_scadenziario`.`scadenza`) AS `data_scadenza` FROM `co_scadenziario` INNER JOIN `co_documenti` ON `co_scadenziario`.`iddocumento`=`co_documenti`.`id` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id` WHERE `co_tipidocumento`.`dir`='entrata' AND `is_fiscale`=1  AND `zz_segments`.`autofatture`=0 AND ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`) AND IF(`co_scadenziario`.`data_concordata`, `co_scadenziario`.`data_concordata`, `co_scadenziario`.`scadenza`) < DATE_SUB(NOW(), INTERVAL ".prepare($giorni_scadenza).' DAY) GROUP BY iddocumento');

            foreach ($rs as $r) {
                $da_inviare = false;
                $destinatario = '';
                $has_inviata = database()->fetchOne('SELECT * FROM em_emails WHERE sent_at IS NOT NULL AND id_template='.prepare($template_1).' AND id_record='.prepare($r['id']));
                $data_sollecito_1 = $has_inviata['sent_at'];
                $id_template = $template_1;
                if (!$has_inviata) {
                    $da_inviare = date('Y-m-d', strtotime($r['scadenza'].' + '.$giorni_scadenza.' days')) < date('Y-m-d') ? true : false;
                } else {
                    $has_inviata = database()->fetchOne('SELECT * FROM em_emails WHERE sent_at IS NOT NULL AND id_template='.prepare($template_2).' AND id_record='.prepare($r['id']));
                    $data_sollecito_2 = $has_inviata['sent_at'];
                    $id_template = $template_2;
                    if (!$has_inviata) {
                        $da_inviare = date('Y-m-d', strtotime($data_sollecito_1.' + '.$giorni_prossimo_sollecito.' days')) < date('Y-m-d') ? true : false;
                    } else {
                        $has_inviata = database()->fetchOne('SELECT * FROM em_emails WHERE sent_at IS NOT NULL AND id_template='.prepare($template_3).' AND id_record='.prepare($r['id']));
                        $data_sollecito_3 = $has_inviata['sent_at'];
                        $id_template = $template_3;
                        if (!$has_inviata) {
                            $da_inviare = date('Y-m-d', strtotime($data_sollecito_2.' + '.$giorni_prossimo_sollecito.' days')) < date('Y-m-d') ? true : false;
                        } else {
                            $has_inviata = database()->fetchOne('SELECT * FROM em_emails WHERE sent_at IS NOT NULL AND id_template='.prepare($template_notifica).' AND id_record='.prepare($r['id']));
                            $id_template = $template_notifica;
                            if (!$has_inviata) {
                                $destinatario = setting('Indirizzo email mancato pagamento dopo i solleciti');
                                $da_inviare = date('Y-m-d', strtotime($data_sollecito_3.' + '.$giorni_prossimo_sollecito.' days')) < date('Y-m-d') && !empty($destinatario) ? true : false;
                            }
                        }
                    }
                }

                if ($da_inviare && $id_template) {
                    $template = Template::find($id_template);
                    $id = $r['id'];

                    $scadenza = Scadenza::find($id);
                    $documento = $scadenza->documento;

                    $id_documento = $documento->id;
                    $id_anagrafica = $documento->idanagrafica;
                    $id_module = Module::where('name', 'Scadenzario')->first()->id;

                    $fattura_allegata = database()->selectOne('zz_files', 'id', ['id_module' => $id_module, 'id_record' => $id, 'original' => 'Fattura di vendita.pdf'])['id'];

                    // Allego stampa della fattura se non presente
                    if (empty($fattura_allegata)) {
                        $print_predefined = PrintTemplate::where('predefined', 1)->where('id_module', Module::where('name', 'Fatture di vendita')->first()->id)->first();
                        $print = \Prints::render($print_predefined['id'], $id_documento, null, true);
                        $name = 'Fattura di vendita';
                        $upload = \Uploads::upload($print['pdf'], [
                            'name' => $name,
                            'original_name' => $name.'.pdf',
                            'category' => 'Generale',
                            'id_module' => $id_module,
                            'id_record' => $id,
                        ]);

                        $fattura_allegata = database()->selectOne('zz_files', 'id', ['id_module' => $id_module, 'id_record' => $id, 'original' => 'Fattura di vendita.pdf'])['id'];
                    }

                    // Selezione destinatari e invio mail
                    if (!empty($template)) {
                        $creata_mail = false;
                        $emails = [];

                        if ($destinatario) {
                            $emails[] = $destinatario;
                            $mail = Mail::build($user, $template, $id);
                            $mail->addReceiver($destinatario);
                            $creata_mail = true;
                        } else {
                            // Aggiungo email anagrafica
                            if (!empty($documento->anagrafica->email)) {
                                $emails[] = $documento->anagrafica->email;
                                $mail = Mail::build($user, $template, $id);
                                $mail->addReceiver($documento->anagrafica->email);
                                $creata_mail = true;
                            }

                            // Aggiungo email referenti in base alla mansione impostata nel template
                            $mansioni = database()->select('em_mansioni_template', 'idmansione', [], ['id_template' => $template->id]);
                            foreach ($mansioni as $mansione) {
                                $referenti = database()->table('an_referenti')->where('idmansione', $mansione['idmansione'])->where('idanagrafica', $id_anagrafica)->where('email', '!=', '')->get();
                                if (!$referenti->isEmpty() && $creata_mail == false) {
                                    $mail = Mail::build($user, $template, $id);
                                    $creata_mail = true;
                                }

                                foreach ($referenti as $referente) {
                                    if (!in_array($referente->email, $emails)) {
                                        $emails[] = $referente->email;
                                        $mail->addReceiver($referente->email);
                                    }
                                }
                            }
                        }

                        if (!empty($emails)) {
                            if (!empty($fattura_allegata)) {
                                $mail->addUpload($fattura_allegata);
                            }

                            $mail->save();
                            OperationLog::setInfo('id_email', $mail->id);
                            OperationLog::setInfo('id_module', $id_module);
                            OperationLog::setInfo('id_record', $id);
                            OperationLog::build('send-email');
                        }
                    }
                }
            }
        }
    }
}
