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

use Models\OperationLog;
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
            $giorni_scadenza = setting('Ritardo in giorni della scadenza della fattura per invio sollecito pagamento');

            $rs = database()->fetchArray("SELECT `co_scadenziario`.* FROM `co_scadenziario` INNER JOIN `co_documenti` ON `co_scadenziario`.`iddocumento`=`co_documenti`.`id` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id` WHERE `co_tipidocumento`.`dir`='entrata' AND `is_fiscale`=1  AND `zz_segments`.`autofatture`=0 AND ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`) AND `scadenza`<DATE_SUB(NOW(), INTERVAL ".prepare($giorni_scadenza).' DAY)');

            if (sizeof($rs) > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function execute()
    {
        if (setting('Invio solleciti in automatico') > 0) {
            $giorni_scadenza = setting('Ritardo in giorni della scadenza della fattura per invio sollecito pagamento');
            $giorni_ultimo_sollecito = setting("Ritardo in giorni dall'ultima email per invio sollecito pagamento");
            $id_template = setting('Template email invio sollecito');
            $id_user = database()->selectOne('zz_users', 'id', ['idgruppo' => 1, 'enabled' => 1])['id'];
            $user = User::find($id_user);

            if ($id_template) {
                $rs = database()->fetchArray("SELECT `co_scadenziario`.* FROM `co_scadenziario` INNER JOIN `co_documenti` ON `co_scadenziario`.`iddocumento`=`co_documenti`.`id` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id` WHERE `co_tipidocumento`.`dir`='entrata' AND `is_fiscale`=1  AND `zz_segments`.`autofatture`=0 AND ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`) AND `scadenza`<DATE_SUB(NOW(), INTERVAL ".prepare($giorni_scadenza).' DAY)');

                foreach ($rs as $r) {
                    $has_inviata = database()->fetchArray('SELECT * FROM em_emails WHERE id_template='.prepare($id_template).' AND id_record='.prepare($r['id']).' AND sent_at>DATE_SUB(NOW(), INTERVAL '.prepare($giorni_ultimo_sollecito).' DAY)');

                    if (!$has_inviata) {
                        $template = Template::find($id_template);
                        $id = $r['id'];

                        $scadenza = Scadenza::find($id);
                        $documento = $scadenza->documento;

                        $id_documento = $documento->id;
                        $id_anagrafica = $documento->idanagrafica;
                        $id_module = \Modules::get('Scadenzario')->id;

                        $fattura_allegata = database()->selectOne('zz_files', 'id', ['id_module' => $id_module, 'id_record' => $id, 'original' => 'Fattura di vendita.pdf'])['id'];

                        // Allego stampa della fattura se non presente
                        if (empty($fattura_allegata)) {
                            $print_predefined = database()->selectOne('zz_prints', '*', ['predefined' => 1, 'id_module' => \Modules::get('Fatture di vendita')['id']]);

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
            }
        }
    }
}
