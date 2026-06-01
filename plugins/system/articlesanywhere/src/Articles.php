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

namespace RegularLabs\Plugin\System\ArticlesAnywhere;

defined('_JEXEC') or die;

use Exception;
use Joomla\Database\DatabaseQuery as JDatabaseQuery;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataTags\DataTags;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\Filter;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\Filters;
use RegularLabs\Plugin\System\ArticlesAnywhere\ForeachTags\Tags as ForeachTags;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\CurrentArticle;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\IfStatements\IfStatements;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;
use RegularLabs\Plugin\System\ArticlesAnywhere\Orderings\Orderings;
use RegularLabs\Plugin\System\ArticlesAnywhere\Pagination\Pagination;

class Articles
{
    static             $unpublished_categories;
    private static int $db_count = 0;
    /**
     * @var Article[]
     */
    private array    $articles;
    private array    $articles_values;
    private object   $attributes;
    private          $current_article_values;
    private DataTags $data_tags;
    private          $database;
    /**
     * @var Filters[]
     */
    private array        $filter_groups;
    private ForeachTags  $foreach_tags;
    private string       $html;
    private IfStatements $if_statements;
    private int          $limit = 1;
    /**
     * @var Numbers[]
     */
    private array      $numbers           = [];
    private Numbers    $numbers_template;
    private int        $offset            = 0;
    private Orderings  $orderings;
    private Pagination $pagination;
    private int        $pagination_limit  = 0;
    private int        $pagination_offset = 0;
    private object     $params;
    /**
     * @var DataGroup[]
     */
    private array  $select_data_groups;
    private string $tag_type;
    private int    $total = 0;

    /**
     * Articles constructor.
     *
     * @param string      $html
     * @param Filters[]   $filter_groups
     * @param object|null $attributes
     * @param string      $tag_type 'article' or 'articles'
     * @param string      $database_name
     */
    public function __construct(
        $html,
        $filter_groups,
        $attributes = null,
        $tag_type = 'article',
        $database_name = ''
    )
    {
        if (empty($html))
        {
            [$data_tag_start, $data_tag_end] = Params::getDataTagCharacters();

            $html = $data_tag_start . 'article' . $data_tag_end;
        }

        $this->html       = $html;
        $this->attributes = $attributes;
        $this->tag_type   = $tag_type;
        $this->database   = new Database($database_name);

        if (isset($this->attributes->per_page) && ! isset($this->attributes->pagination))
        {
            $this->attributes->pagination = true;
        }

        $this->params = Params::get($this->attributes);

        $this->filter_groups = $filter_groups;
        $this->data_tags     = new DataTags($html, $database_name);
        $this->if_statements = new IfStatements($html, $database_name);
        $this->foreach_tags  = new ForeachTags($html, $database_name);

        $this->setFilterValues();

        $data_tag_data_groups     = $this->data_tags->getDataGroups();
        $if_statement_data_groups = $this->if_statements->getDataGroups();
        $foreach_tag_data_groups = $this->foreach_tags->getDataGroups();

        $this->select_data_groups = [...$data_tag_data_groups, ...$if_statement_data_groups];

        $this->select_data_groups = [...$this->select_data_groups, ...$foreach_tag_data_groups];

        $this->orderings = new Orderings($this->params->ordering, $this->params->ordering_direction, $database_name);

        $this->setOffsetAndLimits();

        $this->articles_values = $this->getValues();

        $this->total = $this->total ?: count($this->articles_values);

        $this->pagination = $this->getPagination();

        $total_results       = count($this->articles_values);
        $total_no_limits     = $this->total;
        $total_no_pagination = min($this->limit, $this->total - $this->getOffset());

        $this->numbers_template = new Numbers(
            $total_results,
            $total_no_limits,
            $total_no_pagination,
            $this->limit,
            $this->offset
            , $this->pagination ? $this->pagination->getNumbers() : null
        );

        $this->articles = $this->getArticles();
    }

