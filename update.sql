# Project:    Web Reference Database (refbase) <http://www.refbase.net>
# Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
#             original author(s).
#
#             This code is distributed in the hope that it will be useful,
#             but WITHOUT ANY WARRANTY. Please see the GNU General Public
#             License for more details.
#
# File:       ./update.sql
# Repository: $HeadURL$
# Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
#
# Created:    01-Mar-05, 16:54
# Modified:   $Date$
#             $Author$
#             $Revision$

# This MySQL database structure file will update any refbase v0.8.0 database to v0.9.1

# IMPORTANT: - If possible, use 'update.php' instead of this file to update an
#              existing refbase installation (v0.8.0 or above), please see the
#              'UPDATE' file for further information.
#            - Do NOT use this file in an attempt to update refbase v0.9.0 to v0.9.1,
#              please use 'update.php' instead.

# --------------------------------------------------------

#
# replace table `formats`
#

DROP TABLE IF EXISTS `formats`;
CREATE TABLE `formats` (
  `format_id` mediumint(8) unsigned NOT NULL auto_increment,
  `format_name` varchar(100) default NULL,
  `format_type` enum('export','import','cite') NOT NULL default 'export',
  `format_enabled` enum('true','false') NOT NULL default 'true',
  `format_spec` varchar(255) default NULL,
  `order_by` varchar(25) default NULL,
  `depends_id` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`format_id`),
  KEY `format_name` (`format_name`)
) TYPE=MyISAM;

#
# data for table `formats`
#

INSERT INTO `formats` VALUES (1, 'MODS XML', 'import', 'true', 'bibutils/import_modsxml2refbase.php', '06', 2),
(2, 'MODS XML', 'export', 'true', 'export_modsxml.php', '06', 1),
(3, 'Text (CSV)', 'export', 'false', 'export_textcsv.php', '07', 1),
(4, 'BibTeX', 'import', 'true', 'bibutils/import_bib2refbase.php', '01', 2),
(5, 'BibTeX', 'export', 'true', 'bibutils/export_xml2bib.php', '01', 2),
(6, 'Endnote', 'import', 'true', 'bibutils/import_end2refbase.php', '02', 2),
(7, 'Endnote XML', 'import', 'true', 'bibutils/import_endx2refbase.php', '02', 2),
(8, 'Endnote', 'export', 'true', 'bibutils/export_xml2end.php', '02', 2),
(9, 'Pubmed Medline', 'import', 'true', 'import_medline2refbase.php', '08', 1),
(10, 'Pubmed XML', 'import', 'true', 'bibutils/import_med2refbase.php', '09', 2),
(11, 'RIS', 'import', 'true', 'import_ris2refbase.php', '03', 1),
(12, 'RIS', 'export', 'true', 'bibutils/export_xml2ris.php', '03', 2),
(13, 'ISI', 'import', 'true', 'import_isi2refbase.php', '04', 1),
(14, 'ISI', 'export', 'true', 'bibutils/export_xml2isi.php', '04', 2),
(15, 'CSA', 'import', 'true', 'import_csa2refbase.php', '05', 1),
(16, 'Copac', 'import', 'true', 'bibutils/import_copac2refbase.php', '10', 2),
(17, 'SRW XML', 'export', 'true', 'export_srwxml.php', '11', 1),
(18, 'ODF XML', 'export', 'true', 'export_odfxml.php', '12', 1),
(19, 'OpenSearch RSS', 'export', 'false', 'export_osrss.php', '13', 1),
(20, 'html', 'cite', 'true', 'formats/cite_html.php', '14', 1),
(21, 'RTF', 'cite', 'true', 'formats/cite_rtf.php', '15', 1),
(22, 'PDF', 'cite', 'true', 'formats/cite_pdf.php', '16', 1),
(23, 'LaTeX', 'cite', 'true', 'formats/cite_latex.php', '17', 1),
(24, 'Markdown', 'cite', 'true', 'formats/cite_markdown.php', '18', 1),
(25, 'ASCII', 'cite', 'true', 'formats/cite_ascii.php', '19', 1),
(26, 'RefWorks', 'import', 'true', 'import_refworks2refbase.php', '20', 1),
(27, 'SciFinder', 'import', 'true', 'import_scifinder2refbase.php', '21', 1),
(28, 'Word XML', 'export', 'true', 'bibutils/export_xml2word.php', '22', 2),
(29, 'LaTeX .bbl', 'cite', 'true', 'formats/cite_latex_bbl.php', '23', 1),
(30, 'Text (Tab-Delimited)', 'import', 'true', 'import_tabdelim2refbase.php', '24', 1),
(31, 'CrossRef XML', 'import', 'true', 'import_crossref2refbase.php', '25', 1);

# --------------------------------------------------------

#
# update table `languages`
#

INSERT INTO `languages` VALUES (NULL, 'fr', 'true', '3'),
(NULL, 'es', 'false', '4'),
(NULL, 'cn', 'true', '5');

UPDATE `languages` SET `language_enabled` = 'true' WHERE `language_name` = 'de';

# --------------------------------------------------------

#
# update table `refs`
#

UPDATE `refs` SET `thesis` = NULL WHERE `thesis` = '';

