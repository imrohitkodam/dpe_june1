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

use XTP_BUILD\Extly\Infrastructure\Creator\CreatorTrait;
use XTP_BUILD\Illuminate\Support\Collection as LaravelCollection;

class Collection extends LaravelCollection
{
    use CreatorTrait;

    public function sanitize()
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $k = (string) Estring::create($key)->sanitize();

            if (\is_string($value)) {
                if ('params' === $k) {
                    $v = Estring::create($value)
                        ->decodeJson()
                        ->sanitize()
                        ->toJson();
                } else {
                    $v = (string) Estring::create($value)->sanitize();
                }
            } else {
                $v = $value;
            }

            $results[$k] = $v;
        }

        return self::create($results);
    }
}
