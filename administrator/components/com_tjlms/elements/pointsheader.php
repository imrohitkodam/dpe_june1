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

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Uri\Uri;

jimport('joomla.html.parameter.element');
jimport('joomla.html.html');
jimport('joomla.filesystem.folder');
jimport('joomla.form.formfield');

/**
 * Tjlms elements
 *
 * @since  1.0.0
 */
class JFormFieldPointsheader extends JFormField
{
	protected $type = 'pointsheader';

	/**
	 * Get html of the element
	 *
	 * @return  Html
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		$communityfolder = JPATH_SITE . DS . 'components' . DS . 'com_community';
		$esfolder        = JPATH_SITE . DS . 'components' . DS . 'com_easysocial';
		$alphafolder     = JPATH_SITE . DS . 'components' . DS . 'com_alphauserpoints';
		$altafolder     = JPATH_SITE . DS . 'components' . DS . 'com_altauserpoints';

		$document = Factory::getDocument();

		if ($this->class == 'show_notes')
		{
			return $return = '

			<div class="alert alert-info">
				' . Text::_($this->value) . '
			</div>';
		}
		elseif ($this->class == 'show_notes show_point_notes')
		{
			$html = array();

			if (Folder::exists($alphafolder))
			{
				$aup_click_link = "<strong><a href='" . JURI::root() . "components/com_tjlms/alphapoints/tjlms_aup.zip'>" . Text::_('HERE') . "</a></strong>";

				$aupPluginLink = "index.php?option=com_alphauserpoints&task=plugins";
				$aup_install_link = "<strong><a href='" . JURI::base() . $aupPluginLink . "' target='_blank'>" . Text::_('HERE') . "</a></strong>";

				$html[] = Text::sprintf('COM_TJLMS_AUP_POINT_SYSTEM_NOTE',
				$aup_click_link,
				$aup_install_link,
				Text::_('COM_TJLMS_COURSE_ENROLLMENT_POINTS'),
				Text::_('COM_TJLMS_LESSON_COMPLETION_POINTS')
				);
			}

			if (Folder::exists($altafolder))
			{
				$aup_click_link = "<strong><a href='" . JURI::root() . "components/com_tjlms/altapoints/tjlms_aup.zip'>" . Text::_('HERE') . "</a></strong>";

				$aupPluginLink = "index.php?option=com_altauserpoints&task=plugins";
				$aup_install_link = "<strong><a href='" . JURI::base() . $aupPluginLink . "' target='_blank'>" . Text::_('HERE') . "</a></strong>";

				$html[] = Text::sprintf('COM_TJLMS_ALTAUP_POINT_SYSTEM_NOTE',
				$aup_click_link,
				$aup_install_link,
				Text::_('COM_TJLMS_COURSE_ENROLLMENT_POINTS'),
				Text::_('COM_TJLMS_LESSON_COMPLETION_POINTS')
				);
			}

			if (Folder::exists($communityfolder))
			{
				$html[] = Text::_('COM_TJLMS_JS_POINT_SYSTEM_NOTE');
			}

			if (Folder::exists($esfolder))
			{
				$html[] = Text::_('COM_TJLMS_ES_POINT_SYSTEM_NOTE');
			}

			$html_str = implode('<br/><hr/>', $html);

			if (!empty($html_str))
			{
				return '<div class="alert alert-info">' . $html_str . '</div>';
			}
			else
			{
				return '';
			}
		}
		else
		{
			$document->addStyleSheet(Uri::root() . 'media/com_tjlms/css/tjlms_backend.css');
			$return = '
			<div class="tjlms_header_div_outer">
				<div class="tjlms_header_div_inner">
					' . Text::_($this->value) . '
				</div>
			</div>';

			return $return;
		}
	}
}
