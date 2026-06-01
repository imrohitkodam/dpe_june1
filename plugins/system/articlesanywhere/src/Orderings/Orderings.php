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



use Joomla\Database\DatabaseQuery as JDatabaseQuery;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;

class Orderings
{
    private       $database_name;
    private array $orderings = [];

    /**
     * @param string $ordering
     * @param string $default_direction
     * @param string $database_name
     */
    public function __construct($ordering, $default_direction = 'ASC', $database_name = '')
    {
        $this->database_name = $database_name;
        $this->setOrderings($ordering, $default_direction);
    }

    /**
     * @return Ordering[]
     */
    public function get()
    {
        return $this->orderings;
    }

    /**
     * @return array
     */
    public function getDatabaseStrings()
    {
        $orderings = [];

        foreach ($this->orderings as $ordering)
        {
            $orderings[] = $ordering->getDatabaseString();
        }

        return $orderings;
    }

    /**
     * @param JDatabaseQuery $query
     */
    public function setOnQuery($query)
    {
        foreach ($this->getDatabaseStrings() as $string)
        {
            $query->order($string);
        }
    }

    private function setOrderings($ordering, $default_direction = 'ASC')
    {
        $parts = RL_Array::toArray($ordering);

        foreach ($parts as $ordering)
        {
            if ($ordering === 'random')
            {
                $this->orderings[] = new Ordering('random');
                continue;
            }

            [$order, $direction] = RL_Array::toArray($ordering . ' ' . $default_direction, ' ');
            $data_group = DataHelper::getDataGroup($order, [], '', $this->database_name);

            if ( ! $data_group)
            {
                continue;
            }

            $order = $data_group->getDatabaseKey();

            $this->orderings[] = new Ordering($order, $direction, $data_group);
        }
    }
}
