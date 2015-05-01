<?php

$extensionPath = t3lib_extMgm::extPath('patch10011');
return array(
	'JambageCom\\Patch10011\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher' => $extensionPath . 'Classes/Frontend/Configuration/TypoScript/ConditionMatching/ConditionMatcher.php',
	'JambageCom\\Patch10011\\Utility\\ExtensionManagementUtility' => $extensionPath . 'Classes/Utility/ExtensionManagementUtility.php',
);
?>