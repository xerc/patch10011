<?php

########################################################################
# Extension Manager/Repository config file for ext "patch10011".
#
# Auto generated 20-04-2010 20:49
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TypoScript Condition to check for extensions',
	'description' => 'A TypoScript will be only executed if a named extension has been installed in a given version number. This implements the patch for bug#10011 which has been pending in the Core list for years.',
	'category' => 'be',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'jambage.com',
	'version' => '0.2.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-7.4.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:6:{s:9:"ChangeLog";s:4:"6ce1";s:27:"class.patch10011_extmgm.php";s:4:"18cd";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"045c";s:14:"doc/manual.sxw";s:4:"5806";s:51:"xclass/class.user_t3lib_matchcondition_frontend.php";s:4:"b3f6";}',
	'suggests' => array(
	),
);

?>