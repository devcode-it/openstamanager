UPDATE `in_interventi` SET `id_contratto` = (SELECT `idcontratto` FROM `co_promemoria` WHERE `idintervento` = `in_interventi`.`id`);
