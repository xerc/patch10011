<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2012 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Contains class for Matching TypoScript conditions
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Franz Holzinger <franz@ttproducts.de>
 */


require_once (t3lib_extMgm::extPath('patch10011') . 'class.patch10011_extmgm.php');


/**
 * Child class for the Web > Page module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage patch1822
 */
class ux_t3lib_matchCondition_frontend extends t3lib_matchCondition_frontend {


	/**
	 * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
	 *
	 * @param	string		$string: The condition to match against its criterias.
	 * @return	boolean		Whether the condition matched
	 * @see t3lib_tsparser::parse()
	 */
	protected function evaluateCondition($string) {
		list($key, $value) = t3lib_div::trimExplode('=', $string, FALSE, 2);

		$result = self::evaluateConditionCommon($key, $value);

		if (is_bool($result)) {
			return $result;
		} else {
			switch ($key) {
				case 'usergroup':
					$groupList = $this->getGroupList();
					if ($groupList != '0,-1') { // '0,-1' is the default usergroups when not logged in!
						$values = t3lib_div::trimExplode(',', $value, TRUE);
						foreach ($values as $test) {
							if ($test == '*' || t3lib_div::inList($groupList, $test)) {
								return TRUE;
							}
						}
					}
				break;
				case 'treeLevel':
					$values = t3lib_div::trimExplode(',', $value, TRUE);
					$treeLevel = count($this->rootline) - 1;
					foreach ($values as $test) {
						if ($test == $treeLevel) {
							return TRUE;
						}
					}
				break;
				case 'PIDupinRootline':
				case 'PIDinRootline':
					$values = t3lib_div::trimExplode(',', $value, TRUE);
					if (($key == 'PIDinRootline') || (!in_array($this->pageId, $values))) {
						foreach ($values as $test) {
							foreach ($this->rootline as $rl_dat) {
								if ($rl_dat['uid'] == $test) {
									return TRUE;
								}
							}
						}
					}
				break;
			}
		}

		return FALSE;
	}