    /**
     * @param int|string $count
     *
     * @return array
     */
    public function getArticleValues($count)
    {
        if ($count === 'current')
        {
            return $this->getCurrentArticleValues();
        }

        return $this->articles_values[$count - 1] ?? [];
    }

    /**
     * @return array
     */
    public function getCurrentArticleValues()
    {
        $article_id = CurrentArticle::getId();

        if ( ! is_null($this->current_article_values))
        {
            return $this->current_article_values;
        }

        /* @var DataGroup[] $data_groups */
        $data_groups = [
            ...$this->data_tags->getCurrentDataGroups(),
            ...$this->if_statements->getDataGroups(),
            ...$this->foreach_tags->getDataGroups(),
        ];

        foreach ($this->filter_groups as $filters)
        {
            $data_groups = [...$data_groups, ...$filters->getValueDataGroups()];
        }

        $values = $this->getValuesByArticleId(
            $article_id,
            $data_groups,
            true
        );

        $this->current_article_values = $values ?? [];

        return $this->current_article_values;
    }

    /**
     * @param int $count
     *
     * @return Numbers|null
     */
    public function getNumbers($count)
    {
        return $this->numbers[$count] ?? null;
    }

    /**
     * @return string
     */
    public function render()
    {
        if (empty($this->articles))
        {
            return $this->params->output_when_empty ?? '';
        }

        $html = [];

        foreach ($this->articles as $article)
        {
            $articleHtml = $article->render();

            $breakRegex    = Params::getRegex('breaktag');
            $continueRegex = Params::getRegex('continuetag');

            if (RL_RegEx::match($continueRegex, $articleHtml))
            {
                $articleHtml = RL_RegEx::replace($continueRegex, '', $articleHtml);
            }

            if (RL_RegEx::match($breakRegex, $articleHtml))
            {
                $articleHtml = RL_RegEx::replace($breakRegex, '', $articleHtml);
                $html[]      = $articleHtml;
                break;
            }

            $html[] = $articleHtml;
        }

        $html = RL_Array::implode(
            $html,
            $this->attributes->separator ?? '',
            $this->attributes->last_separator ?? null
        );

        return $this->renderPagination('top')
            . $html
            . $this->renderPagination('bottom');

    }

    /**
     * @param string $position
     *
     * @return string
     */
    public function renderPagination($position)
    {
        return $this->pagination->render($position);
    }

    protected function isSingle()
    {

        return $this->tag_type === Params::get()->article_tag;

    }

    /**
     * @param JDatabaseQuery $query
     */
    private function addCategoryStateChecks($query)
    {
        $unpublished_categories = $this->getUnpublishedCategoryIds();

        if (empty($unpublished_categories))
        {
            return;
        }

        $query->where(DB::notIn('article.catid', $unpublished_categories));
    }

    /**
     * @param $query
     */
    private function applyOrdering($query)
    {
        $this->orderings->setOnQuery($query);
    }

    /**
     * @return array|mixed
     */
    private function getArticleIdsByArticleIds(array $ids)
    {
        $selects   = [DB::quoteName('article.id', 'article.id')];
        $wheres    = [DB::is('article.id', $ids, ['handle_wildcards' => false])];
        $joins     = $this->getOrderingJoins();
        $group_bys = [];

        $query = $this->prepareQuery(
            $selects,
            $wheres,
            $joins,
            $group_bys,
            true,
            true
        );
        $this->addCategoryStateChecks($query);

        return $this->database->getResults($query, 'column', true);
    }

