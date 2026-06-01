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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups;

defined('_JEXEC') or die;



use Joomla\CMS\Helper\TagsHelper as JTagsHelper;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Plugin\System\ArticlesAnywhere\Database;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Tags as TagsHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class Tags extends DataGroup
{
    protected static $db_prefix        = 'tags';
    protected static $default_data_key = 'layout';
    protected static $main_table       = '';
    protected static $prefix           = 'tags';

    /**
     * @return array
     */
    public function getAliases()
    {
        $tags = $this->getTagsItems();

        return TagsHelper::getTagAliases($tags);
    }

    /**
     * @return mixed
     */
    public function getForeachData()
    {
        $tags = $this->getTagsItems();

        foreach ($tags as $tag)
        {
            $tag->text = $tag->title;
        }

        return $tags;
    }

    public function getOutputAliases()
    {
        $tags    = $this->getTagsItems();
        $aliases = TagsHelper::getTagAliases($tags);

        return RL_Array::implode(
            $aliases,
            $this->getAttribute('separator', ','),
            $this->getAttribute('last-separator')
        );
    }

    /**
     * @return mixed
     */
    public function getOutputRaw()
    {
        return self::getTitles();
    }

    public function getOutputTitles()
    {
        $tags   = $this->getTagsItems();
        $titles = TagsHelper::getTagTitles($tags);

        return RL_Array::implode(
            $titles,
            $this->getAttribute('separator', ','),
            $this->getAttribute('last-separator')
        );
    }

    public function getRequiredQueryKeys()
    {
        return ['article.id'];
    }

    public function getSubQueryTypesForWheres($values, $glue = 'OR')
    {
        if (empty($values))
        {
            return [[], []];
        }

        if ($glue === 'AND')
        {
            $includes = [];
            $excludes = [];

            foreach ($values as $value)
            {
                [
                    $value_includes, $value_excludes,
                ] = $this->getSubQueryTypesForWheres([$value], 'OR');

                $includes = [...$includes, ...$value_includes];
                $excludes = [...$excludes, ...$value_excludes];
            }

            $includes = RL_Array::clean($includes);
            $excludes = RL_Array::clean($excludes);

            return [$includes, $excludes];
        }

        [$includes, $excludes] = DB::getIncludesExcludes($values);

        $includes = [self::getSubQueriesForWheres($includes, $glue)];
        $excludes = [self::getSubQueriesForWheres($excludes, $glue)];

        $includes = RL_Array::clean($includes);
        $excludes = RL_Array::clean($excludes);

        return [$includes, $excludes];
    }

    /**
     * @return array
     */
    public function getTagsItems()
    {
        $tags_helper = new JTagsHelper;
        $tags_helper->getItemTags('com_content.article', $this->get('article.id'));

        $tags = $tags_helper->itemTags ?? [];

        $this->applyOrdering($tags);

        return $tags;
    }

    /**
     * @return array
     */
    public function getTitles()
    {
        $tags = $this->getTagsItems();

        return TagsHelper::getTagTitles($tags);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $tags = $this->getTagsItems();

        if (empty($tags))
        {
            return '';
        }

        $layout    = $this->getAttribute('layout', true);
        $use_links = $this->getAttribute('links', true);

        if ( ! $use_links || $this->getAttribute('separator'))
        {
            $layout = false;
        }

        if (
            (RL_Input::getCmd('option') === 'com_finder'
                && RL_Input::getCmd('format') === 'json')
        )
        {
            // Force text output without links for finder indexing, as the Tags RouteHelper causes errors
            $layout    = false;
            $use_links = false;
        }

        if ( ! $layout)
        {
            $separator = $this->getAttribute('separator', ', ');

            return TagsHelper::getOutput($tags, $separator, $this->getAttribute('last-separator'), $use_links);
        }

        return TagsHelper::getOutputViaLayout($tags, $this->getAttribute('layout'));
    }

    /**
     * @param array|object|string $values
     * @param bool                $exclude
     *
     * @return string
     */
    public function getWhere($values, $glue = 'OR')
    {
        if (empty($values))
        {
            return '';
        }

        $values = Data::valuesToSimpleArray($values);

        if (in_array('=*', $values))
        {
            return '';
        }

        [$includes, $excludes] = self::getSubQueryTypesForWheres($values, $glue);

        $wheres = [];

        foreach ($includes as $sub_query)
        {
            $wheres[] = DB::quoteName('article.id') . ' IN (' . (string) $sub_query . ')';
        }

        foreach ($excludes as $sub_query)
        {
            $wheres[] = DB::quoteName('article.id') . ' NOT IN (' . (string) $sub_query . ')';
        }

        return DB::combine($wheres, $glue);
    }

    private function applyOrdering(&$tags)
    {
        $tags = RL_Array::toArray($tags ?? []);

        if (empty($tags))
        {
            return;
        }

        $ordering = $this->attributes->ordering ?? 'title';

        [$ordering, $direction] = explode(' ', $ordering . ' ASC');

        if ($ordering == 'random')
        {
            shuffle($tags);

            return;
        }

        if ($ordering == 'ordering')
        {
            $ordering = 'lft';
        }

        if ( ! isset($tags[0]->{$ordering}))
        {
            return;
        }

        $ordered = [];

        foreach ($tags as $tag)
        {
            $order_id = $tag->{$ordering};

            if (is_numeric($order_id))
            {
                // pad the number with zeros to make sure the order is correct
                $order_id = str_pad($order_id, 10, '0', STR_PAD_LEFT);
            }

            $order_id .= '.' . $tag->id;

            $ordered[$order_id] = $tag;
        }

        $direction === 'DESC'
            ? krsort($ordered)
            : ksort($ordered);

        $tags = $ordered;
    }

    private function getChildIds($parent_ids = [], $level = 1)
    {
        if (empty($parent_ids))
        {
            return [];
        }

        $include_child_tags = $this->getAttribute('include-child-tags');

        if (is_numeric($include_child_tags) && $include_child_tags < $level)
        {
            return [];
        }

        $query = DB::getQuery()
            ->select('tag.id')
            ->from(DB::quoteName('#__tags', 'tag'))
            ->where(DB::is('tag.parent_id', $parent_ids, ['handle_wildcards' => false]));

        $database = new Database($this->database_name);

        $children = $database->getResults($query);

        if (empty($children))
        {
            return [];
        }

        return [...$children, ...$this->getChildIds($children, $level++)];
    }

    private function getIdsByValues($values, $glue = 'OR')
    {
        $query = DB::getQuery()
            ->select('tag.id')
            ->from(DB::quoteName('#__tags', 'tag'))
            ->where($this->getSubQueriesWhere($values, $glue));

        $database = new Database($this->database_name);

        return $database->getResults($query);
    }

    private function getSubQueriesForWheres($values, $glue = 'OR')
    {
        if (empty($values))
        {
            return '';
        }

        $tag_ids = $this->getIdsByValues($values, $glue);

        if ( ! empty($this->getAttribute('include-child-tags')))
        {
            $child_ids = $this->getChildIds($tag_ids);
            $tag_ids   = [...$tag_ids, ...$child_ids];
        }

        return (string) DB::getQuery()
            ->select(DB::quoteName('map.content_item_id'))
            ->from(DB::quoteName('#__contentitem_tag_map', 'map'))
            ->join('LEFT', DB::quoteName('#__tags', 'tag')
                . ' ON ' . DB::quoteName('tag.id') . ' = ' . DB::quoteName('map.tag_id'))
            ->where(DB::is('map.type_alias', 'com_content.article'))
            ->where(DB::is('tag.id', $tag_ids));
    }

    private function getSubQueriesWhere($values, $glue = 'OR')
    {
        if (in_array('=+', $values))
        {
            return '('
                . DB::quoteName('tag.id') . ' != ' . DB::quote('')
                . ' AND '
                . DB::quoteName('tag.id') . ' IS NOT NULL'
                . ')';
        }

        return DB::is(['tag.id', 'tag.title', 'tag.alias'], $values, compact('glue'));
    }
}
