<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanTemplateHelperFields extends ComKoowaTemplateHelperBehavior
{
    protected static $_tabs = array();
    protected static $_contents = array();

    /**
     * Initialize custom fields
     *
     * @param array $config
     * @return string
     */
    public function init($config = array())
    {
        $config = new KObjectConfigJson($config);
        
        $signature = 'customfields-entity';
        
        if (!self::isLoaded($signature))
        {
            $model = new ComDocmanJoomlaModelDocument();

            $entity = $config->entity;

            $form = $model->getForm();

            $fieldsets = $form->getFieldsets();

            foreach($fieldsets as $name => $fieldset)
            {
                // Store the tab headers
                static::$_tabs[$name] = JText::sprintf($fieldset->label);

                // Store the tab content
                $form_fields = $form->getFieldset($name);

                foreach ($form_fields as $form_field)
                {
                    $field = $entity->getField($form_field->fieldname);

                    if (isset($field->rawvalue)) $form_field->setValue($field->rawvalue);

                    // Render the custom fields ourselves to set possible unsaved values
                    static::$_contents[$name] = isset(static::$_contents[$name]) ? static::$_contents[$name] . $form_field->renderField() : $form_field->renderField();
                }

                // Render custom fields using the helper
                // static::$_contents[$name] = FieldsHelper::render('content.article', 'joomla.edit.fieldset', $displayData);
            }

            self::setLoaded($signature, true);
        }
    }

    public function render($config = [])
    {
        $config = new KObjectConfig($config);

        $config->append(['id' => []]);

        $entity = $config->entity;

        $html = '';
        
        if ($group = $config->group)
        {
            if ($fields = $entity->getFields($group))
            {
                foreach ($fields as $field) {
                    $html .= $this->_render($entity, $field->id);
                }
            }
        }
        $html .= $this->_render($entity, $config->id);

        return $html;
    }

    protected function _render(KModelEntityInterface $entity, $id)
    {
        $ids = (array) KObjectConfig::unbox($id);

        $html = '';

        foreach ($ids as $id)
        {
            if ($field = $entity->getField($id))
            {
                $html .= '<p><span class="field-label">';
                $html .= $field->name;
                $html .= ':</span>';

                $html .= '<span class="field-value">';
                $html .= $field->value;
                $html .= '</span></p>';
            }
        }

        return $html;
    }

    /**
     * Display tab header(s)
     * @return array [name => label]
     */
    public static function tabs()
    {
        return static::$_tabs;
    }

    /**
     * Display tab content(s)
     */
    public static function contents()
    {
        return static::$_contents;
    }
}
