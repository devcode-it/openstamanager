<?php

// Correzione zz_operations
use Models\Mail;
use Models\MailTemplate;
use Models\User;

$database->query('ALTER TABLE `zz_operations` DROP FOREIGN KEY `zz_operations_ibfk_3`');
$logs = $database->fetchArray("SELECT * FROM `zz_operations` WHERE `op` = 'send-email'");
foreach ($logs as $log) {
    $user = User::find($log['id_utente']);
    $template = MailTemplate::find($log['id_email']);
    $mail = Mail::build($user, $template, $log['id_record']);

    $options = json_decode($log['options'], true);
    $mail->attachments = $options['attachments'] ?: [];
    $mail->prints = $options['prints'] ?: [];

    foreach ($options['receivers'] as $receiver){
        $mail->addReceiver($receiver);
    }

    $mail->created_at = $log['created_at'];
    $mail->sent_at = $log['created_at'];

    $mail->save();

    $database->query('UPDATE `zz_operations` SET `id_email` = '.prepare($mail->id).' WHERE `id_module` = '.prepare($log['id_module']).' AND `id_email` = '.prepare($log['id_email']).' AND `id_record` = '.prepare($log['id_record']).' AND `options` = '.prepare($log['options']).' AND `created_at` = '.prepare($log['created_at']));
}

$database->query('ALTER TABLE `zz_operations` ADD FOREIGN KEY (`id_email`) REFERENCES `em_emails`(`id`) ON DELETE SET NULL');

// File e cartelle deprecate
$files = [
    'src\API.php',
    'src\Mail.php',
    'modules\utenti\api',
    'modules\stato_servizi\api',
    'modules\stati_preventivo\api',
    'modules\stati_intervento\api',
    'modules\tipi_intervento\api',
    'modules\stati_contratto\api',
    'modules\articoli\api',
    'modules\anagrafiche\api',
    'modules\interventi\api\update.php',
    'modules\interventi\api\retrieve.php',
    'modules\interventi\api\delete.php',
    'modules\interventi\api\create.php',
    'modules\aggiornamenti\api',
    'plugins\exportFE\src\Connection.php',
    'modules\contratti\plugins\addfattura.php',
    'modules\contratti\plugins\contratti.fatturaordiniservizio.php',
    'modules\contratti\plugins\contratti.ordiniservizio.interventi.php ',
    'modules\contratti\plugins\contratti.ordiniservizio.php',
    'templates\contratti_cons\body.php',
    'templates\preventivi_cons\body.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
