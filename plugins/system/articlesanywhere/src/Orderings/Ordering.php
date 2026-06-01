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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Orderings;

defined('_JEXEC') or die;



use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class Ordering
{
    private $data_group;
    private $direction;
    private $order;

    /**
     * @param string         $key
     * @param string         $direction
     * @param DataGroup|null $data_group
     */
    public function __construct($order, $direction = 'ASC', $data_group = null)
    {
        $this->data_group = $data_group;
        $this->direction  = $direction;
        $this->order      = $order;
    }

    /**
     * @return DataGroup
     */
    public function getDataGroup()
    {
        return $this->data_group;
    }

    /**
     * @return string
     */
    public function getDatabaseString()
    {
        if ($this->order === 'random')
        {
            return 'RAND()';
        }

        $key       = DB::quoteName($this->order);
        $direction = $this->direction;

        $orderings = [
            // Check for NULL and empty fields first so they are ordered last
            $key . ' IS NULL',
            // Work around issue with DATETIME fields throwing errors on MySQL 8 when comparing to a string
            'CAST(' . $key . ' AS CHAR(1)) = ' . DB::quote(''),
            'CAST(' . $key . ' AS CHAR(10)) = ' . DB::quote('0000-00-00'),
            // Use casting to SIGNED to deal with numeric strings
            // But keep them before alpha strings
            'CAST(' . $key . ' AS SIGNED INTEGER) = 0',
            'CAST(' . $key . ' AS SIGNED INTEGER) ' . $direction,
            $key . ' ' . $direction,
        ];

        return implode(', ', $orderings);
    }

    /**
     * @return string
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * @return array
     */
    public function getJoins()
    {
        if ( ! $this->data_group)
        {
            return [];
        }

        return $this->data_group->getJoinsForFilters();
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return array
     */
    public function getSelects()
    {
        if ( ! $this->data_group)
        {
            return [];
        }

        return $this->data_group->getSelectsForFilters();
    }
}
