UPDATE `co_documenti` SET `data_competenza` = `data` WHERE `data_competenza` = '0000-00-00' OR `data_competenza` IS NULL;
