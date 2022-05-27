<?php

namespace JambageCom\Patch10011\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


class UtilitiesConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    protected $existingVariables; // registered variables. see TYPO3 documentation "Symfony expression language"

    public function setExistingVariables ($existingVariables)
    {
        $this->existingVariables = $existingVariables;
    }

    public function getExistingVariables ()
    {
        return $this->existingVariables;
    }

    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        return [
            $this->getUserFunction(),
            $this->getExtensionVersionFunction(),
        ];
    }

    /**
     * @return ExpressionFunction
     */
    protected function getUserFunction(): ExpressionFunction
    {
        return new ExpressionFunction('userFunc', function () {
            // Not implemented, we only use the evaluator
        }, function (...$arguments) {
            $funcName = '';
            $result = false;
            if (
                is_array($arguments) &&
                isset($arguments['1'])
            ) {
                $funcName = $arguments['1'];
            }

            if (
                isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']) &&
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']) &&
                isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['userFunc']) &&
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['userFunc']) &&
                isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['userFunc'][$funcName])
            ) {
                $className = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['userFunc'][$funcName];
                $hookObj = new $className;

                if (method_exists($hookObj, 'init')) {
                    $hookObj->init($arguments['0'], $value);
                }

                $funcValue = [];
                $i = 2;
                while($arguments[$i] != '') {
                    $funcValue[] = $arguments[$i];
                    $i++;
                }

                if (method_exists($hookObj, $funcName)) {
                    $this->setExistingVariables($arguments['0']);
                    $result = 
                        call_user_func_array([&$hookObj, $funcName], [&$funcValue, &$this]);
                }
            }
            
            return $result;
        });
    }
    

    /**
     * @return ExpressionFunction
     */
    protected function getExtensionVersionFunction(): ExpressionFunction
    {
        return new ExpressionFunction('ext', function () {
            // Not implemented, we only use the evaluator
        }, function (...$arguments) {
            $extensionKey = $arguments[1];
            $type = isset($arguments[2]) ? $arguments[2] : null;

            $isLoaded = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey);
            if ($isLoaded) {
                if(null === $type) {
                    return true;
                }
                $extInfoArray = \JambageCom\Patch10011\Utility\ExtensionManagementUtility::getExtensionInfo($extensionKey);
                if ($type == 'version' || $type == 'title') {
                    return \JambageCom\Patch10011\Utility\ExtensionManagementUtility::makeVersion($extInfoArray[$type], '');
                }
            }
            return false;
        });
    }
}
