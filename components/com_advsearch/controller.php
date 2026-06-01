<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

jimport('joomla.application.component.controller');

require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/advsearch.php';

/**
 * advance search controller
 *
 * @since  1.6
 */
class AdvsearchController extends BaseController
{
	/**
	 * Method to get attributes.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function get_attributes()
	{
		$input = Factory::getApplication()->input;
		$type  = $input->get('type', '', 'STRING');

		if ($type)
		{
			// Calling advsearch getAttributes method to get the search parameters.
			$model  = $this->getModel('advsearch');
			$result = $model->getAttributes($type);

			if ($result)
			{
				echo $result;
			}
			else
			{
				echo Text::_('COM_ADVANCED_SEARCH_SELECT_INDEXER_FIELDS_FIRST');
			}

			Jexit();
		}
		else
		{
			echo Text::_('COM_ADVANCED_SEARCH_SELECT_INDEXER_FIRST');
		}

		Jexit();
	}

	/**
	 * Method to get fields.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function get_fields()
	{
		$model = $this->getModel('advsearch');
		echo $Fields = $model->getFields();
		Jexit();
	}

	/**
	 * Method to get form fields.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function getFormfield()
	{
		$field_key = JRequest::getVar('field');
		$model     = $this->getModel('advsearch');
		echo $Fields = $model->getFieldType();
		Jexit();
	}

	/**
	 * Method for delete advanced serach saved searches.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function delete()
	{
		$db    = Factory::getDBO();
		$id    = JRequest::getVar('id');
		$query = "DELETE FROM `paai_advanced_search_saved_searches` WHERE id=$id";
		$db->setQuery($query);
		$db->loadObjectList();
		$message = "deleted sucessfully";
		$this->setRedirect(Route::_('index.php?option=com_advsearch&view=searchlist'), $message);
	}

	/**
	 * Method for crone job .
	 *
	 * @return boolean
	 *
	 * @since    1.6
	 */
	public function cronjob()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$pkey = $input->get('pkey', '', 'STRING');

				$params = ComponentHelper::getParams('com_advsearch');
				$privateKey = $params->get('private_key');

			if ($pkey === $privateKey)
			{
				$model	= $this->getModel('searchindexer');
				$model->cronjob();
			}
			else
			{
				$this->setRedirect(Route::_(Uri::base()), Text::_('COM_ADVANCED_SEARCH_DENIED'), 'error');

			return false;
			}
	}

	/**
	 * Method to get related field options.
	 *
	 * @return model
	 *
	 * @since    1.6
	 */
	public function getRelatedFieldOptions()
	{
		$model = $this->getModel('advsearch');
		echo json_encode($model->getRelatedFieldOptions());
		Jexit();
	}
}
