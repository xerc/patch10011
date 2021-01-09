<?php
namespace JambageCom\Patch10011\Utility;

/*
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
* Extension Management functions
*
* This class is never instantiated, rather the methods inside is called as functions like
* JambageCom\Patch10011\Utility\ExtensionManagementUtility::isLoaded('my_extension');
*
* @author	Kasper Skårhøj <kasperYYYY@typo3.com>
* @package TYPO3
* @subpackage patch10011
*/
class ExtensionManagementUtility {
    /**
    * Evaluates a $leftValue based on an operator: "<", ">", "<=", ">=", "!=" or "="
    *
    * @param	string		$test: The value to compare with on the form [operator][number]. Eg. "< 123"
    * @param	integer		$leftValue: The value on the left side
    * @return	boolean		If $value is "50" and $test is "< 123" then it will return true.
    */
    public function testNumber ($test, $leftValue) {
        $test = trim($test);

        if (preg_match('/^(!?=+|<=?|>=?)\s*([^\s]*)\s*$/', $test, $matches)) {
            $operator = $matches[1];
            $rightValue = $matches[2];

            switch ($operator) {
                case '>=':
                    return ($leftValue >= doubleval($rightValue));
                    break;
                case '<=':
                    return ($leftValue <= doubleval($rightValue));
                    break;
                case '!=':
                    return ($leftValue != doubleval($rightValue));
                    break;
                case '<':
                    return ($leftValue < doubleval($rightValue));
                    break;
                case '>':
                    return ($leftValue > doubleval($rightValue));
                    break;
                default:
                    // nothing valid found except '=', use '='
                    return ($leftValue == trim($rightValue));
                    break;
            }
        }

        return false;
    }

    /**
    * Parses the version number x.x.x and returns an array with the various parts.
    *
    * @param	string		Version code, x.x.x
    * @param	string		Increase version part: "main", "sub", "dev"
    * @return	string
    */
    public function renderVersion ($v, $raise = '') {
        $parts = GeneralUtility::intExplode('.', $v . '..');
        $parts[0] = GeneralUtility::intInRange($parts[0], 0, 999);
        $parts[1] = GeneralUtility::intInRange($parts[1], 0, 999);
        $parts[2] = GeneralUtility::intInRange($parts[2], 0, 999);

        switch((string)$raise) {
            case 'main':
                $parts[0]++;
                $parts[1] = 0;
                $parts[2] = 0;
                break;
            case 'sub':
                $parts[1]++;
                $parts[2] = 0;
                break;
            case 'dev':
                $parts[2]++;
                break;
        }

        $res = array();
        $res['version'] = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
        $res['version_int'] = intval($parts[0] * 1000000 + $parts[1] * 1000 + $parts[2]);
        $res['version_main'] = $parts[0];
        $res['version_sub'] = $parts[1];
        $res['version_dev'] = $parts[2];

        return $res;
    }




    /**
    * Returns version information
    *
    * @param	string		Version code, x.x.x
    * @param	string		part: "", "int", "main", "sub", "dev"
    * @return	string
    * @see renderVersion()
    */
    public function makeVersion ($v, $mode)	{
        $vDat = self::renderVersion($v);
        return $vDat['version_' . $mode];
    }

    /**
    * Evaluates differences in version numbers with three parts, x.x.x. Returns true if $v1 is greater than $v2
    *
    * @param	string		Version number 1
    * @param	string		Version number 2
    * @param	string		comparator string for the version compare
    * @param	integer		Tolerance factor. For instance, set to 1000 to ignore difference in dev-version (third part)
    * @return	boolean		True if version 1 is greater than version 2
    */
    public function versionDifference ($v1, $v2, $comp = '', $div = 1) {
        $result = FALSE;
        $leftValue = floor(self::makeVersion($v1, 'int') / $div);
        $rightValue = floor(self::makeVersion($v2, 'int') / $div);
        if (!$comp) {
            $comp = '>';
        }
        $result = self::testNumber($comp . $rightValue, $leftValue);
        return $result;
    }

    /**
    * Gets information for an extension, eg. version and most-recently-edited-script
    *
    * @param	string		Extension key
    * @param	string		predefined path ... needed if you have the extension in another place
    * @return	array		Information array (unless an error occured)
    */
    public function getExtensionInfo ($extKey, $path = '') {
        $result = '';

        if (!$path) {
            $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extKey);
        }

        if (is_dir($path)) {
            $file = $path . 'ext_emconf.php';

            if (@is_file($file)) {
                $_EXTKEY = $extKey;
                $EM_CONF = array();
                include($file);

                $eInfo = array();
                $fieldArray = array(
                    'author',
                    'author_company',
                    'author_email',
                    'category',
                    'constraints',
                    'description',
                    'lastuploaddate',
                    'state',
                    'title',
                    'version'
                );
                $extConf = $EM_CONF[$extKey];

                if (isset($extConf) && is_array($extConf)) {
                    foreach ($extConf as $field => $value) {
                        if (in_array($field, $fieldArray)) {
                            $eInfo[$field] = $value;
                        }
                    }

                    foreach ($fieldArray as $field) {
                        // Info from emconf:
                        $eInfo[$field] = $extConf[$field];
                    }

                    if (is_array($extConf['constraints']) && is_array($EM_CONF[$extKey]['constraints']['depends'])) {
                        $eInfo['TYPO3_version'] = $extConf['constraints']['depends']['typo3'];
                    } else {
                        $eInfo['TYPO3_version'] = $extConf['TYPO3_version'];
                    }
                    $filesHash = unserialize($extConf['_md5_values_when_last_written']);
                    $eInfo['manual'] = @is_file($path . '/doc/manual.sxw');
                    $result = $eInfo;
                } else {
                    $result = 'ERROR: The array $EM_CONF is wrong in file: ' . $file;
                }
            } else {
                $result = 'ERROR: No emconf.php file: ' . $file;
            }
        } else {
            $result = 'ERROR: Path not found: ' . $path;
        }

        return $result;
    }
}