    /**
     * @return array|mixed
     */
    private function getArticleIdsByFilters(
        array $filters
    )
    {
        $selects = [DB::quoteName('article.id', 'article.id')];
        [$main, $separate] = $this->getQueryGroups($filters);

        $query = $this->prepareQuery(
            $selects,
            $main->wheres,
            $main->joins,
            $main->group_bys,
            false,
            false
        );
        $this->addCategoryStateChecks($query);

        if ( ! empty($main->pre_orderings))
        {
            $query->order($main->pre_orderings);
        }

        $ids = $this->database->getResults($query, 'column', true);

        if (empty($ids))
        {
            return [];
        }

        foreach ($separate as $group)
        {
            $where = [
                DB::is('article.id', $ids, ['handle_wildcards' => false]),
                ...$group->wheres,
            ];

            $query = $this->prepareQuery(
                $selects,
                $where,
                $group->joins,
                $group->group_bys,
                false,
                false
            );

            if ( ! empty($main->pre_orderings))
            {
                $query->order($main->pre_orderings);
            }

            $ids = $this->database->getResults($query, 'column', true);

            if (empty($ids))
            {
                return [];
            }
        }

        $ids = array_unique($ids);

        return $ids;
    }

    /**
     * @return Article[]
     */
    private function getArticles()
    {
        $articles = [];

        $current_id = CurrentArticle::getId();

        foreach ($this->articles_values as $i => $article_values)
        {
            /* @var DataTags $data_tags */
            $data_tags = RL_Object::clone($this->data_tags);
            /* @var IfStatements $if_statements */
            $if_statements = RL_Object::clone($this->if_statements);
            /* @var ForeachTags $foreach_tags */
            $foreach_tags = RL_Object::clone($this->foreach_tags);

            $count = $i + 1;

            $this->numbers[$count] = RL_Object::clone($this->numbers_template);
            $this->numbers[$count]->setCount($count);
            $this->numbers[$count]->setNumber('is-current', $current_id && $article_values['article.id'] == $current_id);

            $articles[] = new Article(
                $data_tags,
                $if_statements,
                $foreach_tags,
                $this,
                $this->html,
                $count,
                $this->numbers_template->get('total')
            );
        }

        return $articles;
    }

    /**
     * @param $filters Filter[]
     *
     * @return array
     */
    private function getFilterJoins($filters)
    {
        $joins = [];

        foreach ($filters as $filter)
        {
            $joins = [...$joins, ...$filter->getDataGroup()->getJoinsForFilters()];
        }

        return $joins;
    }

    /**
     * @param $filters Filter[]
     *
     * @return array
     */
    private function getFilterPreOrdering($filters)
    {
        $orderings = [];

        foreach ($filters as $filter)
        {
            $ordering = $filter->getDataGroup()->getPreOrdering($filter->getValues());

            if (empty($ordering))
            {
                continue;
            }

            $orderings[] = $ordering;
        }

        $orderings = RL_Array::clean($orderings);

        return $orderings;
    }

    /**
     * @param $filters Filter[]
     *
     * @return array
     */
    private function getFilterSelects($filters)
    {
        $selects = [];

        foreach ($filters as $filter)
        {
            $selects = [...$selects, ...$filter->getDataGroup()->getSelectsForFilters()];
        }

        return $selects;
    }

    /**
     * @param $filters Filter[]
     *
     * @return array
     */
    private function getFilterWheres($filters)
    {
        $wheres = [];

        foreach ($filters as $filter)
        {
            $where = $filter->getDataGroup()->getWhere($filter->getValues(), $filter->getGlue());

            if (empty($where))
            {
                continue;
            }

            $wheres[] = $where;

            // Add ignore filters for specific DataGroup
            // But we can ignore the Articles DataGroup as we already added those
            if ($filter->getDataGroup()->getGroupName() === 'Article')
            {
                continue;
            }

            $ignores_where = $filter->getDataGroup()->getIgnoreWhere();

            if (empty($ignores_where))
            {
                continue;
            }

            $wheres[] = $ignores_where;
        }

        $wheres = RL_Array::clean($wheres);

        return $wheres;
    }

    /**
     * @param $filters Filter[]
     *
     * @return array
     */
    private function getGroupBys($filters)
    {
        $groups = [];

        foreach ($filters as $filter)
        {
            $groups = [...$groups, ...$filter->getDataGroup()->getGroupBys()];
        }

        return $groups;
    }

