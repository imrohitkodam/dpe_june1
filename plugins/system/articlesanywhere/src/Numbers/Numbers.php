<?php
/**
 * @package         Articles Anywhere
 * @version         17.2.10PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Numbers;

defined('_JEXEC') or die;

use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;

class Numbers
{
    private $aliases;
    private $numbers;
    private $pagination;

    public function __construct(
        $total_results,
        $total_no_limits,
        $total_no_pagination,
        $limit,
        $offset,
        $pagination_numbers = null
    )
    {
        $this->aliases = self::getAliases();
        $this->initNumbers();

        $this->setNumber('total', $total_results);
        $this->setNumber('total-no-limit', $total_no_limits);
        $this->setNumber('total-no-pagination', $total_no_pagination);
        $this->setNumber('limit', $limit);
        $this->setNumber('offset', $offset);

        $this->pagination = $pagination_numbers;
        $this->setPage();
    }

    public static function getAliases()
    {
        return [
            'first'  => 'is-first',
            'last'   => 'is-last',
            'even'   => 'is-even',
            'uneven' => 'is-uneven',

            'first-no-limit'  => 'is-first-no-limit',
            'last-no-limit'   => 'is-last-no-limit',
            'even-no-limit'   => 'is-even-no-limit',
            'uneven-no-limit' => 'is-uneven-no-limit',

            'first-no-pagination'  => 'is-first-no-pagination',
            'last-no-pagination'   => 'is-last-no-pagination',
            'even-no-pagination'   => 'is-even-no-pagination',
            'uneven-no-pagination' => 'is-uneven-no-pagination',

            'total-pages' => 'pages',
        ];
    }

    public static function getDefaultNumbers()
    {
        return [
            ...self::getDefaultCountsByType(),
            ...self::getDefaultCountsByType('no-limit'),
            ...self::getDefaultCountsByType('no-pagination'),
            'limit'             => 1,
            'offset'            => 0,
            'pages'             => 1,
            'total-pages'       => 1,
            'page'              => 1,
            'per-page'          => 1,
            'is-first-page'     => true,
            'is-last-page'      => true,
            'has-next-page'     => false,
            'has-previous-page' => false,
            'next-page'         => 1,
            'previous-page'     => 1,
        ];
    }

    public function get($key)
    {
        if (RL_RegEx::match('every-(?<number>[0-9]+)', $key, $match))
        {
            return $this->isEvery($match['number']);
        }

        if (RL_RegEx::match('is-(?<number>[0-9]+)-of-(?<column>[0-9]+)', $key, $match))
        {
            return $this->isColumn($match['number'], $match['column']);
        }

        return $this->getNumber($key) ?? null;
    }

    public function setCount($count)
    {
        $this->setCountByType(
            $count
        );

        $page_offset = ($this->getNumber('page') - 1) * $this->getNumber('per-page');

        $this->setCountByType(
            $count + $page_offset,
            'no-pagination'
        );

        $this->setCountByType(
            $count + $page_offset + $this->getNumber('offset'),
            'no-limit'
        );
    }

    public function setNumber($key, $value)
    {
        $this->numbers->{$key} = $value;
    }

    private static function getDefaultCountsByType($suffix = '')
    {
        $suffix = $suffix ? '-' . $suffix : '';

        return [
            'is-current' . $suffix   => false,
            'total' . $suffix        => 1,
            'count' . $suffix        => 1,
            'is-first' . $suffix     => true,
            'is-last' . $suffix      => true,
            'has-next' . $suffix     => true,
            'has-previous' . $suffix => false,
            'next' . $suffix         => 1,
            'previous' . $suffix     => 1,
            'is-even' . $suffix      => false,
            'is-uneven' . $suffix    => true,
        ];
    }

    private function getNumber($key)
    {
        $key = RL_String::toDashCase($key);

        if (isset($this->aliases[$key]))
        {
            $key = $this->aliases[$key];
        }

        return $this->numbers->{$key};
    }

    private function initNumbers()
    {
        $this->numbers = (object) self::getDefaultNumbers();
    }

    private function isColumn($number = 1, $column_count = 1)
    {
        // Make sure the number is below the total column count
        // number will be 0 when it is equal to the column count
        // ie: col_1_of_3 = 1, col_3_of_3 = 0
        $number = $number % $column_count;

        return $this->getNumber('count') % $column_count === $number;
    }

    private function isEvery($number = 1)
    {
        return $this->getNumber('count') % $number === 0;
    }

    private function setCountByType($count, $suffix = '')
    {
        $suffix = $suffix ? '-' . $suffix : '';

        $first    = 1;
        $last     = $this->numbers->{'total' . $suffix};
        $is_first = $count === $first;
        $is_last  = $count === $last;

        $this->setNumber('count' . $suffix, $count);
        $this->setNumber('is-first' . $suffix, $is_first);
        $this->setNumber('is-last' . $suffix, $is_last);
        $this->setNumber('has-next' . $suffix, ! $is_last);
        $this->setNumber('has-previous' . $suffix, ! $is_first);
        $this->setNumber('next' . $suffix, $is_last ? $first : $count + 1);
        $this->setNumber('previous' . $suffix, $is_first ? $last : $count - 1);
        $this->setNumber('is-even' . $suffix, ($count % 2) === 0);
        $this->setNumber('is-uneven' . $suffix, ($count % 2) !== 0);
    }

    private function setPage()
    {
        if (empty($this->pagination) || ! $this->pagination->limit)
        {
            return;
        }

        $pages = $this->pagination->pagesTotal;
        $page  = min($this->pagination->pagesCurrent, $pages);

        $is_first_page = $page === 1;
        $is_last_page  = $page === $pages;

        $this->setNumber('pages', $pages);
        $this->setNumber('page', $page);
        $this->setNumber('per-page', $this->pagination->limit);
        $this->setNumber('is-first-page', $is_first_page);
        $this->setNumber('is-last-page', $is_last_page);
        $this->setNumber('has-next-page', ! $is_last_page);
        $this->setNumber('has-previous_page', ! $is_first_page);
        $this->setNumber('next-page', $is_last_page ? 1 : $page + 1);
        $this->setNumber('previous-page', $is_first_page ? $pages : $page - 1);
    }
}
