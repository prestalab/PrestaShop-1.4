SET NAMES 'utf8';

/* PHP:update_module_mailalerts(); */;

/* Backward compatibility */
INSERT INTO `PREFIX_module` (`name`, `active`) VALUES ('backwardcompatibility', 1);

INSERT INTO `PREFIX_hook_module` (`id_module`, `id_hook` , `position`)
(SELECT id_module, 9, (SELECT max_position from (SELECT MAX(position)+1 as max_position FROM `PREFIX_hook_module` WHERE `id_hook` = 9) tmp) FROM `PREFIX_module` WHERE `name` = 'backwardcompatibility');
INSERT INTO `PREFIX_hook_module` (`id_module`, `id_hook` , `position`)
(SELECT id_module, 50, (SELECT max_position from (SELECT MAX(position)+1 as max_position FROM `PREFIX_hook_module` WHERE `id_hook` = 50) tmp) FROM `PREFIX_module` WHERE `name` = 'backwardcompatibility');
INSERT INTO `PREFIX_hook_module` (`id_module`, `id_hook` , `position`)
(SELECT id_module, 54, (SELECT max_position from (SELECT MAX(position)+1 as max_position FROM `PREFIX_hook_module` WHERE `id_hook` = 54) tmp) FROM `PREFIX_module` WHERE `name` = 'backwardcompatibility');

/* Cache system */
ALTER TABLE  `PREFIX_hook_module` ADD  `time` INT( 10 ) NOT NULL DEFAULT  '0';
INSERT INTO `PREFIX_tab` (`id_parent`, `class_name`, `module`, `position`) VALUES
(8, 'AdminCache', '', 13);
INSERT INTO `PREFIX_tab_lang` (`id_tab`, `id_lang`, `name`) SELECT `id_tab`, 1, 'Cache' FROM `PREFIX_tab` WHERE `class_name`='AdminCache';
INSERT INTO `PREFIX_tab_lang` (`id_tab`, `id_lang`, `name`) SELECT `id_tab`, 2, 'Cache' FROM `PREFIX_tab` WHERE `class_name`='AdminCache';
INSERT INTO `PREFIX_tab_lang` (`id_tab`, `id_lang`, `name`) SELECT `id_tab`, 3, 'Cache' FROM `PREFIX_tab` WHERE `class_name`='AdminCache';
INSERT INTO `PREFIX_tab_lang` (`id_tab`, `id_lang`, `name`) SELECT `id_tab`, 4, 'Cache' FROM `PREFIX_tab` WHERE `class_name`='AdminCache';
INSERT INTO `PREFIX_tab_lang` (`id_tab`, `id_lang`, `name`) SELECT `id_tab`, 5, 'Cache' FROM `PREFIX_tab` WHERE `class_name`='AdminCache';
INSERT INTO `PREFIX_tab_lang` (`id_tab`, `id_lang`, `name`) SELECT `id_tab`, 6, 'Кэш' FROM `PREFIX_tab` WHERE `class_name`='AdminCache';
INSERT INTO `PREFIX_access` (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`) SELECT '1', `id_tab`, '1', '1', '1', '1' FROM `PREFIX_tab` WHERE `class_name`='AdminCache';
INSERT INTO `PREFIX_configuration` (`name`, `value`) VALUES
('PL_CACHE_LIST', '86400'),
('PL_CACHE_LONG', '31536000'),
('PL_CACHE_SHORT', '86400');

/* Fix RU PDF */
INSERT IGNORE INTO `ps_configuration` (`name`, `value`) VALUES
('PS_PDF_ENCODING_RU', 'cp1251');
INSERT IGNORE INTO `PREFIX_configuration` (`name`, `value`) VALUES
('PS_PDF_FONT_RU', 'courier');