    /**
     * @return int
     */
    private function getLimit()
    {
        if (isset($this->attributes->limit))
        {
            return (int) $this->attributes->limit ?: 999999;
        }

        return (int) $this->params->limit ?: 999999;

        //        return $this->params->pagination
        //            ? (int) min( $this->params->limit, $this->pagination_limit)
        //            : (int) $this->params->limit;
    }

    /**
     * @return int
     */
    private function getOffset()
    {
        return (int) ($this->attributes->offset ?? 0);
    }

    /**
     * @return array
     */
    private function getOrderingJoins()
    {
        $joins = [];

        foreach ($this->orderings->get() as $ordering)
        {
            $joins = [...$joins, ...$ordering->getJoins()];
        }

        return $joins;
    }

    /**
     * @return array
     */
    private function getOrderingSelects()
    {
        $selects = [];

        foreach ($this->orderings->get() as $ordering)
        {
            $selects = [...$selects, ...$ordering->getSelects()];
        }

        return $selects;
    }

    /**
     * @return Pagination
     */
    private function getPagination()
    {
        $total = min($this->limit, $this->total) - $this->getOffset();
        $limit = min($this->limit, $this->pagination_limit);

        return new Pagination(
            $total,
            $this->pagination_offset,
            $limit,
            $this->params->page_param,
            $this->params
        );
    }

    /**
     * @return array
     */
    private function getQueryGroups($filters)
    {
        $separate = [];
        $main     = (object) [
            'wheres'        => [],
            'joins'         => [],
            'group_bys'     => [],
            'pre_orderings' => [],
        ];

        // Add ignore filters for Articles
        $data_group = DataHelper::getDataGroup('id', $this->attributes, 'Article', $this->database->name);

        $ignores_where = $data_group->getIgnoreWhere();

        if ( ! empty($ignores_where))
        {
            $main->wheres[] = $ignores_where;
        }

        foreach ($filters as $filter)
        {
            $wheres        = $this->getFilterWheres([$filter]);
            $joins         = $this->getFilterJoins([$filter]);
            $pre_orderings = $this->getFilterPreOrdering([$filter]);
            $group_bys = $this->getGroupBys([$filter]);

            if ($this->hasComplexFilters($wheres))
            {
                $separate[] = (object) [
                    'wheres'    => $wheres,
                    'joins'     => $joins,
                    'group_bys' => $group_bys,
                ];
                continue;
            }

            $main->wheres        = [...$main->wheres, ...$wheres];
            $main->joins         = [...$main->joins, ...$joins];
            $main->group_bys     = [...$main->group_bys, ...$group_bys];
            $main->pre_orderings = [...$main->pre_orderings, ...$pre_orderings];
        }

        return [$main, $separate];
    }

    /**
     * @return int|mixed
     */
    private function getQueryLimit()
    {
        if ($this->isSingle())
        {
            return 1;
        }

        if ( ! $this->params->pagination)
        {
            return $this->getLimit();
        }

        $max_limit = $this->limit - $this->pagination_offset;

        return min($max_limit, $this->pagination_limit);
    }

    /**
     * @return int
     */
    private function getQueryOffset()
    {
        if ($this->isSingle())
        {
            return 0;
        }

        if ( ! $this->params->pagination)
        {
            return $this->getOffset();
        }

        return $this->offset + $this->pagination_offset;
    }

    /**
     * @param $query
     */
    private function getUnpublishedCategoryIds()
    {
        if ( ! is_null(self::$unpublished_categories))
        {
            return self::$unpublished_categories;
        }

        $query = DB::getQuery()
            ->from(DB::quoteName('#__categories', 'category'))
            ->select('category.id')
            ->join(
                'LEFT',
                DB::quoteName('#__categories', 'parent'),
                DB::quoteName('category.parent_id') . ' = ' . DB::quoteName('parent.id')
            )
            ->where(DB::is('category.extension', 'com_content'))
            ->where(DB::combine([
                DB::in('category.published', [0, -1, -2]),
                DB::in('parent.published', [0, -1, -2]),
            ], 'OR'))
            ->group('category.id');

        self::$unpublished_categories = $this->database->getResults($query, 'column', true);

        return self::$unpublished_categories;
    }

