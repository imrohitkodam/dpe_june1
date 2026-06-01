<?php
/**
 * @package     Joomla.API.Plugin
 * @subpackage  com_tjlms-API
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die( 'Restricted access' );
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Language\Text;

jimport('joomla.plugin.plugin');
jimport('joomla.user.helper');
use Joomla\CMS\Factory;

/**
 * API Plugin
 *
 * @package     Joomla_API_Plugin
 * @subpackage  com_tjlms-API-coursesCategories
 * @since       1.0
 */
class LmsApiResourceCategories extends ApiResource
{
	/**
	 * API Plugin for get method
	 *
	 * @return  avoid.
	 */
	public function get()
	{
		$input     = Factory::getApplication()->input;
		$extension = $input->get('extension', 'tjlms', 'string');

		$result = new stdClass;
		$result->err_code = '';
		$result->err_message = '';
		$result->data = new stdClass;
		$this->apiData = array();
		$this->items = '';

		// Import Joomla Categories library
		jimport('joomla.application.categories');
		$categories = Categories::getInstance($extension);

		if ($categories)
		{
			$root = $categories->get('root');
			$this->items = $root->getChildren(true);
		}

		if (empty($this->items))
		{
			$result->err_code		= '400';
			$result->err_message	= Text::_('PLG_API_TJLMS_REQUIRED_COURSE_DATA_EMPTY_MESSAGE');
			$this->plugin->setResponse($result);

			return;
		}

		$this->getApiItems();
		$result->data->result = $this->items;
		unset($this->items);
		$this->plugin->setResponse($result);

		return;
	}

	/**
	 * Method to process Courses categories data for API.
	 *
	 * @return  null
	 *
	 * @since   1.0.0
	 */
	private function getApiItems()
	{
		if (!empty($this->items))
		{
			foreach ($this->items as $ind => &$objCopy)
			{
				// Courses categories Metadata
				$obj = new stdClass;
				$obj->cat_id = $objCopy->id;
				$obj->cat_title = $objCopy->title;
				$obj->cat_level = $objCopy->level;
				$obj->cat_parent_id = $objCopy->parent_id;
				$obj->cat_description = $objCopy->description;
				$params = json_decode($objCopy->params);
				$obj->image = isset($params->image) ? $params->image : '';

				// Assign the new Object
				$objCopy = $obj;
				$obj = null;
			}
		}
	}
}
