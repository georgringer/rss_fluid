<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'RSS with fluid',
    'description' => 'Render RSS feed with fluid',
    'category' => 'fe',
    'author' => 'Georg Ringer',
    'author_email' => 'mail@ringer.it',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.13-11.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