    /**
     * @param null $data_groups
     *
     * @return array
     */
    private function getValueJoins($data_groups = null)
    {
        $data_groups ??= $this->select_data_groups;

        $joins = [];

        foreach ($data_groups as $data_group)
        {
            $joins = [...$joins, ...$data_group->getJoins()];
        }

        return $joins;
    }

    /**
     * @param null $data_groups
     *
     * @return array
     */
    private function getValueSelects($data_groups = null)
    {
        $data_groups ??= $this->select_data_groups;

        $selects = [];

        foreach ($data_groups as $data_group)
        {
            $selects = [...$selects, ...$data_group->getSelects()];
        }

        return $selects;
    }

    /**
     * @return array|mixed
     */
    private function getValues()
    {
        if ($this->isSingle())
        {
            return $this->getValuesFromSingleArticleTag();
        }

        $ids = [];

        // Grab all article IDs based on the filters
        // Ignore ordering and limits
        foreach ($this->filter_groups as $filters)
        {
            $ids_by_filters = $this->getArticleIdsByFilters($filters->get());

            $ids = [...$ids, ...$ids_by_filters];
        }

        $ids = array_unique($ids);

        if (isset($this->attributes->minimum) && count($ids) < (int) $this->attributes->minimum)
        {
            $ids = [];
        }

        $this->total = count($ids);

        if (empty($ids))
        {
            return [];
        }

        if ( ! $this->shouldUseSeparateLimitOrderingQuery(count($ids)))
        {
            // Get the values based on the IDs
            // And apply order and limits
            $ids = $this->getValuesByArticleIds($ids, null, true, true);

            return $ids;
        }

        // Apply order and limits
        // No need to do this if only 1 ID is found
        $ids = $this->getArticleIdsByArticleIds($ids);

        if (empty($ids))
        {
            return [];
        }

        // Get the values based on the IDs (keeping the ordering of the IDs)
        return $this->getValuesByArticleIds($ids, null, false, false);

    }

    /**
     * @return array|mixed
     */
    private function getValuesByArticleId(
        int  $id,
             $data_groups = null,
        bool $use_local_db = false
    )
    {
        $values = $this->getValuesByArticleIds(
            [$id],
            $data_groups,
            false,
            1,
            $use_local_db
        );

        return $values[0] ?? [];
    }

    /**
     * @return array|mixed
     */
    private function getValuesByArticleIds(
        array $ids,
              $data_groups = null,
        bool  $apply_ordering = true,
              $apply_limits = true,
        bool  $use_local_db = false
    )
    {
        $selects = [
            DB::quoteName('article.id', 'article.id'), ...$this->getValueSelects($data_groups),
        ];
        $wheres  = [DB::is('article.id', $ids, ['handle_wildcards' => false])];
        $joins   = $this->getValueJoins($data_groups);

        $query = $this->prepareQuery(
            $selects,
            $wheres,
            $joins,
            [],
            $apply_ordering,
            $apply_limits !== 1 ? $apply_limits : false
        );

        if (empty($query->order) && count($ids) > 1)
        {
            // Use the FIELD() function to keep the ordering of the given $ids
            $query->order('FIELD(article.id,' . implode(',', $ids) . ')');
        }

        if ($apply_limits === 1)
        {
            $query->setLimit(1, 0);
        }

        $database = $use_local_db ? new Database : $this->database;

        return $database->getResults($query, 'assocList', true);
    }

