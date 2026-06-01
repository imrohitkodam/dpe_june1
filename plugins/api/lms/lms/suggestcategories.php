<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_tjlms.libraries.suggestcategories', JPATH_SITE);

/**
 * Suggest categories api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.3.22
 */
class LmsApiResourceSuggestCategories extends ApiResource
{
	/**
	 * API Plugin for get method
	 *
	 * @return  avoid.
	 */
	public function get()
	{
		$user = Factory::getUser();

		$result = new stdClass;
		$result->err_code = '';
		$result->err_message = '';
		$result->data = new stdClass;
		$this->items = '';

		$this->items = TjSuggestCategories::suggestCategories();

		if (empty($this->items) || !$user->id)
		{
			$result->err_code		= '400';
			$result->err_message	= Text::_('PLG_API_TJLMS_REQUIRED_DATA_EMPTY_MESSAGE');
			$this->plugin->setResponse($result);

			return;
		}

		$result->data->result = $this->items;
		unset($this->items);
		$this->plugin->setResponse($result);

		return;
	}
}
