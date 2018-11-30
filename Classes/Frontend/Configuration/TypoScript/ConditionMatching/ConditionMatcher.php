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

                        if (
                            $classRefParts[0] == $funcNameParts[0] || // namespace variant
                            $classRefParts[1] == $funcNameParts[0]
                        ) {
                            $hookObj= GeneralUtility::makeInstance($classRef);
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

            default:
                return parent::evaluateConditionCommon($key, $value);
            break;
        }
        return null;
    }
}

