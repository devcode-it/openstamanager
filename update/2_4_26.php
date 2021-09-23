<?php

use Models\Module;

$modulo_account_email = Module::pool('Account email');
$redirect = base_path().'/editor.php?id_module='.$modulo_account_email->id.'&id_record=';

$database->query("INSERT INTO `zz_oauth2` (`id`, `class`, `client_id`, `client_secret`, `config`, `state`, `access_token`, `refresh_token`, `after_configuration`) SELECT `id`, 'Modules\\\\Emails\\\\OAuth2\\\\Microsoft', `client_id`, `client_secret`, `oauth2_config`, `oauth2_state`, `access_token`, `refresh_token`, CONCAT('".$redirect."', `id`) FROM `em_accounts` WHERE `provider` = 'microsoft' AND `client_id` IS NOT NULL");

$database->query("INSERT INTO `zz_oauth2` (`id`, `class`, `client_id`, `client_secret`, `config`, `state`, `access_token`, `refresh_token`, `after_configuration`) SELECT `id`, 'Modules\\\\Emails\\\\OAuth2\\\\Google', `client_id`, `client_secret`, `oauth2_config`, `oauth2_state`, `access_token`, `refresh_token`, CONCAT('".$redirect."', `id`) FROM `em_accounts` WHERE `provider` = 'google' AND `client_id` IS NOT NULL");

$database->query('UPDATE `em_accounts` SET `id_oauth2` = (SELECT `id` FROM `zz_oauth2` WHERE `zz_oauth2`.`id` = `em_accounts`.`id`)');
