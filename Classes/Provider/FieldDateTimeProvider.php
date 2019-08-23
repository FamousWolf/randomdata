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
use WIND\Randomdata\Service\RandomdataService;

/**
 * Field Date Time Provider
 */
class FieldDateTimeProvider implements ProviderInterface
{
    /**
     * Generate
     *
     * @param Generator $faker
     * @param array $configuration
     * @param RandomdataService $randomdataService
     * @param array $previousFieldsData
     * @return string
     * @throws \Exception
     */
    static public function generate(Generator $faker, array $configuration, RandomdataService $randomdataService, array $previousFieldsData)
    {
        $configuration = array_merge([
            'field' => null,
            'fieldDateFormat' => 'c',
            'interval' => '+1 day',
            'timezone' => null,
            'format' => 'c'
        ], $configuration);

        if (!empty($configuration['field']) && !empty($previousFieldsData[$configuration['field']])) {
            $fieldDate = \DateTime::createFromFormat($configuration['fieldDateFormat'], $previousFieldsData[$configuration['field']]);
        } else {
            $fieldDate = new \DateTime();
        }

        return $faker->dateTimeInInterval($fieldDate, $configuration['interval'], $configuration['timezone'])->format($configuration['format']);
    }
}