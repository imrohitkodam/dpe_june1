<?php
/**
 * @version    SVN: <svn_id>
 * @package    Quick2cart
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of courses
 *
 * @since  1.6
 */
class JFormFieldCourses extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'courses';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	2.2
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$db = Factory::getDbo();
		$jinput = Factory::getApplication()->input;
		$user = Factory::getUser();
		$viewname = $jinput->get('view', '', 'STR');
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('c.id, c.title');
		$query->from('`#__tjlms_courses` AS c');
		$query->where('c.state = 1');

		if ($this->name == 'jform[course_id][]')
		{
			$query->where('c.created_by = "' . $user->id . '" AND c.type = 1');
		}

		$query->order($db->escape('c.title ASC'));

		$db->setQuery($query);

		// Get all countries.
		$allcourses = $db->loadObjectList();

		$options = array();

		if ($this->name !== 'jform[course_id][]')
		{
			$options[] = HTMLHelper::_('select.option', "", Text::_('COM_TJLMS_COUPON_COURSE'));
		}

		foreach ($allcourses as $c)
		{
			$options[] = HTMLHelper::_('select.option', $c->id, $c->title);
		}

		if (!$this->loadExternally)
		{
			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);
		}

		return $options;
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   2.2
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
