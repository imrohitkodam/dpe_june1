<?php
/**
 * @package    FrameworkOnFramework
 * @subpackage form
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

JFormHelper::loadFieldClass('tag');

/**
 * Form Field class for XTF0F
 * Tag Fields
 *
 * @package  FrameworkOnFramework
 * @since    2.1
 */
class XTF0FFormFieldTag extends JFormFieldTag implements XTF0FFormField
{
	protected $static;

	protected $repeatable;

	/** @var   XTF0FTable  The item being rendered in a repeatable form field */
	public $item;

	/** @var int A monotonically increasing number, denoting the row number in a repeatable view */
	public $rowid;

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.0
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'static':
				if (empty($this->static))
				{
					$this->static = $this->getStatic();
				}

				return $this->static;
				break;

			case 'repeatable':
				if (empty($this->repeatable))
				{
					$this->repeatable = $this->getRepeatable();
				}

				return $this->repeatable;
				break;

			default:
				return parent::__get($name);
		}
	}

	/**
	 * Method to get a list of tags
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.1
	 */
	protected function getOptions()
	{
		$options = array();

		$published = $this->element['published']? $this->element['published'] : array(0,1);

		$db		= XTF0FPlatform::getInstance()->getDbo();
		$query	= $db->getQuery(true)
			->select('DISTINCT a.id AS value, a.path, a.title AS text, a.level, a.published, a.lft')
			->from('#__tags AS a')
			->join('LEFT', $db->quoteName('#__tags') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt');

		if ($this->item instanceof XTF0FTable)
		{
			$item = $this->item;
		}
		else
		{
			$item = $this->form->getModel()->getItem();
		}

		if ($item instanceof XTF0FTable)
		{
			// Fake value for selected tags
			$keyfield = $item->getKeyName();
			$content_id  = $item->$keyfield;
			$type = $item->getContentType();

			$selected_query = $db->getQuery(true);
			$selected_query
				->select('tag_id')
				->from('#__contentitem_tag_map')
				->where('content_item_id = ' . (int) $content_id)
				->where('type_alias = ' . $db->quote($type));

			$db->setQuery($selected_query);

			$this->value = $db->loadColumn();
		}

		// Filter language
		if (!empty($this->element['language']))
		{
			$query->where('a.language = ' . $db->quote($this->element['language']));
		}

		$query->where($db->qn('a.lft') . ' > 0');

		// Filter to only load active items

		// Filter on the published state
		if (is_numeric($published))
		{
			$query->where('a.published = ' . (int) $published);
		}
		elseif (is_array($published))
		{
            XTF0FUtilsArray::toInteger($published);
			$query->where('a.published IN (' . implode(',', $published) . ')');
		}

		$query->order('a.lft ASC');

		// Get the options.
		$db->setQuery($query);

		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			return false;
		}

		// Prepare nested data
		if ($this->isNested())
		{
			$this->prepareOptionsNested($options);
		}
		else
		{
			$options = JHelperTags::convertPathsToNames($options);
		}

		return $options;
	}

	/**
	 * Get the rendering of this field type for static display, e.g. in a single
	 * item view (typically a "read" task).
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getStatic()
	{
		$class     = $this->element['class'] ? (string) $this->element['class'] : '';
		$translate = $this->element['translate'] ? (string) $this->element['translate'] : false;

		$options = $this->getOptions();

		$html = '';

		foreach ($options as $option) {

			$html .= '<span>';

			if ($translate == true)
			{
				$html .= JText::_($option->text);
			}
			else
			{
				$html .= $option->text;
			}

			$html .= '</span>';
		}

		return '<span id="' . $this->id . '" class="' . $class . '">' .
			$html .
			'</span>';
	}

	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.1
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
		$class     = $this->element['class'] ? (string) $this->element['class'] : '';
		$translate = $this->element['translate'] ? (string) $this->element['translate'] : false;

		$options = $this->getOptions();

		$html = '';

		foreach ($options as $option) {

			$html .= '<span>';

			if ($translate == true)
			{
				$html .= JText::_($option->text);
			}
			else
			{
				$html .= $option->text;
			}

			$html .= '</span>';
		}

		return '<span class="' . $this->id . ' ' . $class . '">' .
			$html .
			'</span>';
	}
}
