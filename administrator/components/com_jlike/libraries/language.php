<?php
/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;

/**
 * Jlike language constant class for common methods
 *
 * @since  _DEPLOY_VERSION_
 */
class JlikeLanguage
{
	/**
	 * Language constants to be used in js
	 *
	 * @return   void
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function JsLanguageConstant()
	{
		Text::script('COM_JLIKE_SELECT_USER');
	}
}