	/**
	 * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
	 *
	 * @param	string		The condition to match against its criterias.
	 * @return	mixed		Returns true or false based on the evaluation
	 */
	protected function evaluateConditionCommon($key, $value) {

		if (t3lib_div::inList('browser,version,system,useragent', strtolower($key))) {
			$browserInfo = $this->getBrowserInfo(t3lib_div::getIndpEnv('HTTP_USER_AGENT'));
		}
		$keyParts = t3lib_div::trimExplode('|', $key);

		switch ($keyParts[0]) {
			case 'browser':
				$values = t3lib_div::trimExplode(',', $value, TRUE);
					// take all identified browsers into account, eg chrome deliver
					// webkit=>532.5, chrome=>4.1, safari=>532.5
					// so comparing string will be
					// "webkit532.5 chrome4.1 safari532.5"
				$all = '';
				foreach ($browserInfo['all'] as $key => $value) {
					$all .= $key . $value . ' ';
				}
				foreach ($values as $test) {
					if (stripos($all, $test) !== FALSE) {
						return TRUE;
					}
				}
			break;
			case 'version':
				$values = t3lib_div::trimExplode(',', $value, TRUE);
				foreach ($values as $test) {
					if (strcspn($test, '=<>') == 0) {
						switch (substr($test, 0, 1)) {
							case '=':
								if (doubleval(substr($test, 1)) == $browserInfo['version']) {
									return TRUE;
								}
							break;
							case '<':
								if (doubleval(substr($test, 1)) > $browserInfo['version']) {
									return TRUE;
								}
							break;
							case '>':
								if (doubleval(substr($test, 1)) < $browserInfo['version']) {
									return TRUE;
								}
							break;
						}
					} elseif (strpos(' ' . $browserInfo['version'], $test) == 1) {
						return TRUE;
					}
				}
			break;
			case 'system':
				$values = t3lib_div::trimExplode(',', $value, TRUE);
					// Take all identified systems into account, e.g. mac for iOS, Linux
					// for android and Windows NT for Windows XP
				$allSystems .= ' ' . implode(' ', $browserInfo['all_systems']);
				foreach ($values as $test) {
					if (stripos($allSystems, $test) !== FALSE) {
						return TRUE;
					}
				}
			break;
			case 'device':
				if (!isset($this->deviceInfo)) {
					$this->deviceInfo = $this->getDeviceType(t3lib_div::getIndpEnv('HTTP_USER_AGENT'));
				}
				$values = t3lib_div::trimExplode(',', $value, TRUE);
				foreach ($values as $test) {
					if ($this->deviceInfo == $test) {
						return TRUE;
					}
				}
			break;
			case 'useragent':
				$test = trim($value);
				if (strlen($test)) {
					return $this->searchStringWildcard($browserInfo['useragent'], $test);
				}
			break;
			case 'language':
				$values = t3lib_div::trimExplode(',', $value, TRUE);
				foreach ($values as $test) {
					if (preg_match('/^\*.+\*$/', $test)) {
						$allLanguages = preg_split('/[,;]/', t3lib_div::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
						if (in_array(substr($test, 1, -1), $allLanguages)) {
							return TRUE;
						}
					} elseif (t3lib_div::getIndpEnv('HTTP_ACCEPT_LANGUAGE') == $test) {
						return TRUE;
					}
				}
			break;
			case 'IP':
				if (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $value)) {
					return TRUE;
				}
			break;
			case 'hostname':
				if (t3lib_div::cmpFQDN(t3lib_div::getIndpEnv('REMOTE_ADDR'), $value)) {
					return TRUE;
				}
			break;
				// hour, minute, dayofweek, dayofmonth, month, year, julianday
			case 'hour':
			case 'minute':
			case 'month':
			case 'year':
			case 'dayofweek':
			case 'dayofmonth':
			case 'dayofyear':
				$theEvalTime = $GLOBALS['SIM_EXEC_TIME']; // In order to simulate time properly in templates.
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
				$theTestValue = intval($theTestValue);
					// comp
				$values = t3lib_div::trimExplode(',', $value, TRUE);
				foreach ($values as $test) {
					if (t3lib_div::testInt($test)) {
						$test = '=' . $test;
					}
					if ($this->compareNumber($test, $theTestValue)) {
						return TRUE;
					}
				}
			break;
			case 'compatVersion':
				return t3lib_div::compat_version($value);
			break;
			case 'loginUser':
				if ($this->isUserLoggedIn()) {
					$values = t3lib_div::trimExplode(',', $value, TRUE);
					foreach ($values as $test) {
						if ($test == '*' || !strcmp($this->getUserId(), $test)) {
							return TRUE;
						}
					}
				} elseif ($value === '') {
					return TRUE;
				}
			break;
			case 'page':
				if ($keyParts[1]) {
					$page = $this->getPage();
					$property = $keyParts[1];
					if (!empty($page) && isset($page[$property])) {
						if (strcmp($page[$property], $value) === 0) {
							return TRUE;
						}
					}
				}
			break;
			case 'globalVar':
				$values = t3lib_div::trimExplode(',', $value, TRUE);
				foreach ($values as $test) {
					$point = strcspn($test, '!=<>');
					$theVarName = substr($test, 0, $point);
					$nv = $this->getVariable(trim($theVarName));
					$testValue = substr($test, $point);

					if ($this->compareNumber($testValue, $nv)) {
						return TRUE;
					}
				}
			break;
			case 'globalString':
				$values = t3lib_div::trimExplode(',', $value, TRUE);
				foreach ($values as $test) {
					$point = strcspn($test, '=');
					$theVarName = substr($test, 0, $point);
					$nv = $this->getVariable(trim($theVarName));
					$testValue = substr($test, $point + 1);

					if ($this->searchStringWildcard($nv, trim($testValue))) {
						return TRUE;
					}
				}
			break;
			case 'userFunc':
				$values = preg_split('/\(|\)/', $value);
// debug ($values, 'evaluateConditionCommon $values +++');
				$funcName = trim($values[0]);
// debug ($funcName, '$funcName');
				$funcValue = t3lib_div::trimExplode(',', $values[1]);
				$funcNameParts = explode('->', $funcName);
// debug ($funcNameParts, '$funcNameParts');

				if (count($funcNameParts) < 2) {
					$prefix = $this->getUserFuncClassPrefix();
					if ($prefix &&
						!t3lib_div::hasValidClassPrefix($funcName, array($prefix))
					) {
						$this->log('Match condition: Function "' . $funcName . '" was not prepended with "' . $prefix . '"');
						return FALSE;
					}
					if (function_exists($funcName) && call_user_func($funcName, $funcValue[0])) {
						return TRUE;
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
							$hookObj= t3lib_div::getUserObj($classRef);
							if (method_exists($hookObj, 'init')) {
								$hookObj->init($key, $value);
							}
							if (method_exists($hookObj, $funcNameParts[1])) {

// debug ($funcName, '$funcName');
// debug ($funcValue, '$funcValue');
								$result = t3lib_div::callUserFunction($funcName, $funcValue, $this, '');
								return $result;
							}
						}
					}
				}

			break;
			case 'ext':
				$values = t3lib_div::trimExplode(',', $value, TRUE);
				$result = FALSE;

				foreach($values as $curValue) {

					if (strlen($curValue)) {
						$curValue = trim($curValue);
						$bExtIsLoaded = patch10011_extMgm::isLoaded($keyArray['1']);
						if (!strlen($keyArray['2']) && $bExtIsLoaded || ($keyArray['2'] == 'isLoaded' && intval($bExtIsLoaded) == $curValue)) {
							$result = TRUE;
							break;
						} else if ($bExtIsLoaded) {
							$extInfoArray = patch10011_extMgm::getExtensionInfo($keyArray['1']);
							if (is_array($extInfoArray) && count($extInfoArray)) {
								foreach ($extInfoArray as $k => $v) {
									if (strpos($keyArray['2'], $k) === 0) {
										$test = str_replace($k, $v, $keyArray['2']);
										switch ($k) {
											case 'version':
												if (preg_match('/^\s*([^\s]*)\s*(!?=+|<=?|>=?)\s*([^\s]*)\s*$/', $test, $matches)) {
													$result &=
														patch10011_extMgm::versionDifference(
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


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/patch10011/xclass/class.user_t3lib_matchcondition_frontend.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/patch10011/xclass/class.user_t3lib_matchcondition_frontend.php']);
}
?>