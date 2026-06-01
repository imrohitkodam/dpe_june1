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



use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\PluginTag as RL_PluginTag;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataTags\DataTag;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataTags\DataTags;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\IfStatements\IfStatements;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;

class Tag
{
    private object          $attributes;
    private DataGroup|false $data_group;
    private string          $database_name;
    private IfStatements    $if_statements;
    private array           $match_data;

    /**
     * @param array $match
     */
    public function __construct(array $match, $if_statements, $database_name = '')
    {
        $this->match_data    = $match;
        $this->if_statements = $if_statements;
        $this->database_name = $database_name;

        $this->attributes = RL_PluginTag::getAttributesFromString(
            $match['attributes'],
            'data',
            [],
            'underscore'
        );

        $this->data_group = DataHelper::getDataGroup($this->attributes->data ?? '', [], '', $database_name);
    }

    public function applyLimitToRows(&$rows)
    {
        if (isset($this->attributes->minimum) && count($rows) < (int) $this->attributes->minimum)
        {
            $rows = [];

            return;
        }

        if (empty($this->attributes->limit) && empty($this->attributes->offset))
        {
            return;
        }

        $limit  = $this->attributes->limit ?? null;
        $offset = $this->attributes->offset ?? 0;

        if (RL_RegEx::match('^(?<from>[0-9]+)-(?<to>[0-9]+)$', $limit, $match))
        {
            $limit  = $match['to'] - $match['from'] + 1;
            $offset = $match['from'] - 1;
        }

        $rows = array_slice($rows, (int) $offset, (int) $limit);

        if (isset($this->attributes->minimum) && count($rows) < (int) $this->attributes->minimum)
        {
            $rows = [];
        }
    }

    public function applyOrderingToRows(&$rows)
    {
        if (empty($this->attributes->ordering))
        {
            return;
        }

        [$order, $direction] = RL_Array::toArray($this->attributes->ordering . ' ', ' ');
        $is_named_array = array_key_first($rows) !== 0;

        $ordered = [];

        $count = 10000;

        foreach ($rows as $i => $row)
        {
            $count++;
            $key = $this->getOrderingKey($i, $row, $order);

            $ordered[$key . chr(0) . $count] = [$i, $row];
        }

        ksort($ordered, SORT_NATURAL);

        if ($direction === 'DESC')
        {
            krsort($ordered, SORT_NATURAL);
        }

        $rows = [];

        foreach ($ordered as $row)
        {
            if ($is_named_array)
            {
                $rows[$row[0]] = $row[1];
                continue;
            }

            $rows[] = $row[1];
        }
    }

    /**
     * @return DataGroup[]
     */
    public function getDataGroups()
    {
        $data_groups = [];

        if ($this->data_group instanceof DataGroup)
        {
            $data_groups[] = $this->data_group;
        }

        return $data_groups;
    }

    public function getOrderingKey($key, $value, $order)
    {
        $key = $this->getOrderingKeyMixed($key, $value, $order);
        $key = ! empty($key) ? $key : '000000';

        return RL_String::toString($key);
    }

    public function getOrderingKeyMixed($key, $value, $order)
    {
        if ($order === 'value')
        {
            return $key;
        }

        if (is_object($value))
        {
            return $value->{$order} ?? $key;
        }

        if ( ! is_array($value))
        {
            return $value;
        }

        [$order_key, $order_subkey] = RL_Array::toArray($order . ':', ':');

        if ( ! isset($value[$order_key]))
        {
            return $key;
        }

        if (is_object($value[$order_key]))
        {
            return $value[$order_key]->{$order_subkey} ?? $value[$order_key]->value ?? $key;
        }

        return $value[$order_key];
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        $rows = $this->data_group ? $this->data_group->getForeachData() : [];

        $html = $this->getOutputFromRows($rows);

        $opening_tags = RL_Html::removeEmptyTagPairs(
            $this->match_data['opening_tags_before_open']
            . $this->match_data['closing_tags_after_open']
        );

        $closing_tags = RL_Html::removeEmptyTagPairs(
            $this->match_data['opening_tags_before_close']
            . $this->match_data['closing_tags_after_close']
        );

        return $opening_tags . $html . $closing_tags;
    }

    /**
     * @return string
     */
    public function getRowOutput($row, $id, $data_tags, $numbers)
    {
        $content = $this->match_data['content'];

        $regex = Params::getRegex('ifstatement');

        if (RL_RegEx::match($regex, $content))
        {
            $this->if_statements->setValues($row, $numbers);
            $this->if_statements->replace($content);
        }

        foreach ($data_tags->getTags() as $data_tag)
        {
            $this->setDataTagValues($data_tag, $id, $row, $numbers);

            $data_tag->replace($content);
        }

        return $content;
    }

    /**
     * @param string $html
     */
    public function replace(&$html)
    {
        $output = $this->getOutput();

        $html = RL_String::replaceOnce($this->match_data[0], $output, $html);
    }

    public function setDataTagValues($data_tag, $id, $row, $numbers)
    {
        if ($data_tag->getDataGroup()->getClassName() === 'Numbers')
        {
            $data_tag->setValues([], $numbers);

            return;
        }

        if (
            $this->data_group->getClassName() === 'Field'
            && $data_tag->getDataGroup()->getClassName() === 'Field'
        )
        {
            $this->setDataTagValuesForField($data_tag, $id, $row);

            return;
        }

        if ($this->data_group->getClassName() === 'Tags')
        {
            $this->setDataTagValuesForTags($data_tag, $id, $row);

            return;
        }

        $data_tag->setValues([
            'value' => $id,
            'text'  => $row,
        ], null);
    }

