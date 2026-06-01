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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Filters;

defined('_JEXEC') or die;

use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\CurrentArticle;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Filters
{
    private        $database_name;
    private array  $filters = [];
    private        $params;
    private string $tag_type;

    /**
     * @param array  $filter_data
     * @param array  $params
     * @param string $tag_type 'article' or 'articles'
     * @param string $database_name
     */
    public function __construct(
        $filter_data,
        $params = [],
        $tag_type = 'articles',
        $database_name = ''
    )
    {
        $this->params        = $params;
        $this->tag_type      = $tag_type;
        $this->database_name = $database_name;

        if (empty($filter_data) && $this->isSingle())
        {
            $filter_data = [
                'id' => CurrentArticle::getId(),
            ];
        }

        $this->setFilters($filter_data);
    }

    /**
     * @return Filter[]
     */
    public function get()
    {
        return $this->filters;
    }

    /**
     * @return array
     */
    public function getValueDataGroups()
    {
        $data_groups = [];

        foreach ($this->filters as $filter)
        {
            $data_groups = [...$data_groups, ...$filter->getValueDataGroups()];
        }

        return $data_groups;
    }

    protected function isSingle()
    {

        return $this->tag_type === Params::get()->article_tag;

    }

    private function removeUnsupportedFilterKeys(&$data)
    {

        return;
    }

    private function setFilters($data)
    {
        if (empty($data))
        {
            return;
        }

        $this->removeUnsupportedFilterKeys($data);

        foreach ($data as $key => $data_value)
        {
            $data_group = DataHelper::getDataGroup($key, $this->params, '', $this->database_name);

            if ( ! $data_group)
            {
                continue;
            }

            $this->filters[] = new Filter($key, $data_value, $data_group, $this->database_name);
        }
    }
}
