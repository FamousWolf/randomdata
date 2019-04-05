<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['Randomdata-Generate'] = \WIND\Randomdata\Controller\RandomdataCommandController::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['randomdata']['allowedActions'] = ['insert', 'replace'];
}