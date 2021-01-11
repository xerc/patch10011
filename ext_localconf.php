<?php
defined('TYPO3_MODE') || die('Access denied.');

if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '10.0.0', '<')
) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher'] = [
        'className' => 'JambageCom\\Patch10011\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher'
    ];
}

