<?php

// Correzione zz_operations
use Models\User;
use Modules\Emails\Mail;
use Modules\Emails\Template;

$database->query('ALTER TABLE `zz_operations` DROP FOREIGN KEY `zz_operations_ibfk_3`');
$logs = $database->fetchArray("SELECT * FROM `zz_operations` WHERE `op` = 'send-email'");

$database->query('UPDATE `zz_operations` SET `id_email` = NULL');
foreach ($logs as $log) {
    $user = User::find($log['id_utente']);
    $template = Template::find($log['id_email']);
    if (empty($template)) {
        continue;
    }

    $mail = Mail::build($user, $template, $log['id_record']);
    $mail->resetPrints();

    $options = json_decode((string) $log['options'], true);

    foreach ($options['receivers'] as $receiver) {
        $mail->addReceiver($receiver);
    }

    foreach ($options['attachments'] as $upload) {
        $mail->addUpload($upload);
    }

    foreach ($options['prints'] as $print) {
        $mail->addPrint($print);
    }

    $sent_at = $log['created_at'] ?: date('Y-m-d H:i:s');
    $mail->created_at = $sent_at;
    $mail->sent_at = $sent_at;

    $mail->save();

    $database->query('UPDATE `zz_operations` SET `id_email` = '.prepare($mail->id).' WHERE `id_module` = '.prepare($log['id_module']).' AND `id_email` = '.prepare($log['id_email']).' AND `id_record` = '.prepare($log['id_record']).' AND `options` = '.prepare($log['options']).' AND `created_at` = '.prepare($log['created_at']));
}

$database->query('ALTER TABLE `zz_operations` ADD FOREIGN KEY (`id_email`) REFERENCES `em_emails`(`id`) ON DELETE SET NULL');

// Aggiunta permessi alla gestione documentale
$gruppi = $database->fetchArray('SELECT `id` FROM `zz_groups`');
$viste = $database->fetchArray('SELECT `id` FROM `do_categorie`');

$array = [];
foreach ($viste as $vista) {
    foreach ($gruppi as $gruppo) {
        $array[] = [
            'id_gruppo' => $gruppo['id'],
            'id_categoria' => $vista['id'],
        ];
    }
}
if (!empty($array)) {
    $database->insert('do_permessi', $array);
}

// File e cartelle deprecate
$files = [
    'src/API.php',
    'src/Mail.php',
    'modules/utenti/api',
    'modules/stato_servizi/api',
    'modules/stati_preventivo/api',
    'modules/stati_intervento/api',
    'modules/tipi_intervento/api',
    'modules/stati_contratto/api',
    'modules/articoli/api',
    'modules/anagrafiche/api',
    'modules/interventi/api/update.php',
    'modules/interventi/api/retrieve.php',
    'modules/interventi/api/delete.php',
    'modules/interventi/api/create.php',
    'modules/aggiornamenti/api',
    'plugins/exportFE/src/Connection.php',
    'modules/contratti/plugins/contratti.ordiniservizio.interventi.php ',
    'modules/contratti/plugins/contratti.ordiniservizio.php',
    'templates/contratti_cons/body.php',
    'templates/preventivi_cons/body.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
