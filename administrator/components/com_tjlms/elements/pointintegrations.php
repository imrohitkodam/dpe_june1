<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
jimport('joomla.html.pane');
jimport('joomla.application.component.helper');
jimport('joomla.filesystem.folder');
jimport('joomla.form.formfield');

/**
 * Function to get points integration select box
 *
 * @since  1.0.0
 */
class JFormFieldPointintegrations extends JFormField
{
	/**
	 * Function to getInput
	 *
	 * @return  complete html.
	 *
	 * @since 1.0.0
	 */
	public function getInput()
	{
		return $this->fetchElement($this->name, $this->value, $this->element, $this->options['controls']);
	}

	/**
	 * Function fetchElement
	 *
	 * @param   STRING  $name          Name of the element
	 * @param   STRING  $value         Value of the element
	 * @param   STRING  &$node         Node
	 * @param   STRING  $control_name  control_name
	 *
	 * @return  complete html.
	 *
	 * @since 1.0.0
	 */
	public function fetchElement($name,$value,&$node,$control_name)
	{
		$communitymainfile = JPATH_SITE . '/components/com_community/libraries/core.php';
		$esfolder = JPATH_SITE . '/components/com_easysocial';
		$alphafolder = JPATH_SITE . '/components/com_alphauserpoints';
		$altafolder = JPATH_SITE . '/components/com_altauserpoints';

		// If point integration
		if ($name == 'jform[pt_option]')
		{
			$options[] = JHTML::_('select.option', 'no', Text::_('COM_TJLMS_NONE'));

			if (File::exists($communitymainfile))
			{
				$options[] = JHTML::_('select.option', 'jspt', Text::_('COM_TJLMS_JOMSOCIAL'));
			}

			if (Folder::exists($esfolder))
			{
				$options[] = JHTML::_('select.option', 'espt', Text::_('COM_TJLMS_EASYSOCIAL'));
			}

			if (Folder::exists($alphafolder) || Folder::exists($altafolder))
			{
				$options[] = JHTML::_('select.option', 'alpha', Text::_('COM_TJLMS_ALPHA_POINTS'));
			}

			$fieldName = $name;

			return HTMLHelper::_('select.genericlist',  $options, $fieldName, 'class="inputbox form-select"  ', 'value', 'text', $value, $control_name . $name);
		}
	}
}
