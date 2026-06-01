<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanDatabaseBehaviorFieldable extends KDatabaseBehaviorAbstract
{
    protected $_context;

    protected $_fields = array();

    /**
     * Constructor.
     *
     * @param  KObjectConfig $config A ObjectConfig object with configuration options
     * @throws InvalidArgumentException
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_context = $config->context;

        $this->addCommandCallback('after.insert', '_storeCustomFields');
        $this->addCommandCallback('after.update', '_storeCustomFields');
        $this->addCommandCallback('after.delete', '_cleanupCustomFields');
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  KObjectConfig $config A ObjectConfig object with configuration options
     * @return void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'context'  => 'com_docman.document',
            'priority' => self::PRIORITY_LOW // Make sure to run first the parameterizable behavior
        ));

        parent::_initialize($config);
    }

    /**
     * Normalize custom fields request data
     *
     * @param KDatabaseContext	$context A database context object
     * @return void
     */
    protected function _storeCustomFields(KDatabaseContext $context)
    {
        if ($context->data->hasProperty('jform') && isset($context->data->jform['com_fields']))
        {
            $data = $context->data->jform['com_fields'];

            $fields = $this->getFields();
            $model  = $this->_getFieldsModel();

            foreach ($fields as $field)
            {
                if (in_array($field->name, array_keys($data)))
                {
                    $value = isset($data[$field->name]) ? $data[$field->name] : null;

                    $model->setFieldValue($field->id, $this->id, $value);
                }
            }
        }
    }

    protected function _getFieldsModel($ignore_request = true)
    {
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/models', 'FieldsModel');

        return JModelLegacy::getInstance('Field', 'FieldsModel', array('ignore_request' => $ignore_request));
    }

    public function getFields($group_id = null)
    {
        $fields = false;

        $mixer = $this->getMixer();

        if ($mixer instanceof KModelEntityInterface)
        {
            if ($mixer->isNew() || !isset($this->_fields[$mixer->id]))
            {
                JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
            
                $fields = array('all' => FieldsHelper::getFields($this->_getContext(), $mixer->toArray(), true));

                foreach ($fields['all'] as $field)
                {
                    $key = sprintf('group:%s', trim($field->group_id));

                    if (!isset($this->_fields[$key])) $this->_fields[$key] = [];

                    $fields[$key][] = $field;
                }

                if (!$mixer->isNew()) {
                    $this->_fields[$mixer->id] = $fields;
                }
            }
            else $fields = $this->_fields[$mixer->id];
            
            if (isset($group_id)) // id:0 => not assigned to a group
            {
                $key = sprintf('group:%s', trim($group_id));

                if (isset($fields[$key])) {
                    $fields = $fields[$key];
                } else {
                    $fields = false;
                }
            }
            else $fields = $fields['all'];
        }

        return $fields;
    }

    public function getField($id)
    {
        $result = false;   

        if ($fields = $this->getFields())
        {
            // Determine the property to use for comparison
            if (is_numeric($id)) {
                $property = 'id';
            } else {
                $property = 'name';
            }

            foreach ($fields as $field)
            {
                if ($field->{$property} == $id) {
                    $result = $field;
                }

                if ($result) break;
            }
        }

        return $result;
    }

    protected function _cleanupCustomFields(KDatabaseContext $context)
    {
        $this->_getFieldsModel()->cleanupValues($this->_getContext(), $this->id);
    }

    protected function _getContext()
    {
        return $this->_context;
    }
}
