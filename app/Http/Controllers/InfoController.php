<?php

namespace App\Http\Controllers;

use Backup;
use Illuminate\Http\Request;
use Modules\Emails\Account;
use Notifications\EmailNotification;
use Update;

class InfoController extends Controller
{
    protected static $bugEmail = 'info@openstamanager.com';

    public function info()
    {
        return view('info');
    }

    public function bug()
    {
        $account = Account::where('predefined', true)->first();

        return view('bug', [
            'mail' => $account,
            'bug_email' => self::$bugEmail,
        ]);
    }

    public function send(Request $request)
    {
        $user = auth()->user();
        $bug_email = self::$bugEmail;

        // Preparazione email
        $mail = new EmailNotification();

        // Destinatario
        $mail->AddAddress($bug_email);

        // Oggetto
        $mail->Subject = 'Segnalazione bug OSM '.Update::getVersion();

        // Aggiunta dei file di log (facoltativo)
        $log_file = base_path('/logs/error.log');
        if (!empty($request->input('log')) && file_exists($log_file)) {
            $mail->AddAttachment($log_file);
        }

        // Aggiunta della copia del database (facoltativo)
        if (!empty($request->input('sql'))) {
            $backup_file = base_path('backup/Backup OSM '.date('Y-m-d').' '.date('H_i_s').'.sql');
            Backup::database($backup_file);

            $mail->AddAttachment($backup_file);

            flash()->info(tr('Backup del database eseguito ed allegato correttamente!'));
        }

        // Aggiunta delle informazioni di base sull'installazione
        $infos = [
            'Utente' => $user['username'],
            'IP' => get_client_ip(),
            'Versione OSM' => Update::getVersion().' ('.(Update::getRevision() ?: tr('In sviluppo')).')',
            'PHP' => phpversion(),
        ];

        // Aggiunta delle informazioni sul sistema (facoltativo)
        if (!empty($request->input('info'))) {
            $infos['Sistema'] = $_SERVER['HTTP_USER_AGENT'].' - '.getOS();
        }

        // Completamento del body
        $body = $request->input('body').'<hr>';
        foreach ($infos as $key => $value) {
            $body .= '<p>'.$key.': '.$value.'</p>';
        }

        $mail->Body = $body;

        $mail->AltBody = 'Questa email arriva dal modulo bug di segnalazione bug di OSM';

        // Invio mail
        if (!$mail->send()) {
            flash()->error(tr("Errore durante l'invio della segnalazione").': '.$mail->ErrorInfo);
        } else {
            flash()->info(tr('Email inviata correttamente!'));
        }

        // Rimozione del dump del database
        if (!empty($request->input('sql'))) {
            delete($backup_file);
        }

        return redirect(route('bug'));
    }
}
