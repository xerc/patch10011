<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TypoScript Condition userFunc enhancements',
    'description' => 'TypoScript condition which will only be executed if a named extension has been installed in a given version number. Add parameters and a return value comparison to userFunc. TYPO3 core patch #10011.',
    'category' => 'misc',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'state' => 'stable',
    'version' => '0.4.1',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-11.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'clearCacheOnLoad' => 0
];
