<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/matchcondition/class.t3lib_matchcondition_frontend.php'] = t3lib_extMgm::extPath('patch10011') . 'xclass/class.user_t3lib_matchcondition_frontend.php';

?>