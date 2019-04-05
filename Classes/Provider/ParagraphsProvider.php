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

/**
 * Paragraphs Provider
 */
class ParagraphsProvider implements ProviderInterface
{
    /**
     * Generate
     *
     * @param \Faker\Generator $faker
     * @param array $configuration
     * @return string
     */
    static public function generate(Generator $faker, array $configuration = [])
    {
        $configuration = array_merge([
            'minimum' => 1,
            'maximum' => 5,
            'sentences' => 10,
            'html' => false,
        ], $configuration);

        $count = rand($configuration['minimum'], $configuration['maximum']);

        $paragraphs = [];
        for ($i = 0; $i < $count; $i++) {
            $paragraphs[] = $faker->paragraph($configuration['sentences']);
        }

        if ($configuration['html']) {
            $paragraphs = array_map('htmlspecialchars', $paragraphs);
            return '<p>' . implode('</p><p>', $paragraphs) . '</p>';
        }

        return implode("\n\n", $paragraphs);
    }
}