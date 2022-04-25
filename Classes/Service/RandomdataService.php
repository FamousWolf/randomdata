<?php
namespace WIND\Randomdata\Service;

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
use Faker\Generator;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;
use WIND\Randomdata\Exception\ConfigurationFileNotFoundException;
use WIND\Randomdata\Exception\CountNotFoundForItemException;
use WIND\Randomdata\Exception\DataHandlerException;
use WIND\Randomdata\Exception\FieldsNotFoundForItemException;
use WIND\Randomdata\Exception\PidNotFoundForItemException;
use WIND\Randomdata\Exception\ProviderException;
use WIND\Randomdata\Exception\TableNotFoundInTcaException;
use WIND\Randomdata\Exception\UnknownActionException;
use WIND\Randomdata\Provider\ProviderInterface;

/**
 * Randomdata Service
 */
class RandomdataService
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $addToDataMap = [];

    /**
     * @var array
     */
    protected $addToCmdMap = [];

    /**
     * @var int
     */
    protected $newUid = 0;

    /**
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Generate random data
     *
     * @param string $configurationFile
     * @param string $locale
     * @param OutputInterface $output
     * @return void
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
     */
    public function generate($configurationFile, $locale, $output = null)
    {
        $this->output = $output;

        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        $this->faker = Factory::create($locale);

        $this->outputWithoutNewLine('Loading configuration file "' . $configurationFile . '" ...');
        try {
            $this->loadConfigurationFile($configurationFile);
        } catch(\Throwable $e) {
            $this->output(' <fg=red>FAIL</>');
            throw $e;
        }
        $this->output(' <fg=green>OK</>');

        foreach ($this->configuration as $configurationKey => $itemConfiguration) {
            $this->generateItem($configurationKey, $itemConfiguration);
        }
    }

    /**
     * Add to data map
     *
     * @param array $dataMap
     * @return void
     */
    public function addToDataMap(array $dataMap)
    {
        $this->addToDataMap = array_merge_recursive($this->addToDataMap, $dataMap);
    }

    /**
     * Add to cmd map
     *
     * @param array $cmdMap
     * @return void
     */
    public function addToCmdMap(array $cmdMap)
    {
        $this->addToCmdMap = array_merge_recursive($this->addToCmdMap, $cmdMap);
    }

    /**
     * Get new uid
     *
     * @return string
     */
    public function getNewUid()
    {
        $this->newUid++;
        return 'NEW' . $this->newUid;
    }

    /**
     * Load configuration file
     *
     * @param string $configurationFile
     * @return void
     * @throws ConfigurationFileNotFoundException
     * @throws \RuntimeException
     */
    protected function loadConfigurationFile($configurationFile)
    {
        $configurationFile = GeneralUtility::getFileAbsFileName(realpath($configurationFile));
        if (!is_file($configurationFile)) {
            throw new ConfigurationFileNotFoundException('Configuration file "' . $configurationFile . '" not found', 1554378907);
        }

        /** @var YamlFileLoader $yamlLoader */
        $yamlLoader = $this->objectManager->get(YamlFileLoader::class);
        $this->configuration = $yamlLoader->load($configurationFile);
    }

    /**
     * Generate random data for item
     *
     * @param string $configurationKey
     * @param array $itemConfiguration
     * @throws FieldsNotFoundForItemException
     * @throws PidNotFoundForItemException
     * @throws TableNotFoundInTcaException
     * @throws UnknownActionException
     * @throws CountNotFoundForItemException
     * @throws DataHandlerException
     * @throws ProviderException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     */
    protected function generateItem($configurationKey, array $itemConfiguration)
    {
        $this->outputWithoutNewLine('Generating data for item "' . $configurationKey . '" ...');
        try {
            $table = $this->getItemTable($configurationKey, $itemConfiguration);
            $pid = $this->getItemPid($configurationKey, $itemConfiguration);
            $action = $this->getItemAction($configurationKey, $itemConfiguration);
            $fields = $this->getItemFields($configurationKey, $itemConfiguration);

            switch ($action) {
                case 'insert':
                    $this->generateAndInsertRecords($configurationKey, $table, $pid, $fields, $itemConfiguration);
                    break;
                case 'replace':
                    $this->generateAndReplaceRecords($configurationKey, $table, $pid, $fields, $itemConfiguration);
                    break;
                default:
                    $this->dispatchSignalSlot('generateItemCustomAction', [$configurationKey, $table, $pid, $action, $fields, $itemConfiguration, $this]);
                    break;
            }
        } catch(\Throwable $e) {
            $this->output(' <fg=red>FAIL</>');
            throw $e;
        }
        $this->output(' <fg=green>OK</>');
    }

    /**
     * Get item table
     *
     * @param string $configurationKey
     * @param array $itemConfiguration
     * @return string
     * @throws TableNotFoundInTcaException
     */
    protected function getItemTable($configurationKey, array $itemConfiguration)
    {
        $table = $this->getItemConfigurationValue($itemConfiguration, 'table');
        if (empty($table)) {
            $table = $configurationKey;
        }

        if (!isset($GLOBALS['TCA'][$table])) {
            throw new TableNotFoundInTcaException('Table "' . $table . '" not found in TCA for item "' . $configurationKey . '"', 1554379741);
        }
        return $table;
    }

    /**
     * Get item PID
     *
     * @param string $configurationKey
     * @param array $itemConfiguration
     * @return int
     * @throws PidNotFoundForItemException
     */
    protected function getItemPid($configurationKey, array $itemConfiguration)
    {
        $pid = $this->getItemConfigurationValue($itemConfiguration, 'pid');

        if ($pid === null) {
            throw new PidNotFoundForItemException('PID not set in configuration for item "' . $configurationKey . '"', 1554380141);
        }

        $pid = (int)$pid;

        if ($pid > 0) {
            /** @var QueryBuilder $pageQueryBuilder */
            $pageQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $page = $pageQueryBuilder->count('*')->from('pages')->where(
                $pageQueryBuilder->expr()->eq('uid', $pid)
            )->execute()->fetchColumn(0);

            if ($page !== 1) {
                throw new PidNotFoundForItemException('Page with uid "' . $pid . '" not found in database for item "' . $configurationKey . '"', 1554380475);
            }
        }

        return $pid;
    }

    /**
     * Get item action
     *
     * @param string $configurationKey
     * @param array $itemConfiguration
     * @return string
     * @throws UnknownActionException
     */
    protected function getItemAction($configurationKey, array $itemConfiguration)
    {
        $action = $this->getItemConfigurationValue($itemConfiguration, 'action');
        if (empty($action)) {
            $action = 'insert';
        }

        if (!in_array($action, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['randomdata']['allowedActions'])) {
            throw new UnknownActionException('Unknown action "' . $action . '" for item "' . $configurationKey . '"', 1554381398);
        }

        return $action;
    }

    /**
     * Get item fields
     *
     * @param string $configurationKey
     * @param array $itemConfiguration
     * @return array
     * @throws FieldsNotFoundForItemException
     */
    protected function getItemFields($configurationKey, array $itemConfiguration)
    {
        $fields = $this->getItemConfigurationValue($itemConfiguration, 'fields');

        if (empty($fields) || !is_array($fields)) {
            throw new FieldsNotFoundForItemException('Fields not set in configuration for item "' . $configurationKey . '"', 1554381915);
        }

        return $fields;
    }

    /**
     * Get item configuration
     *
     * @param array $itemConfiguration
     * @param string $key
     * @return mixed
     */
    protected function getItemConfigurationValue(array $itemConfiguration, $key)
    {
        return isset($itemConfiguration[$key]) ? $itemConfiguration[$key] : null;
    }

    /**
     * Insert new records with random data for item
     *
     * @param string $configurationKey
     * @param string $table
     * @param int $pid
     * @param array $fields
     * @param array $itemConfiguration
     * @return void
     * @throws CountNotFoundForItemException
     * @throws DataHandlerException
     * @throws ProviderException
     */
    protected function generateAndInsertRecords($configurationKey, $table, $pid, array $fields, array $itemConfiguration)
    {
        $count = (int)$this->getItemConfigurationValue($itemConfiguration, 'count');
        
        if (empty($count)) {
            throw new CountNotFoundForItemException('Count not set in configuration for item "' . $configurationKey . '"', 1554383433);
        }

        $dataMap = [$table => []];

        $this->addToDataMap = [];
        $this->addToCmdMap = [];

        for ($i = 1; $i <= $count; $i++) {
            $recordUid = $this->getNewUid();
            $data = [
                'pid' => $pid,
            ];
            foreach ($fields as $field => $fieldConfiguration) {
                $fieldConfiguration['__table'] = $table;
                $fieldConfiguration['__pid'] = $pid;
                $fieldConfiguration['__field'] = $field;
                $fieldConfiguration['__recordUid'] = $recordUid;

                $data[$field] = $this->generateData($configurationKey, $field, $fieldConfiguration, $pid, $data);
            }

            $dataMap[$table][$recordUid] = $data;
        }

        if (!empty($this->addToDataMap)) {
            $dataMap = array_merge_recursive($dataMap, $this->addToDataMap);
        }

        $this->dataHandler->start($dataMap, $this->addToCmdMap);
        $this->dataHandler->process_datamap();

        if (!empty($this->dataHandler->errorLog)) {
            throw new DataHandlerException(count($this->dataHandler->errorLog) . ' errors handling data for item "' . $configurationKey . '". First error: ' . $this->dataHandler->errorLog[0], 1540909750);
        }
    }

    /**
     * Replace records with random data for item
     *
     * @param string $configurationKey
     * @param string $table
     * @param int $pid
     * @param array $fields
     * @param array $itemConfiguration
     * @return void
     * @throws DataHandlerException
     * @throws ProviderException
     */
    protected function generateAndReplaceRecords($configurationKey, $table, $pid, array $fields, array $itemConfiguration)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $records = $queryBuilder->select('uid')->from($table)->where($queryBuilder->expr()->eq('pid', $pid))->execute();

        if (!empty($records)) {
            $dataMap = [$table => []];

            $this->addToDataMap = [];
            $this->addToCmdMap = [];

            foreach ($records as $record) {
                $data = [];
                foreach ($fields as $field => $fieldConfiguration) {
                    $fieldConfiguration['__table'] = $table;
                    $fieldConfiguration['__pid'] = $pid;
                    $fieldConfiguration['__field'] = $field;
                    $fieldConfiguration['__recordUid'] = $record['uid'];

                    $data[$field] = $this->generateData($configurationKey, $field, $fieldConfiguration, $pid);
                }

                $dataMap[$table][$record['uid']] = $data;
            }

            if (!empty($this->addToDataMap)) {
                $dataMap = array_merge_recursive($dataMap, $this->addToDataMap);
            }

            $this->dataHandler->start($dataMap, $this->addToCmdMap);
            $this->dataHandler->process_cmdmap();
            $this->dataHandler->process_datamap();

            if (!empty($this->dataHandler->errorLog)) {
                throw new DataHandlerException(count($this->dataHandler->errorLog) . ' errors handling data for item "' . $configurationKey . '". First error: ' . $this->dataHandler->errorLog[0], 1554457506);
            }
        }
    }

    /**
     * Generate random data
     *
     * @param string $configurationKey
     * @param string $field
     * @param array $fieldConfiguration
     * @param int $pid
     * @param $previousFieldsData
     * @return mixed
     * @throws ProviderException
     */
    public function generateData($configurationKey, $field, array $fieldConfiguration, $pid, array $previousFieldsData = [])
    {
        // Public so it can be used in signal slots
        $provider = $this->getItemConfigurationValue($fieldConfiguration, 'provider');

        if (empty($provider)) {
            throw new ProviderException('Provider not set for field "' . $field . '" in item "' . $configurationKey . '"', 1554386438);
        }

        /** @var ProviderInterface $providerClass */
        if (strpos($provider, '\\') === 0) {
            $providerClass = $provider;
        } else {
            $providerClass = '\\WIND\\Randomdata\\Provider\\' . $provider . 'Provider';
        }
        if (!class_exists($providerClass)) {
            throw new ProviderException('Provider "' . $provider . '" does not exist for field "' . $field . '" in item "' . $configurationKey . '"', 1554387194);
        } elseif (!in_array(ProviderInterface::class, class_implements($providerClass))) {
            throw new ProviderException('Provider "' . $provider . '" does not implement ' . ProviderInterface::class . ' for field "' . $field . '" in item "' . $configurationKey . '"', 1554387209);
        }

        if ($provider === 'Relation' && (!isset($fieldConfiguration['pid']) || $fieldConfiguration['pid'] === 'same')) {
            $fieldConfiguration['pid'] = $pid;
        }

        return $providerClass::generate($this->faker, $fieldConfiguration, $this, $previousFieldsData);
    }

    /**
     * Dispatch signal slot
     *
     * @param string $name
     * @param array $arguments
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     * @return void
     */
    protected function dispatchSignalSlot($name, array $arguments)
    {
        /** @var Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signalSlotDispatcher->dispatch(__CLASS__, $name, $arguments);
    }

    /**
     * Output message
     *
     * @param string $message
     * @return void
     */
    protected function output($message)
    {
        if (!empty($this->output)) {
            $this->output->writeln($message);
        }
    }

    protected function outputWithoutNewLine($message)
    {
        if (!empty($this->output)) {
            $this->output->write($message);
        }
    }
}