<?php

// THX @ https://github.com/lochmueller

declare(strict_types=1);

$baseDir = dirname(__DIR__, 3);

require $baseDir.'/vendor/autoload.php';

$finder = PhpCsFixer\Finder::create()
    ->in($baseDir.'/Classes')
    ->in($baseDir.'/Resources/Private/Build')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP74Migration' => true,
        '@PHP74Migration:risky' => true,
        'no_superfluous_phpdoc_tags' => true,
    ])
    ->setFinder($finder)
;