    /**
     * @param null $filters
     *
     * @return array|mixed
     */
    private function getValuesFromSingleArticleTag($filters = null)
    {
        $filters ??= $this->filter_groups[0]->get();

        $article_ids = $this->getArticleIdsByFilters($filters);

        return $this->getValuesByArticleIds(
            $article_ids,
            null,
            true,
            1
        );
    }

    /**
     * @return bool
     */
    private function hasComplexFilters(array $wheres)
    {
        foreach ($wheres as $where)
        {
            if (
                str_contains($where, ' <')
                || str_contains($where, ' >')
                || str_contains($where, 'LIKE')
            )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function hasExoticOrderings()
    {
        $ordering_joins = $this->getOrderingJoins();
        $value_joins    = $this->getValueJoins();

        return ! empty(array_diff($ordering_joins, $value_joins));
    }

    /**
     * @return array|mixed
     */
    private function prepareQuery(
        array $selects,
        array $wheres = [],
        array $joins = [],
        array $group_bys = [],
        bool  $apply_ordering = true,
        bool  $apply_limits = true
    )
    {
        if ($apply_ordering)
        {
            $selects = [...$selects, ...$this->getOrderingSelects()];
            $joins   = [...$joins, ...$this->getOrderingJoins()];
        }

        $selects   = RL_Array::clean($selects);
        $wheres    = RL_Array::clean($wheres);
        $joins     = RL_Array::clean($joins);
        $group_bys = RL_Array::clean($group_bys);

        if (empty($selects))
        {
            return [];
        }

        $query = DB::getQuery()
            ->from(DB::quoteName('#__content', 'article'))
            ->select($selects);

        if ( ! empty($wheres))
        {
            $query->where($wheres);
        }

        foreach ($joins as $table => $condition)
        {
            $query->join('LEFT', $table, $condition);
        }

        if ($apply_ordering)
        {
            $this->applyOrdering($query);
        }
        
        foreach ($group_bys as $table => $column)
        {
            $query->group(DB::quoteName($table . '.' . $column));
        }
        
        $offset = $apply_limits ? $this->getQueryOffset() : 0;
        $limit  = $apply_limits ? $this->getQueryLimit() : 0;

        // MySQL needs a limit if you want an offset
        if ($offset > 0 && $limit === 0)
        {
            $limit = 9999;
        }

        $query->setLimit($limit, $offset);

        return $query;
    }

    /**
     * @return void
     */
    private function setFilterValues()
    {
        $values = $this->getCurrentArticleValues();

        foreach ($this->filter_groups as $filters)
        {
            $data_groups = $filters->getValueDataGroups();

            foreach ($data_groups as $data_group)
            {
                $data_group->setValues($values, null);
            }
        }
    }

    /**
     *
     */
    private function setOffsetAndLimits()
    {
        if ($this->isSingle())
        {
            return;
        }

        $this->setPaginationOffsetAndLimit();

        if (
            isset($this->attributes->limit)
            && RL_RegEx::match('^(?<from>[0-9]+)-(?<to>[0-9]+)$', $this->attributes->limit, $match)
        )
        {
            $this->attributes->limit  = $match['to'] - $match['from'] + 1;
            $this->attributes->offset = $match['from'] - 1;
        }

        $this->offset = $this->getOffset();
        $this->limit  = $this->getLimit();
    }

    /**
     * @throws Exception
     */
    private function setPaginationOffsetAndLimit()
    {
        if ($this->isSingle())
        {
            return;
        }

        if ( ! $this->params->pagination)
        {
            return;
        }

        $page_param = $this->params->page_param;
        $page       = RL_Input::getInt($page_param, 1);

        $this->pagination_offset = $this->params->per_page * ($page - 1);
        $this->pagination_limit  = $this->params->per_page;
    }

    /**
     * @param $nr_of_results
     *
     * @return bool
     */
    private function shouldUseSeparateLimitOrderingQuery($nr_of_results)
    {
        if ($nr_of_results > $this->limit)
        {
            return true;
        }

        return $this->hasExoticOrderings();
    }
}
