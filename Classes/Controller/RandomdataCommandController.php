<?php
namespace WIND\Randomdata\Controller;

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

use Faker\Factory;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use WIND\Randomdata\Service\RandomdataService;

/**
 * Randomdata Command Controller
 */
class RandomdataCommandController extends CommandController
{
    /**
     * Generate random data
     *
     * @param string $file YAML configuration file
     * @param string $locale Locale used to generate data
     * @param bool $quiet Only output errors if true
     * @return void
     * @throws \RuntimeException
     * @throws \WIND\Randomdata\Exception\ConfigurationFileNotFoundException
     * @throws \WIND\Randomdata\Exception\FieldsNotFoundForItemException
     * @throws \WIND\Randomdata\Exception\PidNotFoundForItemException
     * @throws \WIND\Randomdata\Exception\TableNotFoundInTcaException
     * @throws \WIND\Randomdata\Exception\UnknownActionException
     * @throws \WIND\Randomdata\Exception\CountNotFoundForItemException
     * @throws \WIND\Randomdata\Exception\DataHandlerException
     * @throws \WIND\Randomdata\Exception\ProviderException
     */
    public function generateCommand($file, $locale = Factory::DEFAULT_LOCALE, $quiet = false)
    {
        /** @var \WIND\Randomdata\Service\RandomdataService $randomdataService */
        $randomdataService = $this->objectManager->get(RandomdataService::class);
        $randomdataService->generate($file, $locale, $quiet);
    }
}