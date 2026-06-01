<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of courses
 *
 * @since  1.6
 */
class JFormFieldCourses extends JFormFieldList
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
		$db    = Factory::getDbo();
		$user  = Factory::getUser();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('c.id, c.title');
		$query->from($db->qn('#__tjlms_courses', 'c'));
		$query->join('LEFT', $db->qn('#__categories', 'cat') . ' ON (' . $db->qn('cat.id') . ' = ' . $db->qn('c.catid') . ')');
		$query->where($db->qn('c.state') . '= 1');
		$query->where($db->qn('cat.published') . '= 1');

		$nullDate	= $db->quote($db->getNullDate());
		$nowDate	= $db->quote(Factory::getDate()->toSql());
		$query->where('(c.start_date = ' . $nullDate . ' OR c.start_date <= ' . $nowDate . ')');

		if ($this->name == 'jform[course_id][]')
		{
			$query->where('c.type =1');
		}

		$query->order($db->escape('c.title ASC'));

		$db->setQuery($query);

		// Get all countries.
		$allcourses = $db->loadObjectList();

		$options = array();

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
