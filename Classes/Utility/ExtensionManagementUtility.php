<?php

declare(strict_types=1);

namespace JambageCom\Patch10011\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Extension Management functions.
 *
 * This class is never instantiated, rather the methods inside is called as functions like
 * JambageCom\Patch10011\Utility\ExtensionManagementUtility::isLoaded('my_extension');
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ExtensionManagementUtility
{
    /**
     * Parses the version number x.x.x and returns an array with the various parts.
     *
     * @param	string		Version code, x.x.x
     * @param	string		Increase version part: "main", "sub", "dev"
     */
    public static function renderVersion($v, $raise = ''): string
    {
        $parts = GeneralUtility::intExplode('.', $v.'..');
        $parts[0] = MathUtility::forceIntegerInRange($parts[0], 0, 999);
        $parts[1] = MathUtility::forceIntegerInRange($parts[1], 0, 999);
        $parts[2] = MathUtility::forceIntegerInRange($parts[2], 0, 999);

        switch ((string) $raise) {
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

        $result = [];
        $result['version'] = $parts[0].'.'.$parts[1].'.'.$parts[2];
        $result['version_int'] = (int) ($parts[0] * 1000000 + $parts[1] * 1000 + $parts[2]);
        $result['version_main'] = $parts[0];
        $result['version_sub'] = $parts[1];
        $result['version_dev'] = $parts[2];

        return $result;
    }

    /**
     * Returns version information.
     *
     * @param	string		Version code, x.x.x
     * @param	string		part: "", "int", "main", "sub", "dev"
     *
     * @see renderVersion()
     */
    public static function makeVersion($v, $mode): string
    {
        $result = '';
        $vDat = self::renderVersion($v);
        if ('' === $mode) {
            $result = sprintf('%02s.%02s.%02s', $vDat['version_main'], $vDat['version_sub'], $vDat['version_dev']);
        } else {
            $result = $vDat['version_'.$mode];
        }

        return $result;
    }

    /**
     * Gets information for an extension, eg. version and most-recently-edited-script.
     *
     * @param	string		Extension key
     * @param	string		Predefined path ; needed if you have the extension in another place
     *
     * @return array of EXT infos | @return string message on error
     */
    public static function getExtensionInfo($extKey, $path = '')
    {
        $result = '';

        if (!$path) {
            $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extKey);
        }

        if (is_dir($path)) {
            $file = $path.'ext_emconf.php';

            if (@is_file($file)) {
                $_EXTKEY = $extKey;
                $EM_CONF = [];

                include $file;

                $eInfo = [];
                $fieldArray = [
                    'author',
                    'author_company',
                    'author_email',
                    'category',
                    'constraints',
                    'description',
                    'lastuploaddate',
                    'state',
                    'title',
                    'version',
                ];
                $extConf = $EM_CONF[$extKey];

                if (isset($extConf) && \is_array($extConf)) {
                    foreach ($extConf as $field => $value) {
                        if (\in_array($field, $fieldArray, true)) {
                            $eInfo[$field] = $value;
                        }
                    }

                    foreach ($fieldArray as $field) {
                        // Info from emconf:
                        $eInfo[$field] = $extConf[$field];
                    }

                    if (\is_array($extConf['constraints']) && \is_array($EM_CONF[$extKey]['constraints']['depends'])) {
                        $eInfo['TYPO3_version'] = $extConf['constraints']['depends']['typo3'];
                    } else {
                        $eInfo['TYPO3_version'] = $extConf['TYPO3_version'];
                    }
                    $filesHash = unserialize($extConf['_md5_values_when_last_written']);
                    $eInfo['manual'] = @is_file($path.'/Documentation/Index.rst') || @is_file($path.'/doc/manual.odt');
                    $result = $eInfo;
                } else {
                    $result = 'ERROR: The array $EM_CONF is wrong in file: '.$file;
                }
            } else {
                $result = 'ERROR: No emconf.php file: '.$file;
            }
        } else {
            $result = 'ERROR: Path not found: '.$path;
        }

        return $result;
    }
}
