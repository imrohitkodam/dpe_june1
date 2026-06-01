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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\ForeachTags;

defined('_JEXEC') or die;

use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\IfStatements\IfStatements;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;

class Tags
{
    private $database_name;
    /**
     * @var IfStatements
     */
    private $if_statements;
    /**
     * @var Tag[]
     */
    private array $tags = [];

    /**
     * @param string $string
     */
    public function __construct($string, $database_name = '')
    {
        $this->database_name = $database_name;
        $this->if_statements = new IfStatements($string, $database_name, true);
        $this->setTags($string);
    }

    /**
     * @return array
     */
    public function getDataGroups()
    {
        $data_groups = [];

        foreach ($this->tags as $tag)
        {
            $data_groups = [...$data_groups, ...$tag->getDataGroups()];
        }

        return $data_groups;
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param $html
     */
    public function replace(&$html)
    {
        foreach ($this->tags as $tag)
        {
            $tag->replace($html);
        }
    }

    /**
     * @param array   $values
     * @param Numbers $numbers
     */
    public function setValues($values, Numbers $numbers)
    {
        foreach ($this->tags as &$tag)
        {
            $tag->setValues($values, $numbers);
        }
    }

    /**
     * @param string $string
     */
    private function setTags($string)
    {
        $this->tags = [];

        $regex = Params::getRegex('foreachtag');

        RL_RegEx::matchAll($regex, $string, $matches);

        if (empty($matches))
        {
            return;
        }

        foreach ($matches as $match)
        {
            $this->tags[] = new Tag($match, $this->if_statements, $this->database_name);
        }
    }
}
