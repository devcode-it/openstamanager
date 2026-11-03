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

namespace HTMLBuilder\Manager;

use Models\Module;
use Modules\SMS\Sms;

/**
 * Gestione SMS e WhatsApp.
 *
 * @since 2.4.2
 */
class SMSManager implements ManagerInterface
{
    /**
     * Gestione "log_sms".
     * Esempio: {( "name": "log_sms", "id_module": "2", "id_record": "1" )}.
     *
     * @param array $options
     *
     * @return string
     */
    public function manage($options)
    {
        // Verifico se il modulo SMS è installato
        $sms_module = Module::where('name', 'Template SMS')->first();
        if (!$sms_module || !$sms_module->id) {
            return ' ';
        }

        // Visualizzo il log delle comunicazioni SMS e WhatsApp
        $messages = Sms::whereRaw('id_template IN (SELECT id FROM sms_templates WHERE id_module = '.prepare($options['id_module']).')')
            ->where('id_record', $options['id_record'])
            ->orderBy('created_at', 'DESC')
            ->get();

        if ($messages->isEmpty()) {
            return ' ';
        }

        // Codice HTML
        $result = '\n<div class="card card-info collapsable collapsed-card">\n    <div class="card-header with-border">\n        <h3 class="card-title"><i class="fa fa-comment"></i> '.tr('SMS e WhatsApp: _NUM_', [
            '_NUM_' => $messages->count(),
        ]).'</h3>\n        <div class="card-tools pull-right">\n            <button type="button" class="btn btn-card-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>\n        </div>\n    </div>\n    <div class="card-body">\n        <ul>';

        foreach ($messages as $message) {
            $message_type = $message->message_type ?: 'sms';
            $is_whatsapp = in_array($message_type, ['whatsapp', 'whatsapp_link']);
            $channel = $is_whatsapp ? tr('WhatsApp') : tr('SMS');
            $icon = $is_whatsapp ? 'fa-whatsapp' : 'fa-comment';

            if ($message_type === 'whatsapp_link') {
                $status = !empty($message['sent_at']) ? tr('aperto il _DATE_ alle _HOUR_', [
                    '_DATE_' => dateFormat($message['sent_at']),
                    '_HOUR_' => timeFormat($message['sent_at']),
                ]) : tr('apertura non completata');
            } elseif (!empty($message['sent_at'])) {
                $status = tr('inviato il _DATE_ alle _HOUR_', [
                    '_DATE_' => dateFormat($message['sent_at']),
                    '_HOUR_' => timeFormat($message['sent_at']),
                ]);
            } elseif (!empty($message['failed_at'])) {
                $status = tr('invio non riuscito');
            } else {
                $status = tr('in coda di invio');
            }

            $description = \Modules::link('Coda SMS', $message->id, tr('_CHANNEL_ "_TEMPLATE_" da _USER_', [
                '_CHANNEL_' => $channel,
                '_TEMPLATE_' => $message->template ? $message->template->name : tr('Template eliminato'),
                '_USER_' => $message->user ? $message->user->username : tr('Utente eliminato'),
            ]));

            $result .= '\n            <li>\n                <i class="fa '.$icon.'"></i> '.$description.' ('.$status.').\n                <ul>\n                    <li><b>'.tr('Destinatario').'</b>: '.htmlspecialchars((string) $message->cellulare, ENT_QUOTES, 'UTF-8').'.</li>\n                </ul>\n            </li>';
        }

        $result .= '\n        </ul>\n    </div>\n</div>';

        return $result;
    }
}
