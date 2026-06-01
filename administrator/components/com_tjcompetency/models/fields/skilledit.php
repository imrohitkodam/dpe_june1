<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

JFormHelper::loadFieldClass('list');

/**
 * Skill Edit field..
 *
 * @since  1.6
 */
class JFormFieldSkillEdit extends JFormFieldList
{
	/**
	 * To allow creation of new skills.
	 *
	 * @var    integer
	 * @since  3.6
	 */
	protected $allowAdd;

	/**
	 * Optional prefix for new skills.
	 *
	 * @var    string
	 * @since  3.9.11
	 */
	protected $customPrefix;

	/**
	 * A flexible skill list that respects access controls
	 *
	 * @var    string
	 * @since  1.6
	 */
	public $type = 'SkillEdit';

	/**
	 * The supported form contexts
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $supportedContext = array(
		'com_tjcompetency.skillcontentmaps',
		'com_tjcompetency.skillcontentusermaps',
		'com_tjreports.reports',
	);

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   3.2
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->allowAdd = isset($this->element['allowAdd']) ? $this->element['allowAdd'] : '';
			$this->customPrefix = (string) $this->element['customPrefix'];
		}

		return $return;
	}

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to get the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   3.6
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'allowAdd':
			case 'customPrefix':
				return $this->$name;
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to set the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.6
	 */
	public function __set($name, $value)
	{
		$value = (string) $value;

		switch ($name)
		{
			case 'allowAdd':
				$value = (string) $value;
				$this->$name = ($value === 'true' || $value === $name || $value === '1');
				break;
			case 'customPrefix':
				$this->$name = (string) $value;
				break;
			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to get a list of skills that respects access controls and can be used for
	 * either skill assignment or parent skill assignment in edit screens.
	 * Use the parent element to indicate that the field will be used for assigning parent skills.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   1.6
	 */
	public function getOptions()
	{
		$options = array();
		$state = $this->element['state'] ? explode(',', (string) $this->element['state']) : array(0, 1);
		$name = (string) $this->element['name'];

		// If the Field is dependant on other fields
		$app             = JFactory::getApplication();
		$jinput          = $app->input;
		$option          = $jinput->get('option');
		$view            = $jinput->get('view');
		$listViewContext = $option . '.' . $view;
		$frameworkId     = '';

		if (in_array($listViewContext, $this->supportedContext) && $this->element['dependant'] == '1')
		{
			$frameworkId = $app->getUserStateFromRequest($listViewContext . '.filter.framework_id', 'framework_id');

			if (empty($frameworkId))
			{
				return $options;
			}
		}

		// Load the skill options for a given extension.

		if ($this->element['parent'] || $jinput->get('option') == 'com_tjcompetency')
		{
			$oldCat = $jinput->get('id', 0);
			$oldParent = $this->form->getValue($name, 0);
		}
		else
			// For items the old category is the category they are in when opened or 0 if new.
		{
			$oldCat = $this->form->getValue($name, 0);
		}

		// Account for case that a submitted form has a multi-value skill id field (e.g. a filtering form), just use the first skill
		$oldCat = is_array($oldCat)
			? (int) reset($oldCat)
			: (int) $oldCat;

		$db = JFactory::getDbo();
		$user = JFactory::getUser();

		$query = $db->getQuery(true)
			->select('a.id AS value, CONCAT(a.title, " (", cf.title, ")") AS text, a.level, a.state, a.lft')
			->from('#__tjcompetency_skills AS a');

		$query->join('LEFT', $db->quoteName('#__tjcompetency_frameworks', 'cf') . ' ON (' . $db->quoteName('cf.id') . ' = ' . $db->quoteName('a.framework_id') . ')');

		if (is_array($frameworkId) && !empty($frameworkId))
		{
			$frameworkId = ArrayHelper::toInteger($frameworkId);
			$frameworkId = implode(',', $frameworkId);
			$query->where('cf.id IN (' . $frameworkId . ')');
		}

		// Filter on the state state
		$query->where('a.state IN (' . implode(',', ArrayHelper::toInteger($state)) . ')');

		$query->order('a.lft ASC');

		// If parent isn't explicitly stated but we are in com_tjcompetency assume we want parents
		if ($oldCat != 0 && ($this->element['parent'] == true || $jinput->get('option') == 'com_tjcompetency'))
		{
			// Prevent parenting to children of this item.
			// To rearrange parents and children move the children up, not the parents down.
			$query->join('LEFT', $db->quoteName('#__tjcompetency_skills') . ' AS p ON p.id = ' . (int) $oldCat)
				->where('NOT(a.lft >= p.lft AND a.rgt <= p.rgt)');

			$rowQuery = $db->getQuery(true);
			$rowQuery->select('a.id AS value, a.title AS text, a.level, a.parent_id')
				->from('#__tjcompetency_skills AS a')
				->where('a.id = ' . (int) $oldCat);
			$db->setQuery($rowQuery);
			$row = $db->loadObject();
		}

		$query->group('a.id,
				a.title'
		);

		// Get the options.
		$db->setQuery($query);

		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		// Pad the option text with spaces using depth level as a multiplier.
		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			// Translate ROOT
			if ($options[$i]->level == 0 && !$this->element['hide_root'])
			{
				$options[$i]->text = JText::_('JGLOBAL_ROOT_PARENT');
			}
			elseif ($options[$i]->level == 0 && $this->element['hide_root'])
			{
				unset($options[$i]);
				continue;
			}

			if ($options[$i]->state == 1)
			{
				$options[$i]->text = str_repeat('- ', !$options[$i]->level ? 0 : $options[$i]->level - 1) . $options[$i]->text;
			}
			else
			{
				$options[$i]->text = str_repeat('- ', !$options[$i]->level ? 0 : $options[$i]->level - 1) . '[' . $options[$i]->text . ']';
			}
		}

		if (($this->element['parent'] == true || $jinput->get('option') == 'com_tjcompetency')
			&& (isset($row) && !isset($options[0]))
			&& isset($this->element['show_root']))
		{
			if ($row->parent_id == '1')
			{
				$parent = new stdClass;
				$parent->text = JText::_('JGLOBAL_ROOT_PARENT');
				array_unshift($options, $parent);
			}

			array_unshift($options, JHtml::_('select.option', '0', JText::_('JGLOBAL_ROOT')));
		}

		// Merge any additional options in the XML definition.
		return array_merge(parent::getOptions(), $options);
	}

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.6
	 */
	protected function getInput()
	{
		$html = array();
		$class = array();
		$attr = '';

		// Initialize some field attributes.
		$class[] = !empty($this->class) ? $this->class : '';

		if ($class)
		{
			$attr .= 'class="' . implode(' ', $class) . '"';
		}

		$attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
		$attr .= $this->multiple ? ' multiple' : '';
		$attr .= $this->required ? ' required aria-required="true"' : '';
		$attr .= $this->autofocus ? ' autofocus' : '';

		// To avoid user's confusion, readonly="true" should imply disabled="true".
		if ((string) $this->readonly == '1'
			|| (string) $this->readonly == 'true'
			|| (string) $this->disabled == '1'
			|| (string) $this->disabled == 'true')
		{
			$attr .= ' disabled="disabled"';
		}

		// Initialize JavaScript field attributes.
		$attr .= $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

		// Get the field options.
		$options = (array) $this->getOptions();

		// Create a read-only list (no name) with hidden input(s) to store the value(s).
		if ((string) $this->readonly == '1' || (string) $this->readonly == 'true')
		{
			$html[] = JHtml::_('select.genericlist', $options, '', trim($attr), 'value', 'text', $this->value, $this->id);

			// E.g. form field type tag sends $this->value as array
			if ($this->multiple && is_array($this->value))
			{
				if (!count($this->value))
				{
					$this->value[] = '';
				}

				foreach ($this->value as $value)
				{
					$html[] = '<input type="hidden" name="' . $this->name . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"/>';
				}
			}
			else
			{
				$html[] = '<input type="hidden" name="' . $this->name . '" value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"/>';
			}
		}
		else
		{
			// Create a regular list.
			/*if (count($options) === 0)
			{
				// All Categories have been deleted, so we need a new skill (This will create on save if selected).
				$options[0]            = new stdClass;
				$options[0]->value     = '0';
				$options[0]->text      = '- Select Parent -';
			}*/

			$html[] = JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
		}

		return implode($html);
	}
}
