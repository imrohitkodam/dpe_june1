<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

namespace PerfectPublisher\Infrastructure\Service\Cms\Joomla\ContentGenerator;

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Contracts\ContentGeneratorInterface;
use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\JoomlaVersionAwareTrait;

final class ContentGeneratorFactory
{
    use JoomlaVersionAwareTrait;

    public function __construct()
    {
        $this->detectJoomlaVersion();
    }

    public function get(): ContentGeneratorInterface
    {
        $constructor = $this->factoryForJoomlaVersion(function () {
            return new Joomla3Article();
        },
        function () {
            return new Joomla4Article();
        });

        return $constructor();
    }
}
