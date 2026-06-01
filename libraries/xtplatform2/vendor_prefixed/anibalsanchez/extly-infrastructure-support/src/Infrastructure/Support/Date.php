<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2021 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Support;

use XTP_BUILD\Carbon\Carbon;

class Date extends Carbon
{
    const NOW = 'now';

    const UTC = 'utc';

    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    const DATE_ZERO = '0000-00-00 00:00:00';

    public function __construct($time = null, $tz = null)
    {
        if (!$tz) {
            $tz = self::UTC;
        }

        parent::__construct($time, $tz);
    }

    public function toPhpDateTime()
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($this->timestamp);
        $dateTimeZone = $this->timezone;

        if (\is_string($this->timezone)) {
            $dateTimeZone = new \DateTimeZone($this->timezone);
        }

        return $dateTime->setTimezone($dateTimeZone);
    }

    public static function formatDateTime(\DateTime $dateTime)
    {
        return $dateTime->format(self::DATETIME_FORMAT);
    }

    public function toSql()
    {
        return $this->format(self::DATETIME_FORMAT);
    }

    public function localeFormat($pattern, $locale = 'en_US')
    {
        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL
        );
        $formatter->setPattern($pattern);

        return $formatter->format($this->toPhpDateTime());
    }
}
