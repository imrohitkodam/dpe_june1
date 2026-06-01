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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;



use Joomla\Component\Fields\Administrator\Helper\FieldsHelper as JFieldsHelper;
use Joomla\Utilities\ArrayHelper;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Library\User as RL_User;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class Fields
{
    public static function getField($id, $article, $prepare_value = true)
    {
        $fields = self::getFields($article, $prepare_value);

        if (isset($fields[$id]))
        {
            return self::clone($fields[$id]);
        }

        foreach ($fields as $field)
        {
            if (
                (is_numeric($id) && (int) $field->id === (int) $id)
                || (RL_String::toDashCase($field->title) === $id)
                || (RL_String::toDashCase($field->label) === $id)
            )
            {
                return self::clone($field);
            }
        }

        return null;
    }

    public static function getFieldGroups()
    {
        $viewlevels = ArrayHelper::toInteger(RL_User::getAuthorisedViewLevels());

        $query = DB::getQuery();
        $query->select(['a.*']);
        $query->from(DB::quoteName('#__fields_groups', 'a'));
        $query->where('a.context = ' . DB::quote('com_content.article'));
        $query->whereIn('a.access', $viewlevels);

        return DB::getResults($query, 'objectList', true, 'id');
    }

    public static function getFieldType($id)
    {
        $fields = self::getFieldTypes();

        foreach ($fields as $field)
        {
            if (
                (is_numeric($id) && (int) $field->id === (int) $id)
                || (RL_String::toDashCase($field->name) === $id)
            )
            {
                return $field;
            }
        }

        return null;
    }

    public static function getFieldTypes()
    {
        $cache = new RL_Cache;

        if ($cache->exists())
        {
            return $cache->get();
        }

        $query = DB::getQuery()
            ->select('*')
            ->from(DB::quoteName('#__fields', 'a'))
            ->whereIn('a.state', [0, 1])
            ->where(DB::is('a.context', 'com_content.article'));

        $fields = DB::getResults($query, 'objectList');

        foreach ($fields as &$field)
        {
            $field->params      = json_decode($field->params ?: '{}');
            $field->fieldparams = json_decode($field->fieldparams ?: '{}');
        }

        return $cache->set($fields);
    }

    public static function getFields($article, $prepare_value = true)
    {
        $cache = new RL_Cache([__METHOD__, $article->id ?? 0, $prepare_value]);

        if ($cache->exists())
        {
            return $cache->get();
        }

        $fields = JFieldsHelper::getFields('com_content.article', $article, $prepare_value);

        if (empty($fields))
        {
            return $cache->set([]);
        }

        $article_fields = [];

        foreach ($fields as $field)
        {
            // flatten params to main field object
            $field           = RL_Object::merge($field, $field->params->toObject());
            $field->value    = $field->value ?? null;
            $field->rawvalue = $field->rawvalue ?? null;

            if ($field->fieldparams->get('multiple') === 1)
            {
                $field->value    = is_array($field->value) ? $field->value : [$field->value];
                $field->rawvalue = is_array($field->rawvalue) ? $field->rawvalue : [$field->rawvalue];
            }

            $article_fields[RL_String::toDashCase($field->name)] = $field;
        }

        return $cache->set($article_fields);
    }

    public static function getFieldsByGroup($group_id, $article, $prepare_value = true)
    {
        if ( ! is_numeric($group_id))
        {
            $group_id = self::getGroupIdByTitle($group_id);
        }

        if ( ! $group_id)
        {
            return [];
        }

        $group_id = (int) $group_id;

        $fields = self::getFields($article, $prepare_value);

        $render_fields = [];

        foreach ($fields as $field)
        {
            $field->value = RL_Array::implode($field->value, ',');

            if ($field->group_id === $group_id)
            {
                $render_fields[] = $field;
            }
        }

        return $render_fields;
    }

    public static function getGroup($group_id)
    {
        if ( ! is_numeric($group_id))
        {
            $group_id = self::getGroupIdByTitle($group_id);
        }

        $groups = self::getFieldGroups();

        return $groups[$group_id] ?? null;
    }

    public static function getGroupIdByTitle($group_title)
    {
        $group_title = RL_String::toDashCase($group_title);
        $groups      = self::getFieldGroups();

        foreach ($groups as $group)
        {
            if (RL_String::toDashCase($group->title) === $group_title)
            {
                return $group->id;
            }
        }

        return 0;
    }

    private static function clone($field): object
    {
        $cloned = RL_Object::clone($field);

        if (empty($field->subform_rows))
        {
            return $cloned;
        }

        foreach ($field->subform_rows as $i => $rows)
        {
            foreach ($rows as $j => $row)
            {
                $cloned->subform_rows[$i][$j] = RL_Object::clone($row);
            }
        }

        return $cloned;
    }
}
