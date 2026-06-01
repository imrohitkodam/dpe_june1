<?php
/**
 * @package     SLA
 * @subpackage  sla_activity
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2019 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');
use Joomla\CMS\Component\ComponentHelper;

/**
 * Cron field for component params.
 *
 * @package  Sla_Activity
 * @since    __DEPLOY_VERSION__
 */
class JFormFieldRemindercron extends JFormField
{
	/**
	 * Method to get input
	 *
	 * @return  void|string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getInput()
	{
		$isEnabled = ComponentHelper::isEnabled('com_jlike');

		if (!$isEnabled)
		{
			return '';
		}

		$this->fetchElement($this->name, $this->value, $this->element, $this->options['controls']);
	}

	/**
	 * Method to fetch elements
	 *
	 * @param   string  $name          Name of element.
	 * @param   string  $value         Value for the element.
	 * @param   string  &$node         node of element.
	 * @param   string  $control_name  Control name.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function fetchElement($name,$value,&$node,$control_name)
	{
		echo "<style>.sla_cron_url{padding:5px 5px 0px 0px;}</style>";
		echo "<div class='sla_cron_url' ><strong>" . JUri::root() . "index.php?option=com_jlike&task=remindersCron&tmpl=component</strong></div>";
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getLabel()
	{
		$isEnabled = ComponentHelper::isEnabled('com_jlike');

		if (!$isEnabled)
		{
			return '';
		}

		return parent::getLabel();
	}
}
