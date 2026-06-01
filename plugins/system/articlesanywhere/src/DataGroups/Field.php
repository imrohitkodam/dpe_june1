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



use DateTimeZone;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use Joomla\Component\Fields\Administrator\Plugin\FieldsListPlugin as JFieldsListPlugin;
use Joomla\Registry\Registry as JRegistry;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\File as RL_File;
use RegularLabs\Library\Parameters as RL_Parameters;
use RegularLabs\Library\Protect as RL_Protect;
use RegularLabs\Library\RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Library\User as RL_User;
use RegularLabs\Plugin\System\ArticlesAnywhere\Database;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Date;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Fields as FieldsHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Image;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Layout;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\ValuesObject;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Field extends DataGroup
{
    protected static $cache_time            = (1 * 60);
    protected static $data_subkey_aliases   = [
        'values'    => 'value',
        'raw'       => 'value',
        'rawvalue'  => 'value',
        'rawvalues' => 'value',
        'texts'     => 'text',
    ];
    protected static $db_prefix             = 'field';
    protected static $default_data_subkey   = 'layout';
    protected static $default_filter_subkey = 'text';
    protected static $main_table            = 'fields';
    protected static $prefix                = 'field';
    private          $field;

    //    private $prepare_values = true;

    public function __construct(
        $key,
        $subkey = '',
        $attributes = null,
        $article_selector = '',
        $database_name = ''
    )
    {
        parent::__construct($key, $subkey, $attributes, $article_selector, $database_name);

        $this->attributes->is_custom_field = true;
    }

    /**
     * @return object
     */
    public function getArticleObject()
    {
        return (object) [
            'id'       => $this->get('article.id'),
            'catid'    => $this->get('article.catid'),
            'language' => $this->get('article.language'),
        ];
    }

    //    public function setPrepareValues($value = true)
    //    {
    //        $this->prepare_values = $value;
    //    }

    public function getDatabaseFieldKey($key = '', $case = 'dash')
    {
        $key = RL_String::toCase($key ?: $this->key, $case);

        return 'field-' . $key;
    }

    public function getDatabaseKey($key = '', $add_prefix = true, $case = 'dash')
    {
        $key = RL_String::toCase($key, $case);

        return $this->getDatabaseValueKey($key) . '.value';
    }

    public function getDatabaseValueKey($key = '', $case = 'dash')
    {
        $key = RL_String::toCase($key ?: $this->key, $case);

        return 'field_value-' . $key;
    }

    public function getField()
    {
        if ( ! is_null($this->field))
        {
            return $this->field;
        }

        if ( ! $this->parent_key)
        {
            $this->field = FieldsHelper::getField($this->key, $this->getArticleObject(), false);

            if (empty($this->field))
            {
                return $this->field;
            }

            if (is_array($this->field->value ?? null))
            {
                $this->field->value = RL_Array::removeEmpty($this->field->value);
            }

            if (empty($this->field->rawvalue))
            {
                return $this->field;
            }

            if (is_array($this->field->rawvalue))
            {
                $this->field->rawvalue = RL_Array::removeEmpty($this->field->rawvalue);

                return $this->field;
            }

            $is_date     = $this->field->type === 'calendar';
            $ignore_time = $is_date && ! $this->field->fieldparams->get('showtime', 1);

            if ($ignore_time)
            {
                $datetime = RegEx::replace('\s[0-9][0-9]:[0-9][0-9]:[0-9][0-9]$', '00:00:00', $this->field->rawvalue);
                $date     = JFactory::getDate($datetime, RL_User::getTimezone()->getName());
                $date->setTimezone(new DateTimeZone('UTC'));

                $this->field->rawvalue = $date->format('Y-m-d H:i:s');
            }

            return $this->field;
        }

        $parent_field = FieldsHelper::getField($this->parent_key, $this->getArticleObject(), false);
        $subfield     = FieldsHelper::getField($this->key, $this->getArticleObject(), false);
        $values       = json_decode($parent_field->rawvalue, true);

        $row                = $this->row;
        $row_values         = $values['row' . $row];
        $subfield->rawvalue = $subfield->value = $row_values['field' . $subfield->id] ?? '';

        $this->field = $subfield;

        return $this->field;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function getFieldType()
    {
        return FieldsHelper::getFieldType($this->key);
    }

    public function getFieldValue($field, $item = null)
    {
        $app = JFactory::getApplication();

        JPluginHelper::importPlugin('fields');

        $options = $field->fieldparams->get('options');

        if ( ! empty($this->getAttribute('calc')) && ! empty($options))
        {
            foreach ($options as &$option)
            {
                $option->name = DataHelper::calculate($option->name, $this->getAttribute('calc'));
            }

            $field->fieldparams->set('options', $options);
        }

        /*
         * On before field prepare
         * Event allow plugins to modify the output of the field before it is prepared
         */
        $app->triggerEvent('onCustomFieldsBeforePrepareField', [
            'com_content.article', $item, &$field,
        ]);

        if ( ! empty($this->getAttribute('calc')) && empty($options))
        {
            $field->value = DataHelper::calculate($field->value, $this->getAttribute('calc'));
        }

        if ($this->attributeExists('separator') && ! $field->fieldparams->exists('separator'))
        {
            $value = $this->getOutputUsingSeparator(
                $field,
                $this->getAttribute('separator'),
                $this->getAttribute('last-separator')
            );

            $app->triggerEvent('onCustomFieldsAfterPrepareField', [
                'com_content.article', $item, $field, &$value,
            ]);

            return $value;
        }

        // Gathering the value for the field
        $value = $app->triggerEvent('onCustomFieldsPrepareField', [
            'com_content.article', $item, &$field,
        ]);

        if (is_array($value))
        {
            $value = array_filter($value);
            $value = implode(' ', $value);
        }

        /*
         * On after field render
         * Event allows plugins to modify the output of the prepared field
         */
        $app->triggerEvent('onCustomFieldsAfterPrepareField', [
            'com_content.article', $item, $field, &$value,
        ]);

        return $value;
    }

    /**
     * @return array
     */
    public function getForeachData()
    {
        $field = $this->getField();

        if (empty($field))
        {
            return [];
        }

        if ($field->type === 'subform')
        {
            $field = $this->getPreparedField();

            return RL_Array::toArray($field->subform_rows ?? []);
        }

        if ( ! empty($field->rawvalue) && is_string($field->rawvalue) && $field->rawvalue[0] === '{')
        {
            return json_decode($field->rawvalue, true);
        }

        return RL_Array::toArray($this->getOutputValues($field));
    }

    /**
     * @return array [table => condition]
     */
    public function getJoins()
    {
        return [];
    }

    public function getJoinsForFilters()
    {
        $all_fields = self::getFieldNames($this->database_name);

        $field_name = $this->key;

        if ( ! in_array($field_name, $all_fields, true))
        {
            $field_name = RL_String::toUnderscoreCase($field_name);
        }

        // fields and fields_values aliases
        $field = $this->getDatabaseFieldKey();
        $value = $this->getDatabaseValueKey();

        return [
            DB::quoteName('#__fields', $field)
            => DB::quoteName($field . '.name') . ' = ' . DB::quote($field_name)
                . ' AND ' . DB::is($field . '.state', 1)
                . ' AND ' . DB::quoteName($field . '.context') . ' = ' . DB::quote('com_content.article'),
            DB::quoteName('#__fields_values', $value)
            => DB::quoteName($value . '.field_id') . ' = ' . DB::quoteName($field . '.id')
                . ' AND ' . DB::quoteName($value . '.item_id') . ' = article.id',
        ];
    }

    public function getOutputImage($field, $key = null)
    {
        $field_data = $field->rawvalue;

        // Fix for only image path values
        if (is_string($field_data) && ($field_data[0] ?? '') !== '{')
        {
            $field_data = '{"imagefile":"' . $field_data . '"}';
        }

        if (is_string($field_data))
        {
            $field_data = json_decode($field_data);
        }

        $field_data = (object) $field_data;

        $image_data = (object) [
            'src'   => $field_data->imagefile ?? '',
            'alt'   => $field_data->alt_text ?? '',
            'class' => $field->fieldparams->get('image_class'),
        ];

        return Image::getOutputByKey($key ?? $this->subkey, $image_data, $this->attributes, 'custom_fields');
    }

    public function getOutputOther($field)
    {
        if ($this->isImage($field))
        {
            return $this->getOutputImage($field);
        }

        $key_underscore = RL_String::toUnderscoreCase($this->subkey);

        if ( ! isset($field->{$key_underscore}))
        {
            return '';
        }

        $value = $field->{$key_underscore};

        if (is_object($value) || is_array($value))
        {
            return '';
        }

        if ($key_underscore === 'label')
        {
            return JText::_($value);
        }

        return $value;
    }

    /**
     * @return mixed
     */
    public function getOutputRaw()
    {
        $field = $this->getField();

        if (empty($field))
        {
            return $this->values['rawvalue'] ?? '';
        }

        if ($field->type === 'subform')
        {
            return $this->getOutputValuesFromSubform($field);
        }

        if ($this->isImage($field))
        {
            return $this->getOutputImage($field, 'url');
        }

        return $field->rawvalue;
    }

    /**
     * @return mixed
     */
    public function getOutputText($field)
    {
        return $this->getOutputUsingSeparator(
            $field,
            $this->getAttribute('separator', ', '),
            $this->getAttribute('last-separator')
        );
    }

    public function getOutputUsingSeparator($field, $separator, $last_separator = null)
    {
        $texts = $this->getOutputValues($field);

        if ( ! is_array($texts))
        {
            return $texts;
        }

        return RL_Array::implode(
            $texts,
            $separator,
            $last_separator
        );
    }

    public function getOutputValues($field)
    {
        if ($field->type === 'subform')
        {
            return $this->getOutputValuesFromSubform($field);
        }

        $plugin = JFactory::getApplication()->bootPlugin($field->type, 'fields');

        if ($plugin instanceof JFieldsListPlugin)
        {
            return $this->getOutputValuesFromList($field, $plugin);
        }

        $this->setFieldParams($field);

        return $field->value ?? '';
    }

    public function getOutputValuesFromList($field, $plugin)
    {
        $field_value = (array) $field->value;
        $options     = $plugin->getOptionsFromField($field);

        $values = [];

        foreach ($options as $value => $name)
        {
            if (in_array((string) $value, $field_value, true))
            {
                $values[$value] = JText::_($name);
            }
        }

        return $values;
    }

    public function getOutputValuesFromSubform($field)
    {
        $row_values = $field->value;

        if ( ! is_array($row_values))
        {
            $row_values = (array) json_decode($row_values);
        }

        if (empty($row_values))
        {
            return [];
        }

        if ($field->fieldparams->get('repeat'))
        {
            $row_values = array_shift($row_values);
        }

        $values = [];

        foreach ($row_values as $value)
        {
            $values[] = RL_Array::implode($value, ',');
        }

        return $values;
    }

    public function getPreparedField()
    {
        return FieldsHelper::getField($this->key, $this->getArticleObject(), true);
    }

    public function getQueryKeys()
    {
        return [
            'field-' . $this->key,
        ];
    }

    public function getRequiredQueryKeys()
    {
        return [
            'article.id',
            'article.catid',
            'article.language',
        ];
    }

    public function getSelects()
    {
        return $this->getSelectsByKeys($this->getRequiredQueryKeys());
    }

    /**
     * @return string
     */
    public function getValue()
    {
        $field = $this->getField();

        if (empty($field))
        {
            return $this->values[$this->subkey] ?? $this->values['value'] ?? '';
        }

        $this->setSubkeyByField($field);

        switch ($this->subkey)
        {
            case 'value':
                $raw_value     = $this->getOutputRaw();
                $has_separator = $this->getAttribute('separator');

                if (is_array($raw_value) || is_array($field->value) || $has_separator)
                {
                    return RL_Array::implode(
                        $this->getOutputRaw(),
                        $this->getAttribute('separator', ','),
                        $this->getAttribute('last-separator')
                    );
                }

                return $raw_value;

            case 'text':
                return $this->getOutputText($field);

            case 'layout':
                return $this->getOutputLayout($field);

            default:
                return $this->getOutputOther($field);
        }
    }

    /**
     * @param array|object|string $values
     * @param bool                $exclude
     *
     * @return string
     */
    public function getWhere($values, $glue = 'OR')
    {
        if ( ! in_array($this->key, self::getFieldNames($this->database_name), true))
        {
            return '';
        }

        if ($this->filter_subkey !== 'text')
        {
            return parent::getWhere($values, $glue);
        }

        $field = self::getFieldFromDatabase($this->key, $this->database_name);

        if (empty($field))
        {
            return '';
        }

        $is_date     = $field->type === 'calendar';
        $ignore_time = $is_date && ! $field->fieldparams->get('showtime', 1);

        $database      = new Database($this->database_name);
        $positives     = [];
        $negatives     = [];
        $has_positives = false;
        $has_negatives = false;

        foreach ($values as $value)
        {
            $value_glue = (string) $glue;

            if ($value instanceof ValuesObject)
            {
                $value_glue = $value->getGlue();
                $value      = $value->getValues();
            }

            if ( ! is_string($value) && ! is_array($value))
            {
                continue;
            }

            if (is_string($value))
            {
                $value = [$value];
            }

            $value = array_filter($value);

            if (empty($value))
            {
                continue;
            }

            if (count($value) == 1 && $value[0] == '=')
            {
                $value = ['!*'];
            }

            $operator    = DB::getOperator($value);
            $is_negative = in_array($operator, ['!=', '<>'], true);

            if ($is_negative)
            {
                $value = DB::removeOperator($value);
            }

            // Get all articles where the field contains the value
            $query = DB::getQuery()
                ->select('value.item_id')
                ->from(DB::quoteName('#__fields_values', 'value'))
                ->join('INNER', DB::quoteName('#__fields', 'field') . ' ON ' . DB::quoteName('field.id') . ' = ' . DB::quoteName('value.field_id'))
                ->where(DB::is('field.name', $this->key))
                ->where(DB::is('field.state', 1))
                ->where(DB::is('field.context', 'com_content.article'));

            $wheres = [];

            foreach ($value as $sub_value)
            {
                if ($ignore_time && str_contains($sub_value, '-'))
                {
                    $wheres[] = DB::is('DATE(value.value)', $sub_value, [], false);
                    continue;
                }

                $wheres[] = DB::is('value.value', $sub_value);
            }

            $query->where(DB::combine($wheres, $value_glue));

            $article_ids = $database->getResults($query);

            if ($is_negative)
            {
                $negatives     = $glue == 'AND'
                    ? array_diff($negatives, $article_ids)
                    : [...$negatives, ...$article_ids];
                $has_negatives = true;
                continue;
            }

            $positives     = $glue == 'AND'
                ? array_diff($positives, $article_ids)
                : [...$positives, ...$article_ids];
            $has_positives = true;
        }

        $wheres = [];

        if ($has_negatives)
        {
            $wheres[] = DB::notIn('article.id', array_unique($negatives));
        }

        if ($has_positives)
        {
            $wheres[] = DB::in('article.id', array_unique($positives));
        }

        if (empty($wheres))
        {
            return DB::in('article.id', []);
        }

        return DB::combine($wheres, 'AND');
    }

    public function isImage($field)
    {
        if ($field->type !== 'media')
        {
            return false;
        }

        if (
            in_array($this->subkey, [
                'tag',
                'width',
                'height',
                'src',
                'url',
                'alt',
                'thumb',
                'thumb-url',
            ], true)
        )
        {
            return true;
        }

        if (isset($this->attributes->width))
        {
            return true;
        }

        if (is_array($field->rawvalue))
        {
            return true;
        }

        if ( ! is_string($field->rawvalue))
        {
            return false;
        }

        if (str_contains($field->rawvalue, 'imagefile'))
        {
            return true;
        }

        return RL_File::isImage($field->rawvalue, 'images/');
    }

    protected static function getFieldFromDatabase(string $name, $database_name = ''): object
    {
        $cache = new RL_Cache([__METHOD__, $database_name]);

        if ($cache->exists())
        {
            return $cache->get();
        }

        $database = new Database($database_name);

        $query = DB::getQuery()
            ->select('*')
            ->from(DB::quoteName('#__fields', 'field'))
            ->where('field.context = ' . DB::quote('com_content.article'))
            ->where(DB::is('field.name', $name));

        $field = $database->getResults($query, 'object');

        if (empty($field))
        {
            return $cache->set($field);
        }

        $field->params      = new JRegistry($field->params);
        $field->fieldparams = new JRegistry($field->fieldparams);

        return $cache->set($field);
    }

    protected static function getFieldNames($database_name = '')
    {
        $cache = new RL_Cache([__METHOD__, $database_name]);

        if ($cache->exists())
        {
            return $cache->get();
        }

        $database = new Database($database_name);

        $query = DB::getQuery()
            ->select('field.name')
            ->from(DB::quoteName('#__fields', 'field'))
            ->where('field.context = ' . DB::quote('com_content.article'));

        return $cache->set($database->getResults($query));
    }

    protected static function getPossiblePlainKeys($database_name = '')
    {
        return self::getFieldNames($database_name);
    }

    private static function getParamKeys()
    {
        return [
            'hint',
            'class',
            'label_class',
            'show_on',
            'render_class',
            'showlabel',
            'label_render_class',
            'display',
            'prefix',
            'suffix',
            'layout',
            'display_readonly',
        ];
    }

    private function getOutputLayout($field)
    {
        if ($field->type === 'calendar' && $this->getAttribute('format'))
        {
            return Date::toString($field->rawvalue, $this->attributes, true);
        }

        if ($field->type === 'media')
        {
            if (is_array($field->rawvalue))
            {
                // This seems to happen when this is a sub-row
                //                $this->prepare_values = true;
                $field->value = $field->rawvalue = json_encode($field->rawvalue);
            }

            // Fix for only image path values
            if (is_string($field->value) && ($field->value[0] ?? '') !== '{')
            {
                $field->value = '{"imagefile":"' . $field->value . '"}';
            }

            $this->setMediaFieldAltText($field);
        }

        $this->setFieldParams($field);

        $layout    = $this->getAttribute('layout', true);
        $layout_id = Layout::getId($layout, ! empty($field->layout) ? $field->layout : 'render', 'field');

        $displayData = [
            'field' => $field,
            'item'  => $this->getArticleObject(),
        ];

        $output = Layout::render(
            $layout_id,
            $displayData,
            ['component' => 'com_fields', 'client' => 0]
        );

        return trim($output);
    }

    private function setFieldParams(&$field)
    {
        // Set show label off by default
        $original_showlabel = $field->params->get('showlabel');

        // Set default param values from xml file
        $field->fieldparams = new JRegistry(RL_Parameters::getObjectFromData(
            $field->fieldparams,
            JPATH_PLUGINS . '/fields/' . $field->type . '/params/' . $field->type . '.xml'
        ));

        $field->params->set('showlabel', false);

        if (isset($this->attributes->label) && ! isset($this->attributes->showlabel))
        {
            $this->attributes->showlabel = true;
        }

        foreach ($this->attributes as $key => $value)
        {
            if ($key === 'layout' && ! $field->fieldparams->exists('layout'))
            {
                continue;
            }

            if ($key === 'showlabel' && $value === 'inherit')
            {
                $field->params->set('showlabel', $original_showlabel);
                continue;
            }

            if ($key === 'custom_html')
            {
                if ( ! $field->fieldparams->exists('custom_html'))
                {
                    continue;
                }

                if ($field->fieldparams->exists('layout'))
                {
                    $field->fieldparams->set('layout', 'custom_html');
                }

                RL_Protect::unprotect($value);
                $value = RL_String::html_entity_decoder($value);

                $field->fieldparams->set('custom_html', $value);
                continue;
            }

            if ($key === 'separator')
            {
                if ( ! $field->fieldparams->exists('separator'))
                {
                    continue;
                }

                if ($field->fieldparams->exists('use_separator'))
                {
                    $field->fieldparams->set('use_separator', true);
                }

                $field->fieldparams->set('separator', $value);
                continue;
            }

            if ($key === 'last_separator')
            {
                if ( ! $field->fieldparams->exists('last_separator'))
                {
                    continue;
                }

                if ($field->fieldparams->exists('use_separator'))
                {
                    $field->fieldparams->set('use_separator', true);
                }

                if ($field->fieldparams->exists('use_last_separator'))
                {
                    $field->fieldparams->set('use_last_separator', true);
                }

                $field->fieldparams->set('last_separator', $value);
                continue;
            }

            if (isset($field->{$key}))
            {
                $field->{$key} = $value;
            }

            if (in_array($key, $this->getParamKeys(), true))
            {
                $field->params->set($key, $value);
                continue;
            }

            $field->fieldparams->set($key, $value);
        }

        $article = $this->getArticleObject();

        $field->value = $this->getFieldValue($field, $article);
    }

    private function setMediaFieldAltText(&$field)
    {
        $value = json_decode($field->value);
        $value = empty($value) ? (object) [] : $value;

        if ( ! empty($value->alt_text))
        {
            return;
        }

        $image_data = json_decode($field->value);
        Image::setAltAndTitle('custom_fields', $image_data, JText::_($field->label));

        if (empty($image_data->alt))
        {
            return;
        }

        $value->alt_text = $image_data->alt;

        $field->value = json_encode($value);
    }

    /**
     * @return string
     */
    private function setSubkeyByField($field)
    {
        if ($this->subkey !== 'layout')
        {
            return;
        }

        $has_layout = $this->getAttribute('layout', ! $this->isImage($field));

        if ($has_layout)
        {
            $this->subkey = 'layout';

            return;
        }

        if ( ! $this->isImage($field))
        {
            $this->subkey = 'text';

            return;
        }

        $params = Params::get();

        if (empty($attributes->resize) && $params->resize_images == 1)
        {
            $this->subkey = 'tag';

            return;
        }

        if (
            ! empty($this->attributes->width)
            || ! empty($this->attributes->height)
            || ! empty($this->attributes->class)
            || ! empty($this->attributes->style)
            || isset($this->attributes->resize)
        )
        {
            $this->subkey = 'tag';

            return;
        }
    }
}
