<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "rest_falprocessing"
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title'            => 'FAL Image Processing',
    'description'      => 'Test',
    'category'         => 'plugin',
    'author'           => 'Jens Jacobsen',
    'author_email'     => 'typo3@jens-jacobsen.de',
    'state'            => 'stable',
    'internal'         => '',
    'uploadfolder'     => '0',
    'createDirs'       => '',
    'clearCacheOnLoad' => 1,
    'version'          => '7.6.0.0',
    'constraints'      => array(
        'depends'   => array(
            'typo3' => '7.6.0-',
            'rest'  => '2.0.0-',
        ),
        'conflicts' => array(),
        'suggests'  => array(),
    ),
    'autoload'         => array(
        'classmap' => array('Classes')
    ),
);
