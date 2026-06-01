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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

/**
 * Supports an HTML select list of course related subscription
 *
 * @since  1.6
 */
class JFormFieldSubscription extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'subscription';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.6
	 */
	protected function getInput()
	{
		$optionsList = self::fetchElement($this->name, $this->value, $this->element, $this->options['control']);

		$selected = explode(',',  $this->value);

		$html = $options = array();
		$html[] = HTMLHelper::_('select.groupedlist',  $optionsList, $this->name,
			array('id' => $this->id, 'group.id' => 'id', 'list.attr' => 'multiple=true', 'list.select' => $selected)
			);

		return implode($html);
	}

	/**
	 * Returns html element select plugin
	 *
	 * @param   string  $name          Name of control
	 * @param   string  $value         Value of control
	 * @param   string  &$node         Node name
	 * @param   array   $control_name  Control Name
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function fetchElement($name, $value, &$node, $control_name)
	{
		$input    = Factory::getApplication()->input;
		$couponId = $input->get('id');

		// Select the required fields from the table.
		if ($couponId)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select(array('c.subscription_id', 'c.course_id'));
			$query->from($db->quoteName('#__tjlms_coupons', 'c'));
			$query->where($db->quoteName('c.id') . ' = ' . (int) $couponId);
			$db->setQuery($query);

			$subscriptionObj = $db->loadObject();

			$courseArray = array();

			if (!empty($subscriptionObj->subscription_id) && !empty($subscriptionObj->course_id))
			{
				$courses = explode(',', $subscriptionObj->course_id);

				$i = 1;

				foreach ($courses as $k => $course)
				{
					JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
					$tjlmsCoursesHelper = new TjlmsCoursesHelper;
					$courseName = $tjlmsCoursesHelper->courseName($course);

					$courseArray[$i]['id'] = $course;
					$courseArray[$i]['text'] = $courseName;

					$options  = array();
					$default  = array();
					$subscriptions = $tjlmsCoursesHelper->getCourseSubplans($course);

					foreach ($subscriptions as $key => $value)
					{
						$options[] = HTMLHelper::_('select.option', $value->id, $value->duration . ' ' . $value->time_measure);
					}

					$courseArray[$i]['items'] = $options;
					$i++;
				}
			}

			return $courseArray;
		}
	}
}
