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



use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Fields as FieldsHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Layout;

class FieldGroup extends DataGroup
{
    protected static $cache_time       = (1 * 60);
    protected static $db_prefix        = 'fieldgroup';
    protected static $default_data_key = 'output';
    protected static $prefix           = 'fieldgroup';
    protected        $id               = '';

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

    /**
     * @return mixed
     */
    public function getOutputRaw()
    {
        $fields = $this->getFieldsByGroup($this->key, false);

        if (empty($fields))
        {
            return '';
        }

        $values = [];

        foreach ($fields as $field)
        {
            $values[] = RL_Array::implode($field->rawvalue, ',');
        }

        return RL_Array::implode($values, ',');
    }

    public function getRequiredQueryKeys()
    {
        return [
            'article.id',
            'article.catid',
            'article.language',
        ];
    }

    /**
     * @return string
     */
    public function getValue()
    {
        if (
            in_array($this->getAttribute('output'), ['value', 'values', 'raw'], true)
            || in_array($this->subkey, ['value', 'values', 'raw'], true)
        )
        {
            return $this->getOutputRaw();
        }

        $fields = $this->getFieldsByGroup($this->key);

        if (empty($fields))
        {
            return '';
        }

        $group          = FieldsHelper::getGroup($this->key);
        $key_underscore = RL_String::toUnderscoreCase($this->subkey);

        if ( ! isset($group->{$key_underscore})
            || is_object($group->{$key_underscore})
            || is_array($group->{$key_underscore})
        )
        {
            return $this->getOutputLayout($fields);
        }

        $value = $group->{$key_underscore};

        if (in_array($key_underscore, ['title', 'description']))
        {
            return JText::_($value);
        }

        return $value;
    }

    private function getFieldsByGroup($id = '', $prepare_values = true)
    {
        return FieldsHelper::getFieldsByGroup($id, $this->getArticleObject(), $prepare_values);
    }

    private function getOutputLayout($fields)
    {
        $layout_id = Layout::getId($this->getAttribute('layout'), 'render', 'fields');

        return Layout::render(
            $layout_id,
            [
                'fields'  => $fields,
                'context' => 'com_content.article',
                'item'    => $this->getArticleObject(),
            ],
            ['component' => 'com_fields', 'client' => 0]
        );
    }
}
