<?php

########################################################################
# Extension Manager/Repository config file for ext "patch10011".
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TypoScript Condition to check for extensions',
	'description' => 'A TypoScript will be only executed if a named extension has been installed in a given version number. This implements the patch for bug#10011 which has been pending in the Core list for some years.',
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
	'version' => '0.3.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-8.99.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