    public function setDataTagValuesForField($data_tag, $id, $row)
    {
        $main_field = clone $this->data_group;
        $main_field = $main_field->getField();

        if ($main_field->type === 'subform')
        {
            $this->setDataTagValuesForFieldSubform($data_tag, $id, $row, $main_field);

            return;
        }

        if (in_array($main_field->type, ['checkboxes', 'radio'], true))
        {
            $main_field->rawvalue = RL_Array::toArray($main_field->rawvalue);
        }

        $main_field->value    = is_array($main_field->rawvalue) ? [$id] : $row;
        $main_field->rawvalue = is_array($main_field->rawvalue) ? [$id] : $id;

        $data_tag->getDataGroup()->setField($main_field);
    }

    /**
     *
     */
    public function setDataTagValuesForFieldList($data_tag, $id, $row, $main_field)
    {
        $main_field->value    = [$id];
        $main_field->rawvalue = [$id];

        $data_tag->getDataGroup()->setField($main_field);
    }

    /**
     *
     */
    public function setDataTagValuesForFieldSubform($data_tag, $id, $row, $main_field)
    {
        if ( ! empty($data_tag->getMatchData()['is_row']))
        {
            $single_row = (object) [];

            foreach ($row as $subfield)
            {
                $single_row->{$subfield->name} = $subfield->rawvalue;
            }

            if ($main_field->fieldparams->get('repeat'))
            {
                $single_row = (object) ['row0' => $single_row];
            }

            $main_field->value    = json_encode($single_row);
            $main_field->rawvalue = json_encode($single_row);

            $data_tag->getDataGroup()->setField($main_field);

            return;
        }

        $tag_key = $data_tag->getDataGroup()->getKey();

        if ( ! isset($row[$tag_key]))
        {
            return;
        }

        $field = $row[$tag_key];

        // set value to original raw value, so it gets rendered again later. This to make sure parameter overriding works.
        $field->value = $field->rawvalue;

        $data_tag->getDataGroup()->setField($field);
        //        $data_tag->getDataGroup()->setPrepareValues(false);
    }

    public function setDataTagValuesForTags($data_tag, $id, $row)
    {
        //        $row->value = $id;
        $values = RL_Array::addPrefixToKeys($row, 'tag.');

        $data_tag->setValues($values, null);
        $data_tag->getDataGroup()->setTag($row);
    }

    /**
     * @param array   $values
     * @param Numbers $numbers
     */
    public function setValues($values, Numbers $numbers)
    {
        $this->data_group->setValues($values, $numbers);
    }

    /**
     * @return array
     */
    private function getDataTags()
    {
        $regex = Params::getRegex('foreachdatatag');

        RL_RegEx::matchAll($regex, $this->match_data['content'], $matches);

        if (empty($matches))
        {
            return [];
        }

        $key_aliases = [
            'value' => ['rawvalue'],
            'text'  => ['title'],
        ];

        $data_tags = [];

        foreach ($matches as $match)
        {
            if ($match['is_row'])
            {
                $match['data_key'] = 'row';
            }

            $data_key = RL_String::toDashCase($match['data_key']);
            $data_key = RL_Object::replaceKeys($data_key, $key_aliases);

            $match['full_key'] = $data_key;
            $match['data_key'] = $data_key;

            if (
                $this->data_group->getClassName() === 'Field'
                && in_array($data_key, ['row', 'text', 'value'], true)
            )
            {
                $match['is_row']      = true;
                $match['full_key']    = $this->data_group->getKey();
                $match['data_key']    = $this->data_group->getKey();
                $match['data_subkey'] = in_array($data_key, ['text', 'value']) ? $data_key : '';

                $data_tags[] = new DataTag($match, 'Field', $this->database_name);
                continue;
            }

            //            if ($data_key === 'row' && $this->data_group->getClassName() === 'Field')
            //            {
            //                $data_tags[] = new DataTag($match, 'Field', $this->database_name);
            //                continue;
            //            }

            $data_tag = new DataTag($match, '', $this->database_name);

            if ($data_tag->getDataGroup() && $data_tag->getDataGroup()->getClassName() === 'Numbers')
            {
                $data_tags[] = $data_tag;
                continue;
            }

            if ($this->data_group->getClassName() === 'Tags')
            {
                $data_tags[] = new DataTag($match, 'Tag', $this->database_name);
                continue;
            }

            if (in_array($data_key, ['row', 'text', 'value'], true))
            {
                $data_tags[] = new DataTag($match, 'RowValue', $this->database_name);
                continue;
            }

            if ( ! $data_tag->getDataGroup())
            {
                continue;
            }

            $data_tags[] = $data_tag;
        }

        return $data_tags;
    }

    /**
     * @param array $rows
     *
     * @return string
     */
    private function getOutputFromRows(array $rows): string
    {
        if (empty($rows))
        {
            return '';
        }

        $total_no_limit = count($rows);

        $this->applyOrderingToRows($rows);
        $this->applyLimitToRows($rows);

        $total = count($rows);

        $data_tags = new DataTags('');
        $data_tags->setTags($this->getDataTags());

        $numbers = new Numbers(
            $total,
            $total_no_limit,
            $total_no_limit,
            0,
            0
        );

        $html      = [];
        $row_count = 0;

        foreach ($rows as $id => $row)
        {
            $row_count++;
            $numbers->setCount($row_count);

            $html[] = $this->getRowOutput($row, $id, RL_Object::clone($data_tags), $numbers);
        }

        return RL_Array::implode(
            $html,
            $this->attributes->separator ?? '',
            $this->attributes->last_separator ?? null
        );
    }
}
