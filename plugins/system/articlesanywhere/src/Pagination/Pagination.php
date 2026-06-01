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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Pagination;

defined('_JEXEC') or die;



use Joomla\CMS\Pagination\Pagination as JPagination;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\RegEx as RL_RegEx;

class Pagination
{
    private $pagination;
    private $params;

    public function __construct($total, $limitstart, $limit, $prefix, $params)
    {
        $this->params = $params;

        $this->pagination = new JPagination(
            $total,
            $limitstart,
            $limit,
            $prefix
        );

        if ( ! $this->params->pagination)
        {
            return;
        }

        $query = $this->getUrlParams();

        if ($query)
        {
            $this->pagination->setAdditionalUrlParam($query . '&', '');
        }
    }

    public function get($key)
    {
        return $this->pagination->{$key} ?? null;
    }

    public function getNumbers()
    {
        /*
         * limitstart
         * limit
         * total
         * prefix
         * pagesStart
         * pagesStop
         * pagesCurrent
         * pagesTotal
         */
        return (object) get_object_vars($this->pagination);
    }

    public function render($position = 'bottom')
    {
        if ( ! $this->params->pagination)
        {
            return '';
        }

        $positions = RL_Array::toArray(
            str_replace(
                'both',
                'top,bottom',
                $this->params->pagination_position
            )
        );

        if ( ! in_array($position, $positions, true))
        {
            return '';
        }

        if ( ! $this->get('pagesTotal'))
        {
            return '';
        }

        $html = $this->getPagesLinks();

        if ($html === '')
        {
            return '';
        }

        // @TODO: Move to layout???
        if ($this->params->pagination_results)
        {
            $html = '<p class="counter float-right pt-3 pr-2">'
                . $this->pagination->getPagesCounter()
                . '</p>'
                . $html;
        }

        return '<div class="articlesanywhere_pagination w-100">'
            . $html
            . '</div>';
    }

    private function cleanUrl($url)
    {
        $url = RL_RegEx::replace('&amp;', '&', $url);

        $uri = parse_url($url);

        if (empty($uri['query']))
        {
            return $url;
        }

        parse_str($uri['query'], $query);

        $page_name = $this->get('prefix');

        if ( ! isset($query[$page_name . 'limitstart']))
        {
            return $url;
        }

        $page_number = $query[$page_name . 'limitstart'] / $this->get('limit') + 1;

        $query[$page_name] = $page_number;
        unset($query[$page_name . 'limitstart']);

        $uri['query'] = http_build_query($query);

        return http_build_url($uri);
    }

    private function getPagesLinks()
    {
        $html = $this->pagination->getPagesLinks();

        $prefix = $this->get('prefix');

        if ( ! RL_RegEx::matchAll(
            'href="(?<url>[^"]*' . RL_RegEx::quote($prefix) . 'limitstart=[^"]*)"',
            $html,
            $matches)
        )
        {
            return $html;
        }

        foreach ($matches as $match)
        {
            $html = str_replace(
                $match[0],
                'href="' . $this->cleanUrl($match['url']) . '"',
                $html
            );
        }

        return $html;
    }

    private function getUrlParams()
    {
        $prefix = $this->get('prefix');
        $query  = $_SERVER['QUERY_STRING'];

        $query = RL_RegEx::replace(
            '(&(amp;)?)?' . RL_RegEx::quote($prefix) . '=[0-9]+',
            '',
            $query
        );

        return $query;
    }
}
