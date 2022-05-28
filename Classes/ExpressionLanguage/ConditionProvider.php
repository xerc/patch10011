<?php

declare(strict_types=1);

namespace JambageCom\Patch10011\ExpressionLanguage;

use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;

class ConditionProvider extends AbstractProvider
{
    public function __construct()
    {
        $this->expressionLanguageProviders = [
            UtilitiesConditionFunctionsProvider::class,
        ];
    }
}
