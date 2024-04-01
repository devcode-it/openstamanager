-- Aggiunta user-agent nei log
ALTER TABLE `zz_logs` ADD `user_agent` VARCHAR(255) NULL DEFAULT NULL AFTER `ip`;
