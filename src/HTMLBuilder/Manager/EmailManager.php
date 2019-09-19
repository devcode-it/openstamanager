<?php

namespace HTMLBuilder\Manager;

use Modules\Emails\Mail;
use Modules;

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
        // Visualizzo il log delle operazioni di invio email
        $emails = Mail::whereRaw('id IN (SELECT id_email FROM zz_operations WHERE id_record = '.prepare($options['id_record']).' AND id_module = '.prepare($options['id_module']).' AND id_email IS NOT NULL)')
            ->orderByDesc('created_at')
            ->get();

        if ($emails->isEmpty()) {
            return ' ';
        }

        // Codice HTML
        $result = '
<div class="box box-info collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-envelope"></i> '.tr('Email inviate: _NUM_', [
            '_NUM_' => $emails->count(),
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>';

        foreach ($emails as $email) {
            $receivers = $email->receivers->pluck('address')->toArray();

            $prints = [];
            $list = $email->prints;
            foreach ($list as $print) {
                $prints[] = $print['title'];
            }

            $uploads = [];
            $list = $email->uploads;
            foreach ($list as $upload) {
                $uploads[] = $upload['name'];
            }

            $sent = !empty($email['sent_at']) ? tr('inviata il _DATE_ alle _HOUR_', [
                '_DATE_' => dateFormat($email['sent_at']),
                '_HOUR_' => timeFormat($email['sent_at']),
            ]) : tr('in coda di invio');

            $descrizione = Modules::link('Stato email', $email->id, tr('Email "_EMAIL_" da _USER_', [
                '_EMAIL_' => $email->template->name,
                '_USER_' => $email->user->username,
            ]));

            $result .= '
            <li>
                '.$descrizione.' ('.$sent.').
                <ul>
                    <li><b>'.tr('Destinatari').'</b>: '.implode(', ', $receivers).'.</li>';

            if (!empty($prints)) {
                $result .= '
                    <li><b>'.tr('Stampe').'</b>: '.implode(', ', $prints).'.</li>';
            }

            if (!empty($uploads)) {
                $result .= '
                    <li><b>'.tr('Allegati').'</b>: '.implode(', ', $uploads).'.</li>';
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
