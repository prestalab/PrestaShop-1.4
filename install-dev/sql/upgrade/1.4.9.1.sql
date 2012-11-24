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
INSERT INTO `PREFIX_tab` (`id_tab`, `id_parent`, `class_name`, `module`, `position`) VALUES
(89, 8, 'AdminCache', '', 13);
INSERT INTO `PREFIX_tab_lang` (`id_tab`, `id_lang`, `name`) VALUES
(89, 1, 'Cache'),
(89, 2, 'Cache'),
(89, 3, 'Cache'),
(89, 4, 'Cache'),
(89, 5, 'Cache'),
(89, 6, 'Кэш');
INSERT INTO `PREFIX_access` (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`) VALUES ('1', '89', '1', '1', '1', '1');
INSERT INTO `PREFIX_configuration` (`name`, `value`) VALUES
('PL_CACHE_LIST', '86400'),
('PL_CACHE_LONG', '31536000'),
('PL_CACHE_SHORT', '86400');

/* Fix RU PDF */
INSERT IGNORE INTO `ps_configuration` (`name`, `value`) VALUES
('PS_PDF_ENCODING_RU', 'cp1251');
INSERT IGNORE INTO `PREFIX_configuration` (`name`, `value`) VALUES
('PS_PDF_FONT_RU', 'courier');

