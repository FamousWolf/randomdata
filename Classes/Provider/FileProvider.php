<?php
namespace WIND\Randomdata\Provider;

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

use Faker\Generator;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use WIND\Randomdata\Exception\ProviderException;
use WIND\Randomdata\Service\RandomdataService;

/**
 * File Provider
 */
class FileProvider implements ProviderInterface
{
    /**
     * Generate
     *
     * @param Generator $faker
     * @param array $configuration
     * @param RandomdataService $randomdataService
     * @param array $previousFieldsData
     * @return string
     * @throws ProviderException
     */
    static public function generate(Generator $faker, array $configuration, RandomdataService $randomdataService, array $previousFieldsData)
    {
        $configuration = array_merge([
            'minimum' => 1,
            'maximum' => 1,
            'referenceFields' => [],
        ], $configuration);

        if (is_numeric($configuration['__recordUid'])) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->select('uid')->from('sys_file_reference')->where(
                $queryBuilder->expr()->eq('uid_foreign', (int)$configuration['__recordUid']),
                $queryBuilder->expr()->eq('tablenames', (int)$configuration['__table']),
                $queryBuilder->expr()->eq('fieldname', (int)$configuration['__field'])
            );
            $rows = $queryBuilder->execute()->fetchAll();
            foreach ($rows as $row) {
                $randomdataService->addToCmdMap([
                    'sys_file_reference' => [
                        $row['uid'] => [
                            'delete' => 1
                        ]
                    ]
                ]);
            }
        }

        if (!empty($configuration['source'])) {
            $sourceAbsolutePath = Environment::getPublicPath() . '/' . trim($configuration['source'], '/') . '/';

            if (is_dir($sourceAbsolutePath)) {
                $count = $faker->numberBetween($configuration['minimum'], $configuration['maximum']);
                $files = self::getRandomFiles($sourceAbsolutePath, $count);

                if (!empty($files)) {
                    $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                    $references = [];
                    foreach ($files as $file) {
                        $fileObject = $resourceFactory->retrieveFileOrFolderObject($file);
                        $referenceUid = $randomdataService->getNewUid();
                        $referenceFieldValues = [
                            'table_local' => 'sys_file',
                            'uid_local' => $fileObject->getUid(),
                            'tablenames' => $configuration['__table'],
                            'uid_foreign' => $configuration['__recordUid'],
                            'fieldname' => $configuration['__field'],
                            'pid' => $configuration['__pid'],
                        ];
                        foreach ($configuration['referenceFields'] as $referenceField => $referenceFieldConfiguration) {
                            $referenceFieldValues[$referenceField] = $randomdataService->generateData('FileProvider:sys_file_reference', $referenceField, $referenceFieldConfiguration, $configuration['__pid'], $referenceFieldValues);
                        }
                        $randomdataService->addToDataMap([
                            'sys_file_reference' => [
                                $referenceUid => $referenceFieldValues
                            ]
                        ]);
                        $references[] = $referenceUid;
                    }

                    return implode(',', $references);
                }
            }
        }

        return '';
    }

    /**
     * Get random files
     *
     * @param string $source
     * @param int $count
     * @return array
     */
    static protected function getRandomFiles($source, $count)
    {
        if ($count < 1) {
            return [];
        }

        $files = array_filter(glob($source . '*', GLOB_MARK), function($item) {
            return substr($item, -1) !== '/';
        });

        shuffle($files);

        if ($count > count($files)) {
            return $files;
        }

        return array_slice($files, -$count);
    }
}
