-- Lo stato 'FAT' è da considerarsi completato 
UPDATE `in_statiintervento` SET `completato` = '1' WHERE `in_statiintervento`.`idstatointervento` = 'FAT';