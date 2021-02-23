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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;
use WIND\Randomdata\Exception\ConfigurationFileNotFoundException;
use WIND\Randomdata\Exception\FieldsNotFoundForItemException;
use WIND\Randomdata\Exception\PidNotFoundForItemException;
use WIND\Randomdata\Exception\TableNotFoundInTcaException;
use WIND\Randomdata\Exception\UnknownActionException;
use WIND\Randomdata\Exception\CountNotFoundForItemException;
use WIND\Randomdata\Exception\DataHandlerException;
use WIND\Randomdata\Exception\ProviderException;
use WIND\Randomdata\Service\RandomdataService;

/**
 * Randomdata Command Controller
 */
class RandomdataCommandController extends Command
{
    /**
     * Configure
     *
     * @return void
     */
    public function configure()
    {
        $this->setDescription('Generate random data');
        $this->setHelp('This command allows your to create random data or replace existing data with random data');
        $this->addArgument('file', InputArgument::REQUIRED, 'YAML configuration file');
        $this->addArgument('locale', InputArgument::OPTIONAL, 'Locale used to generate data', Factory::DEFAULT_LOCALE);
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \RuntimeException
     * @throws ConfigurationFileNotFoundException
     * @throws FieldsNotFoundForItemException
     * @throws PidNotFoundForItemException
     * @throws TableNotFoundInTcaException
     * @throws UnknownActionException
     * @throws CountNotFoundForItemException
     * @throws DataHandlerException
     * @throws ProviderException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getNumericTypo3Version()) >= 9002000) {
            Bootstrap::initializeBackendAuthentication();
        } else {
            Bootstrap::getInstance()->initializeBackendAuthentication();
        }
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var RandomdataService $randomdataService */
        $randomdataService = $objectManager->get(RandomdataService::class);
        $randomdataService->generate($input->getArgument('file'), $input->getArgument('locale'), $output);

        return 0;
    }
}