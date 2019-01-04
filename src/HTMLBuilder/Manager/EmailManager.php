<?php

namespace HTMLBuilder\Manager;

use Prints;
use Translator;

/**
 * Gestione allegati.
 *
 * @since 2.4.2
 */
class EmailManager implements ManagerInterface
{
    /**
     * Gestione "log_email".
     * Esempio: {( "name": "log_email", "id_module": "2", "id_record": "1" )}.
     *
     * @param array $options
     *
     * @return string
     */
    public function manage($options)
    {
        $database = database();

        // Visualizzo il log delle operazioni di invio email
        $operations = $database->fetchArray('SELECT created_at, options, (SELECT name FROM zz_emails WHERE id = id_email) AS email, (SELECT username FROM zz_users WHERE id = id_utente) AS user FROM zz_operations WHERE id_record = '.prepare($options['id_record']).' AND id_module = '.prepare($options['id_module'])." AND op = 'send-email' AND id_email IS NOT NULL ORDER BY created_at DESC");

        if (empty($operations)) {
            return ' ';
        }

        // Codice HTML
        $result = '
<div class="box box-info collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-envelope"></i> '.tr('Email inviate: _NUM_', [
            '_NUM_' => count($operations),
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>';

        foreach ($operations as $operation) {
            $options = json_decode($operation['options'], true);
            $receivers = $options['receivers'];

            $prints = [];
            foreach ($options['prints'] as $print) {
                $print = Prints::get($print);

                $prints[] = $print['title'];
            }

            $attachments = [];
            foreach ($options['attachments'] as $attachment) {
                $attachment = $database->selectOne('zz_files', '*', ['id' => $attachment]);

                $attachments[] = $attachment['name'];
            }

            $result .= '
            <li>
                '.tr('Email "_EMAIL_" inviata il _DATE_ alle _HOUR_ da _USER_', [
                    '_EMAIL_' => $operation['email'],
                    '_DATE_' => Translator::dateToLocale($operation['created_at']),
                    '_HOUR_' => Translator::timeToLocale($operation['created_at']),
                    '_USER_' => $operation['user'],
                ]).'.
                <ul>
                    <li><b>'.tr('Destinatari').'</b>: '.implode(', ', $receivers).'.</li>';

            if (!empty($prints)) {
                $result .= '
                    <li><b>'.tr('Stampe').'</b>: '.implode(', ', $prints).'.</li>';
            }

            if (!empty($attachments)) {
                $result .= '
                    <li><b>'.tr('Allegati').'</b>: '.implode(', ', $attachments).'.</li>';
            }

            $result .= '
                </ul>
            </li>';
        }

        $result .= '
        </ul>
    </div>
</div>';

        return $result;
    }
}
