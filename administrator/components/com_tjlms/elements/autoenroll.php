<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.form.formfield');

/**
 * Auto Enroll field class.
 *
 * @since  1.3.31
 */
class JFormFieldAutoenroll extends JFormField
{
	/**
	 * Method to get the field input.
	 *
	 * @return	string	The field input.
	 *
	 * @since	1.3.31
	 */
	public function getInput()
	{
		$params     = ComponentHelper::getParams('com_tjlms');
		$autoEnroll = $params->get('auto_enroll');
		$document   = Factory::getDocument();
		$checkedNo  = $checkedYes = '';

		if ($autoEnroll)
		{
			$checkedYes = 'checked="checked"';
		}
		else
		{
			$checkedNo = 'checked="checked"';
		}

		$html = '<div>
			<fieldset id="jform_auto_enroll" class="btn-group radio">
				<input type="radio" id="jform_auto_enroll0" class="btn-check" name="jform[auto_enroll]" value="1" ' . $checkedYes . '>
				<label for="jform_auto_enroll0" class="btn btn-outline-secondary">
					' . Text::_("JYES") . '
				</label>

				<input type="radio" id="jform_auto_enroll1" class="btn-check" name="jform[auto_enroll]" value="0"' . $checkedNo . ' >
				<label for="jform_auto_enroll1" class="btn btn-outline-secondary">
				' . Text::_("JNO") . '
				</label>
			</fieldset>
		</div>';

		$document->addScriptDeclaration("
			jQuery(document).ready(function()
			{
				jQuery(document).on('click', '[name$=\'[admin_approval]\']', function () {
						jQuery('#jform_auto_enroll1').click();
			 	});
			});
		");

		return $html;
	}
}
