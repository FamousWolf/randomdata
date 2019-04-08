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
 * Date Time Provider
 */
class DateTimeProvider implements ProviderInterface
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
            'minimum' => '-30 years',
            'maximum' => 'now',
            'timezone' => null,
            'format' => 'c'
        ], $configuration);

        return $faker->dateTimeBetween($configuration['minimum'], $configuration['maximum'], $configuration['timezone'])->format($configuration['format']);
    }
}