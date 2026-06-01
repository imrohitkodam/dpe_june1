<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to welcome
 *
 * @since  1.3.8
 */
class TjlmsViewTools extends HtmlView
{
	protected $state;

	protected $courses;

	protected $sidebar;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state		= $this->get('State');
		FormHelper::addFieldPath(JPATH_SITE . '/components/com_tjlms/models/fields');
		$courses			=	FormHelper::loadFieldType('courses', false);
		$this->courses		=	$courses->getOptionsExternally();
		TjlmsHelper::getLanguageConstant();

		TjlmsHelper::addSubmenu('tools');

		ToolbarHelper::title(Text::_('COM_TJLMS_TITLE_TOOLS'), 'list');

		$this->sidebar = JHtmlSidebar::render();

		parent::display($tpl);
	}
}
