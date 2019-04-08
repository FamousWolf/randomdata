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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WIND\Randomdata\Service\RandomdataService;

/**
 * Relation Provider
 */
class RelationProvider implements ProviderInterface
{
    /**
     * Generate
     *
     * @param Generator $faker
     * @param array $configuration
     * @param RandomdataService $randomdataService
     * @return string
     */
    static public function generate(Generator $faker, array $configuration, RandomdataService $randomdataService)
    {
        $configuration = array_merge([
            'minimum' => 0,
            'maximum' => 1,
        ], $configuration);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($configuration['table']);
        $queryBuilder->select('uid')->from($configuration['table'])->where(
            $queryBuilder->expr()->eq('pid', (int)$configuration['pid'])
        );
        $rows = $queryBuilder->execute();

        $list = [];
        foreach ($rows as $row) {
            $list[] = $row['uid'];
        }
        $randomList = self::array_random($list, rand($configuration['minimum'], $configuration['maximum']));
        if (is_array($randomList)) {
            return implode(',', $randomList);
        }
        return $randomList;
    }

    /**
     * Random array from array
     *
     * @param array $array
     * @param int $count
     * @return array|mixed
     */
    static public function array_random(array $array, $count = 1)
    {
        if ($count < 1) {
            return [];
        }

        shuffle($array);
        $r = [];
        for ($i = 0; $i < $count; $i++) {
            $r[] = $array[$i];
        }
        return $count === 1 ? $r[0] : $r;
    }
}