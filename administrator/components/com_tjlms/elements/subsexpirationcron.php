<?php
/**
 * @package    Invitex
 *
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

jimport('joomla.form.formfield');

/**
 * Cron element.
 *
 * @since  1.6
 */
class JFormFieldSubsexpirationcron extends JFormField
{
	/**
	 * Method to get input
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function getInput()
	{
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
	 * @since   1.6
	 */
	public function fetchElement($name,$value,&$node,$control_name)
	{
		$params = ComponentHelper::getParams('com_tjlms');
		$private_key_cronjob = $params->get('private_key_storage_cron');

		echo	"<style>.tjlms_cron_url{padding:5px 5px 0px 0px;}</style>";
		echo	"<div class='tjlms_cron_url' ><strong>" .
				Uri::root() . 'index.php?option=com_tjlms&task=expiredsubscron&tmpl=component&pkey=' .
				$private_key_cronjob . "</strong></div>";
	}
}
