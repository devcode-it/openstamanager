```sql
ALTER TABLE zz_users ADD COLUMN remember_token VARCHAR (255) AFTER password;

ALTER TABLE `zz_widgets` DROP `class`, ADD `class` varchar(255) NOT NULL;

UPDATE `zz_widgets` SET `class` = 'App\\OSM\\Widgets\\Retro\\ModalWidget' WHERE `more_link_type` = 'popup';
UPDATE `zz_widgets` SET `class` = 'App\\OSM\\Widgets\\Retro\\LinkWidget' WHERE `more_link_type` = 'link';
UPDATE `zz_widgets` SET `class` = 'App\\OSM\\Widgets\\Retro\\StatsWidget' WHERE `more_link_type` = 'javascript';
UPDATE `zz_widgets` SET `class` = 'App\\OSM\\Widgets\\Retro\\StatsWidget' WHERE `type` = 'print';
UPDATE `zz_widgets` SET `class` = 'App\\OSM\\Widgets\\Retro\\StatsWidget' WHERE `class` = '';
UPDATE `zz_widgets` SET `more_link` = `php_include` WHERE `more_link` = '';
UPDATE `zz_widgets` SET `class` = 'App\\OSM\\Widgets\\Retro\\ModalWidget' WHERE `name` = 'Stampa calendario';

UPDATE `zz_widgets` SET `more_link` = REPLACE(`more_link`, 'plugins/', 'modules/');

ALTER TABLE `zz_widgets` DROP `print_link`, DROP `more_link_type`, DROP `php_include`;

UPDATE `zz_widgets` SET `more_link` = REPLACE(`more_link`, './', '/');

UPDATE `zz_widgets` SET `class` = 'Modules\\Dashboard\\NotificheWidget', `more_link` = '' WHERE `zz_widgets`.`name` = 'Note interne';
```
