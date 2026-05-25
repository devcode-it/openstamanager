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

$database->query('ALTER TABLE `zz_operations` ADD CONSTRAINT `zz_operations_ibfk_5` FOREIGN KEY (`id_email`) REFERENCES `em_emails`(`id`) ON DELETE SET NULL');

// Rinomina foreign keys dopo RENAME TABLE
$fk_renames = [
    ['table' => 'em_templates', 'old_fk' => 'zz_emails_ibfk_1', 'new_fk' => 'em_templates_ibfk_1', 'column' => 'id_module', 'ref_table' => 'zz_modules', 'ref_column' => 'id'],
    ['table' => 'em_templates', 'old_fk' => 'zz_emails_ibfk_2', 'new_fk' => 'em_templates_ibfk_2', 'column' => 'id_smtp', 'ref_table' => 'em_accounts', 'ref_column' => 'id'],
    ['table' => 'em_print_template', 'old_fk' => 'zz_email_print_ibfk_1', 'new_fk' => 'em_print_template_ibfk_1', 'column' => 'id_email', 'ref_table' => 'em_templates', 'ref_column' => 'id'],
    ['table' => 'em_print_template', 'old_fk' => 'zz_email_print_ibfk_2', 'new_fk' => 'em_print_template_ibfk_2', 'column' => 'id_print', 'ref_table' => 'zz_prints', 'ref_column' => 'id'],
];

foreach ($fk_renames as $fk) {
    $exists = $database->fetchOne('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '.prepare($fk['table']).' AND CONSTRAINT_NAME = '.prepare($fk['old_fk']).' AND CONSTRAINT_TYPE = \'FOREIGN KEY\'');
    if (!empty($exists)) {
        $database->query('ALTER TABLE `'.$fk['table'].'` DROP FOREIGN KEY `'.$fk['old_fk'].'`, ADD CONSTRAINT `'.$fk['new_fk'].'` FOREIGN KEY (`'.$fk['column'].'`) REFERENCES `'.$fk['ref_table'].'`(`'.$fk['ref_column'].'`) ON DELETE CASCADE');
    }
}

$database->query('ALTER TABLE `em_templates` DROP FOREIGN KEY `em_templates_ibfk_2`, DROP `id_smtp`');
$database->query('ALTER TABLE `em_templates` ADD CONSTRAINT `em_templates_ibfk_2` FOREIGN KEY (`id_account`) REFERENCES `em_accounts`(`id`) ON DELETE CASCADE;');

$database->query('ALTER TABLE `em_print_template` DROP FOREIGN KEY `em_print_template_ibfk_1`, DROP `id_email`;');
$database->query('ALTER TABLE `em_print_template` ADD CONSTRAINT `em_print_template_ibfk_3` FOREIGN KEY (`id_template`) REFERENCES `em_templates`(`id`) ON DELETE CASCADE;');

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
