<?php
namespace JambageCom\Patch10011\Frontend\Configuration\TypoScript\ConditionMatching;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Matching TypoScript conditions for frontend disposal.
 *
 * Used with the TypoScript parser.
 * Matches browserinfo, IPnumbers for use with templates
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class ConditionMatcher extends \TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher {

	protected function evaluateCondition($string) {
		$result = parent::evaluateCondition($string);
		return $result;
	}

    /**
     * Evaluates a TypoScript condition given as input, eg. "[applicationContext = Production][...(other condition)...]"
     *
     * @param string $key The condition to match against its criteria.
     * @param string $value
     * @return NULL|bool Result of the evaluation; NULL if condition could not be evaluated
     */
    protected function evaluateConditionCommon($key, $value)
    {
        $lowerKey = strtolower($key);
        if ($lowerKey === 'browser' || $lowerKey === 'device' || $lowerKey === 'version' || $lowerKey === 'system' || $lowerKey === 'useragent') {
            if (version_compare(TYPO3_version, '7.0.0', '>=')) {
                GeneralUtility::deprecationLog(
                    'Usage of client related conditions (browser, device, version, system, useragent) is deprecated since 7.0.'
                );
            }
            $browserInfo = $this->getBrowserInfo(GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
        }
        $keyParts = GeneralUtility::trimExplode('|', $key);
        switch ($keyParts[0]) {
            case 'applicationContext':
                $values = GeneralUtility::trimExplode(',', $value, true);
                $currentApplicationContext = GeneralUtility::getApplicationContext();
                foreach ($values as $applicationContext) {
                    if ($this->searchStringWildcard($currentApplicationContext, $applicationContext)) {
                        return true;
                    }
                }
                return false;
                break;
            case 'browser':
                $values = GeneralUtility::trimExplode(',', $value, true);
                // take all identified browsers into account, eg chrome deliver
                // webkit=>532.5, chrome=>4.1, safari=>532.5
                // so comparing string will be
                // "webkit532.5 chrome4.1 safari532.5"
                $all = '';
                foreach ($browserInfo['all'] as $key => $value) {
                    $all .= $key . $value . ' ';
                }
                foreach ($values as $test) {
                    if (stripos($all, $test) !== false) {
                        return true;
                    }
                }
                return false;
                break;
            case 'version':
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    if (strcspn($test, '=<>') == 0) {
                        switch ($test[0]) {
                            case '=':
                                if (doubleval(substr($test, 1)) == $browserInfo['version']) {
                                    return true;
                                }
                                break;
                            case '<':
                                if (doubleval(substr($test, 1)) > $browserInfo['version']) {
                                    return true;
                                }
                                break;
                            case '>':
                                if (doubleval(substr($test, 1)) < $browserInfo['version']) {
                                    return true;
                                }
                                break;
                        }
                    } elseif (strpos(' ' . $browserInfo['version'], $test) == 1) {
                        return true;
                    }
                }
                return false;
                break;
            case 'system':
                $values = GeneralUtility::trimExplode(',', $value, true);
                // Take all identified systems into account, e.g. mac for iOS, Linux
                // for android and Windows NT for Windows XP
                $allSystems = ' ' . implode(' ', $browserInfo['all_systems']);
                foreach ($values as $test) {
                    if (stripos($allSystems, $test) !== false) {
                        return true;
                    }
                }
                return false;
                break;
            case 'device':
                if (!isset($this->deviceInfo)) {
                    $this->deviceInfo = $this->getDeviceType(GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
                }
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    if ($this->deviceInfo == $test) {
                        return true;
                    }
                }
                return false;
                break;
            case 'useragent':
                $test = trim($value);
                if ($test !== '') {
                    return $this->searchStringWildcard((string)$browserInfo['useragent'], $test);
                } else {
                    return false;
                }
                break;
            case 'language':
                if (GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE') === $value) {
                    return true;
                }
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    if (preg_match('/^\\*.+\\*$/', $test)) {
                        $allLanguages = preg_split('/[,;]/', GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
                        if (in_array(substr($test, 1, -1), $allLanguages)) {
                            return true;
                        }
                    } elseif (GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE') == $test) {
                        return true;
                    }
                }
                return false;
                break;
            case 'IP':
                if ($value === 'devIP') {
                    $value = trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
                }

                return (bool)GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $value);
                break;
            case 'hostname':
                return (bool)GeneralUtility::cmpFQDN(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $value);
                break;
            case 'hour':
            case 'minute':
            case 'month':
            case 'year':
            case 'dayofweek':
            case 'dayofmonth':
            case 'dayofyear':
                // In order to simulate time properly in templates.
                $theEvalTime = $GLOBALS['SIM_EXEC_TIME'];
                switch ($key) {
                    case 'hour':
                        $theTestValue = date('H', $theEvalTime);
                        break;
                    case 'minute':
                        $theTestValue = date('i', $theEvalTime);
                        break;
                    case 'month':
                        $theTestValue = date('m', $theEvalTime);
                        break;
                    case 'year':
                        $theTestValue = date('Y', $theEvalTime);
                        break;
                    case 'dayofweek':
                        $theTestValue = date('w', $theEvalTime);
                        break;
                    case 'dayofmonth':
                        $theTestValue = date('d', $theEvalTime);
                        break;
                    case 'dayofyear':
                        $theTestValue = date('z', $theEvalTime);
                        break;
                }
                $theTestValue = (int)$theTestValue;
                // comp
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($test)) {
                        $test = '=' . $test;
                    }
                    if ($this->compareNumber($test, $theTestValue)) {
                        return true;
                    }
                }
                return false;
                break;
            case 'compatVersion':
                return GeneralUtility::compat_version($value);
                break;
            case 'loginUser':
                if ($this->isUserLoggedIn()) {
                    $values = GeneralUtility::trimExplode(',', $value, true);
                    foreach ($values as $test) {
                        if ($test == '*' || (string)$this->getUserId() === (string)$test) {
                            return true;
                        }
                    }
                } elseif ($value === '') {
                    return true;
                }
                return false;
                break;
            case 'page':
                if ($keyParts[1]) {
                    $page = $this->getPage();
                    $property = $keyParts[1];
                    if (!empty($page) && isset($page[$property]) && (string)$page[$property] === (string)$value) {
                        return true;
                    }
                }
                return false;
                break;
            case 'globalVar':
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    $point = strcspn($test, '!=<>');
                    $theVarName = substr($test, 0, $point);
                    $nv = $this->getVariable(trim($theVarName));
                    $testValue = substr($test, $point);
                    if ($this->compareNumber($testValue, $nv)) {
                        return true;
                    }
                }
                return false;
                break;
            case 'globalString':
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    $point = strcspn($test, '=');
                    $theVarName = substr($test, 0, $point);
                    $nv = (string)$this->getVariable(trim($theVarName));
                    $testValue = substr($test, $point + 1);
                    if ($this->searchStringWildcard($nv, trim($testValue))) {
                        return true;
                    }
                }
                return false;
                break;
            case 'userFunc':
                $values = preg_split('/\(|\)/', $value);
                $funcName = trim($values[0]);
                $funcValue = GeneralUtility::trimExplode(',', $values[1]);
                $funcNameParts = explode('->', $funcName);

                if (count($funcNameParts) < 2) {
                    $matches = array();
                    preg_match_all('/^\s*([^\(\s]+)\s*(?:\((.*)\))?\s*$/', $value, $matches);
                    $funcName = $matches[1][0];
                    $funcValues = $matches[2][0] ? $this->parseUserFuncArguments($matches[2][0]) : array();
                    if (is_callable($funcName) && call_user_func_array($funcName, $funcValues)) {
                        return true;
                    }
                } else if (
                    isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']) &&
                    is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']) &&
                    isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['includeLibs']) &&
                    is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['includeLibs'])
                ) {
                    $i = 2;
                    while($values[$i] != '') {
                        $funcValue[] = $values[$i];
                        $i++;
                    }

                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['includeLibs'] as $classRef) {
                        $classRefParts = explode(':&', $classRef);

                        if ($classRefParts[1] == $funcNameParts[0]) {
                            $hookObj= GeneralUtility::getUserObj($classRef);
                            if (method_exists($hookObj, 'init')) {
                                $hookObj->init($key, $value);
                            }
                            if (method_exists($hookObj, $funcNameParts[1])) {
                                $result = GeneralUtility::callUserFunction($funcName, $funcValue, $this, '');
                                return $result;
                            }
                        }
                    }
                }
                return false;
                break;
            case 'ext':
                $values = GeneralUtility::trimExplode(',', $value, true);
                $result = false;

                foreach($values as $curValue) {

                    if (strlen($curValue)) {
                        $curValue = trim($curValue);
                        $bExtIsLoaded = ExtensionManagementUtility::isLoaded($keyArray['1']);
                        if (!strlen($keyArray['2']) && $bExtIsLoaded || ($keyArray['2'] == 'isLoaded' && intval($bExtIsLoaded) == $curValue)) {
                            $result = true;
                            break;
                        } else if ($bExtIsLoaded) {
                            $extInfoArray = ExtensionManagementUtility::getExtensionInfo($keyArray['1']);
                            if (is_array($extInfoArray) && count($extInfoArray)) {
                                foreach ($extInfoArray as $k => $v) {
                                    if (strpos($keyArray['2'], $k) === 0) {
                                        $test = str_replace($k, $v, $keyArray['2']);
                                        switch ($k) {
                                            case 'version':
                                                if (preg_match('/^\s*([^\s]*)\s*(!?=+|<=?|>=?)\s*([^\s]*)\s*$/', $test, $matches)) {
                                                    $result &=
                                                        ExtensionManagementUtility::versionDifference(
                                                            $matches['1'],
                                                            $curValue,
                                                            $matches['2'],
                                                            1
                                                        );
                                                }
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                return $result;
            break;
        }
        return NULL;
    }

}