UPDATE `refs` SET `type` = 'Conference Article' WHERE `type` RLIKE '^(Unsupported: )?Conference Proceeding$';
UPDATE `refs` SET `type` = 'Miscellaneous' WHERE `type` RLIKE '^(Unsupported: )?Generic$';
UPDATE `refs` SET `type` = 'Newspaper Article' WHERE `type` RLIKE '^(Unsupported: )?Newspaper$';
UPDATE `refs` SET `type` = 'Software' WHERE `type` RLIKE '^(Unsupported: )?Computer Program$';
UPDATE `refs` SET `type` = REPLACE(`type`, "Unsupported: ", "") WHERE `type` RLIKE "^Unsupported: (Abstract|Conference (Article|Volume)|Magazine Article|Manual|Miscellaneous|Newspaper Article|Patent|Report|Software)$";

# --------------------------------------------------------

#
# update table `styles`
#

UPDATE `styles` SET `style_spec` = REPLACE(`style_spec`,"cite_","styles/cite_") WHERE `style_spec` RLIKE "^cite_";

INSERT INTO `styles` VALUES (NULL, 'Ann Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B050', 1),
(NULL, 'J Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B060', 1),
(NULL, 'APA', 'true', 'styles/cite_APA.php', 'A010', 1),
(NULL, 'MLA', 'true', 'styles/cite_MLA.php', 'A030', 1);

UPDATE `styles` SET `order_by` = 'C010' WHERE `style_name` = 'Text Citation';
UPDATE `styles` SET `order_by` = 'B010' WHERE `style_name` = 'Polar Biol';
UPDATE `styles` SET `order_by` = 'B020' WHERE `style_name` = 'Mar Biol';
UPDATE `styles` SET `order_by` = 'B030' WHERE `style_name` = 'MEPS';
UPDATE `styles` SET `order_by` = 'B040' WHERE `style_name` = 'Deep Sea Res';

# --------------------------------------------------------

#
# update table `types`
#

UPDATE `types` SET `order_by` = '01' WHERE `type_name` = 'Journal Article';
UPDATE `types` SET `order_by` = '02' WHERE `type_name` = 'Abstract';
UPDATE `types` SET `order_by` = '03' WHERE `type_name` = 'Book Chapter';
UPDATE `types` SET `order_by` = '04' WHERE `type_name` = 'Book Whole';
UPDATE `types` SET `order_by` = '05' WHERE `type_name` = 'Conference Article';
UPDATE `types` SET `order_by` = '06' WHERE `type_name` = 'Conference Volume';
UPDATE `types` SET `order_by` = '07' WHERE `type_name` = 'Journal';
UPDATE `types` SET `order_by` = '08' WHERE `type_name` = 'Magazine Article';
UPDATE `types` SET `order_by` = '09' WHERE `type_name` = 'Manual';
UPDATE `types` SET `order_by` = '10' WHERE `type_name` = 'Manuscript';
UPDATE `types` SET `order_by` = '11' WHERE `type_name` = 'Map';
UPDATE `types` SET `order_by` = '12' WHERE `type_name` = 'Miscellaneous';
UPDATE `types` SET `order_by` = '13' WHERE `type_name` = 'Newspaper Article';
UPDATE `types` SET `order_by` = '14' WHERE `type_name` = 'Patent';
UPDATE `types` SET `order_by` = '15' WHERE `type_name` = 'Report';
UPDATE `types` SET `order_by` = '16' WHERE `type_name` = 'Software';

INSERT INTO `types` VALUES (NULL, 'Abstract', 'true', 2, '02'),
(NULL, 'Conference Article', 'true', 2, '05'),
(NULL, 'Conference Volume', 'true', 3, '06'),
(NULL, 'Magazine Article', 'true', 1, '08'),
(NULL, 'Manual', 'true', 3, '09'),
(NULL, 'Miscellaneous', 'true', 3, '12'),
(NULL, 'Newspaper Article', 'true', 1, '13'),
(NULL, 'Patent', 'true', 3, '14'),
(NULL, 'Report', 'true', 3, '15'),
(NULL, 'Software', 'true', 3, '16');

# --------------------------------------------------------

#
# add table `user_options`
#

DROP TABLE IF EXISTS `user_options`;
CREATE TABLE `user_options` (
  `option_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `export_cite_keys` enum('yes','no') NOT NULL default 'yes',
  `autogenerate_cite_keys` enum('yes','no') NOT NULL default 'yes',
  `prefer_autogenerated_cite_keys` enum('no','yes') NOT NULL default 'no',
  `use_custom_cite_key_format` enum('no','yes') NOT NULL default 'no',
  `cite_key_format` varchar(255) default NULL,
  `uniquify_duplicate_cite_keys` enum('yes','no') NOT NULL default 'yes',
  `nonascii_chars_in_cite_keys` enum('transliterate','strip','keep') default NULL,
  `use_custom_text_citation_format` enum('no','yes') NOT NULL default 'no',
  `text_citation_format` varchar(255) default NULL,
  `records_per_page` smallint(5) unsigned default NULL,
  `main_fields` text,
  PRIMARY KEY  (`option_id`),
  KEY `user_id` (`user_id`)
) TYPE=MyISAM;

#
# data for table `user_options`
#

INSERT INTO `user_options` VALUES (1, 0, 'yes', 'yes', 'no', 'no', '<:authors:><:year:>', 'yes', NULL, 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>', NULL, 'author, title, publication, keywords, abstract'),
(2, 1, 'yes', 'yes', 'no', 'no', '<:firstAuthor:><:year:>', 'yes', NULL, 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>', NULL, 'author, title, publication, keywords, abstract');

# --------------------------------------------------------

#
# alter table `user_permissions`
#

ALTER TABLE `user_permissions` ADD COLUMN `allow_browse_view` ENUM('yes', 'no') NOT NULL AFTER `allow_print_view`;

#
# update table `user_permissions`
#

UPDATE `user_permissions` SET `allow_browse_view` = 'no';

