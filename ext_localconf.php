<?php
defined('TYPO3_MODE') or die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher'] = array(
	'className' => 'JambageCom\\Patch10011\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher'
);

