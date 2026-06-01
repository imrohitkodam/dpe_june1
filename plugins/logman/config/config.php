<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Config LOGman Plugin.
 *
 * Provides event handlers for dealing with com_config events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanConfig extends ComLogmanPluginJoomla
{
    protected function _getComponentObjectData($data, $event)
    {
        return array('name' => $data->element, 'id' => $data->extension_id);
    }

    public function onApplicationAfterSave($config)
    {
        $current = JFactory::getConfig();

        $changes = $this->_getConfigChanges($config->toArray(), $current->toArray());

        if (!empty($changes))
        {
            $data = array(
                'object' => array(
                    'package' => 'config',
                    'type'     => 'configuration',
                    'metadata' => array('changes' => $changes)
                ),
                'result' => 'changed',
                'verb'   => 'change'
            );

            $this->logActivity($data);
        }
    }

    protected function _getConfigChanges($new, $old)
    {
        $changes = array();

        // Calculate setting differences to keep track of configuration changes
        foreach ($new as $key => $new_value)
        {
            if (isset($old[$key]))
            {
                if (is_scalar($old[$key]) && is_scalar($new_value))
                {
                    $old_value = $old[$key];


                    // Trim string values to avoid logging fake config changes (empty string values with different length)

                    if (is_string($old_value)) $old_value = trim($old_value);
                    if (is_string($new_value)) $new_value = trim($new_value);

                    if ($old_value != $new_value) {
                        $changes[$key] = array( 'values' => array('old' => array('value' => $old[$key]), 'new' => array('value' => $new_value)));
                    }
                }

                if (is_array($new_value)) {
                    $changes[$key] = $this->_getConfigChanges($new_value, $old[$key]);
                }
            }
            else $changes[$key] = array('values' => array('old' => array('value' => null), 'new' => array('value' => $new_value)));
        }

        if (version_compare(JVERSION, '4', '<')) {
            $form = sprintf('%s/components/com_config/model/form/application.xml', JPATH_ADMINISTRATOR);
        } else {
            $form = sprintf('%s/components/com_config/forms/application.xml', JPATH_ADMINISTRATOR);
        }


        if (!empty($changes) && is_file($form))
        {
            $xml = simplexml_load_file($form);

            foreach ($xml->children()->fieldset as $fieldset)
            {
                foreach ($fieldset->children() as $field)
                {
                    $name = (string) $field['name'];

                    if (in_array($name, array_keys($changes)))
                    {
                        $changes[$name]['type']  = (string) $field['type'];
                        $changes[$name]['label'] = (string) $field['label'];

                        switch($changes[$name]['type'])
                        {
                            case 'list':
                                // Store value label from list
                                foreach ($field->children() as $option)
                                {
                                    if (isset($changes[$name]['values'])) {
                                        foreach($changes[$name]['values'] as $key => $data)
                                        {
                                            if ($data['value'] == (string) $option['value']) {
                                                $changes[$name]['values'][$key]['label'] = (string) $option;
                                            }
                                        }
                                    }

                                }
                                break;
                            case 'accesslevel':
                                // Store the label for the corresponding access level
                                $table = JTable::getInstance('viewlevel');

                                foreach($changes[$name]['values'] as $key => $data)
                                {
                                    if ($table->load($data['value'])) {
                                        $changes[$name]['values'][$key]['label'] = $table->title;
                                    }
                                }
                                break;
                            case 'radio':
                                // Store radio input value labels
                                foreach($changes[$name]['values'] as $key => $data)
                                {
                                    if ($data['value'] == 1) {
                                        $changes[$name]['values'][$key]['label'] = 'JYES';
                                    } else if ($data['value'] == 0) {
                                        $changes[$name]['values'][$key]['label'] = 'JNO';
                                    }
                                }
                                break;
                            case 'plugins':
                                $changes[$name]['folder'] = (string) $field['folder'];
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        }

        return $changes;
    }
}