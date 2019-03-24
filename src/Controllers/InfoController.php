<?php

namespace Controllers;

use Auth;
use Mail;

class InfoController extends Controller
{
    /** @var int Lunghezza minima della password */
    public static $min_length_password = 8;

    protected static $bugEmail = 'info@openstamanager.com';

    public function info($request, $response, $args)
    {
        $response = $this->twig->render($response, 'info\info.twig', $args);

        return $response;
    }

    public function logs($request, $response, $args)
    {
        $query = 'SELECT * FROM `zz_logs`';
        if (!Auth::admin()) {
            $query .= ' WHERE `id_utente`='.prepare($args['user']['id']);
        }
        $query .= ' ORDER BY `created_at` DESC LIMIT 0, 100';

        $results = $this->database->fetchArray($query);

        $status = Auth::getStatus();
        $data = [];
        foreach ($status as $state) {
            $color = 'warning';

            $color = $state['code'] == $status['success']['code'] ? 'success' : $color;
            $color = $state['code'] == $status['failed']['code'] ? 'danger' : $color;

            $data[$state['code']] = [
                'message' => $state['message'],
                'color' => $color,
            ];
        }

        $args['status'] = $data;
        $args['results'] = $results;

        $response = $this->twig->render($response, 'info\logs.twig', $args);

        return $response;
    }

    public function user($request, $response, $args)
    {
        $user = Auth::user();
        $token = auth()->getToken();

        $anagrafica = $this->database->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica = '.prepare($user['idanagrafica']));
        $api = BASEURL.'/api/?token='.$token;

        $args['api'] = $api;
        $args['sync_link'] = $api.'&resource=sync';

        $response = $this->twig->render($response, 'user\user.twig', $args);

        return $response;
    }

    public function password($request, $response, $args)
    {
        $args['min_length_password'] = self::$min_length_password;

        $response = $this->twig->render($response, 'user\password.twig', $args);

        return $response;
    }

    public function passwordPost($request, $response, $args)
    {
        $user = $args['user'];
        $password = post('password');

        $user->password = $password;
        $user->save();

        flash()->info(tr('Password aggiornata!'));

        $response = $response->withRedirect($this->router->pathFor('user'));

        return $response;
    }

    public function bug($request, $response, $args)
    {
        $args['mail'] = Mail::get();
        $args['bug_email'] = self::$bugEmail;

        $response = $this->twig->render($response, 'info\bug.twig', $args);

        return $response;
    }

    public function bugSend($request, $response, $args)
    {
        $bug_email = self::$bugEmail;

        // Preparazione email
        $mail = new Mail();

        // Destinatario
        $mail->AddAddress($bug_email);

        // Oggetto
        $mail->Subject = 'Segnalazione bug OSM '.$args['version'];

        // Aggiunta dei file di log (facoltativo)
        if (!empty(post('log')) && file_exists(DOCROOT.'/logs/error.log')) {
            $mail->AddAttachment(DOCROOT.'/logs/error.log');
        }

        // Aggiunta della copia del database (facoltativo)
        if (!empty(post('sql'))) {
            $backup_file = DOCROOT.'/Backup OSM '.date('Y-m-d').' '.date('H_i_s').'.sql';
            Backup::database($backup_file);

            $mail->AddAttachment($backup_file);

            flash()->info(tr('Backup del database eseguito ed allegato correttamente!'));
        }

        // Aggiunta delle informazioni di base sull'installazione
        $infos = [
            'Utente' => $args['user']['username'],
            'IP' => get_client_ip(),
            'Versione OSM' => $args['version'].' ('.($args['revision'] ? $args['revision'] : 'In sviluppo').')',
            'PHP' => phpversion(),
        ];

        // Aggiunta delle informazioni sul sistema (facoltativo)
        if (!empty(post('info'))) {
            $infos['Sistema'] = $_SERVER['HTTP_USER_AGENT'].' - '.getOS();
        }

        // Completamento del body
        $body = post('body').'<hr>';
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
        if (!empty(post('sql'))) {
            delete($backup_file);
        }

        $response = $response->withRedirect($this->router->pathFor('bug'));

        return $response;
    }
}
